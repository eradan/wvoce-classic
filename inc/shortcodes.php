<?php
/**
 * Shortcode da usare nei widget SiteOrigin, nei widget del footer e nelle pagine.
 *
 *   [vv_cards categoria="appuntamenti" numero="3" variante="evento" colonne="3"]
 *                                        (non ripete articoli già mostrati sopra; doppioni="si" per permetterlo)
 *   [vv_cards in_evidenza="si" numero="3"]                 solo gli articoli spuntati "In evidenza"
 *   [vv_cards fissati="si" numero="10" layout="carosello"] i fissati + i 10 più recenti, dal più recente, a carosello
 *   [vv_cards categoria="eventi" periodo="futuro"]        solo gli eventi non ancora finiti (date dell'evento), dal più vicino
 *   [vv_cards categoria="eventi" periodo="dal-3-mesi" layout="carosello" autoplay="si"]
 *                                        anche quelli finiti da meno di 3 mesi, dopo i prossimi; senza nessuno, gli "In evidenza"
 *                                        (con periodo="futuro", invece, una frase "niente in programma").
 *                                        autoplay="si": il carosello scorre da solo (con pausa)
 *   [vv_sottopagine]                     card delle pagine figlie della pagina corrente
 *   [vv_elenco categoria="laboratori"]   almeno 9 card grandi (in programma, poi le più recenti) + archivio per anno;
 *                                        senza date (progetti) un'unica griglia di schede
 *   [vv_sostienici]                      socio / donazione / 5×1000
 *   [vv_dati_fiscali]                    CF, P.IVA, IBAN con pulsante "copia" (mostra="iban" o "cf": uno solo)
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * [vv_cards]
 *
 * @param array $atts Attributi.
 */
function vv_sc_cards( $atts ) {
	$atts = shortcode_atts( array(
		'categoria'   => '',
		'numero'      => 6,
		'variante'    => 'evento',
		'colonne'     => '',
		'ordine'      => 'data',
		'ids'         => '',
		'doppioni'    => 'no',
		'in_evidenza' => 'no',
		'fissati'     => 'no',
		'layout'      => 'griglia',
		'periodo'     => '',
		'autoplay'    => 'no',
	), $atts, 'vv_cards' );

	// Articoli già mostrati da un [vv_cards] precedente nella stessa pagina.
	static $shown = array();

	$slugs   = sanitize_text_field( $atts['categoria'] );
	$exclude = ( 'si' !== $atts['doppioni'] && ! $atts['ids'] ) ? $shown : array();

	$extra = array();
	if ( $atts['ids'] ) {
		$extra['post__in'] = array_map( 'absint', explode( ',', $atts['ids'] ) );
		$extra['orderby']  = 'post__in';
	} elseif ( 'titolo' === $atts['ordine'] ) {
		$extra['orderby'] = 'title';
		$extra['order']   = 'ASC';
	}
	// Spunta "In evidenza": stesso campo del vecchio plugin Featured Post Creative.
	if ( 'si' === $atts['in_evidenza'] ) {
		$extra = vv_add_meta_query( $extra, vv_featured_clause() );
	}

	// Articoli "Fissati in cima al blog" prima degli altri, come faceva il vecchio carosello.
	$sticky = array();
	if ( 'si' === $atts['fissati'] && ! $atts['ids'] ) {
		$sticky_ids = array_diff( array_map( 'absint', (array) get_option( 'sticky_posts', array() ) ), $exclude );
		if ( $sticky_ids ) {
			$sticky  = vv_posts_in( $slugs, -1, array_merge( $extra, array( 'post__in' => array_values( $sticky_ids ) ) ) );
			$exclude = array_merge( $exclude, wp_list_pluck( $sticky, 'ID' ) );
		}
	}

	if ( $exclude ) {
		$extra['post__not_in'] = array_values( array_unique( $exclude ) );
	}
	if ( in_array( $atts['periodo'], array( 'futuro', 'dal-3-mesi' ), true ) && ! $atts['ids'] ) {
		$posts = vv_posts_by_period( $slugs, $atts['periodo'], (int) $atts['numero'], $extra );
		// Niente in calendario: in home gli "In evidenza", perché la sezione non resti vuota; nella
		// pagina "In programma" lo si dice (gli "In evidenza" sembrerebbero in programma).
		if ( ! $posts && 'futuro' === $atts['periodo'] ) {
			return '<p class="vv-empty">Al momento non ci sono appuntamenti in programma: torna a trovarci presto!</p>';
		}
		if ( ! $posts ) {
			$posts = vv_posts_in( '', (int) $atts['numero'], vv_add_meta_query( $extra, vv_featured_clause() ) );
		}
	} else {
		$posts = vv_posts_in( $slugs, (int) $atts['numero'], $extra );
	}
	$posts = array_merge( $sticky, $posts );
	// I fissati entrano nell'elenco ma in ordine di data, dal più recente.
	if ( $sticky && 'data' === $atts['ordine'] ) {
		usort( $posts, static fn( $a, $b ) => strcmp( $b->post_date, $a->post_date ) );
	}
	$shown = array_merge( $shown, wp_list_pluck( $posts, 'ID' ) );

	$opts = array(
		'layout'   => 'carosello' === $atts['layout'] ? 'carosello' : 'griglia',
		'autoplay' => 'si' === $atts['autoplay'],
	);
	if ( $atts['colonne'] ) {
		$opts['colonne'] = max( 1, min( 4, (int) $atts['colonne'] ) );
	}
	return vv_render_cards( $posts, sanitize_key( $atts['variante'] ), $opts );
}
add_shortcode( 'vv_cards', 'vv_sc_cards' );

/**
 * Pagine figlie di una pagina, come card.
 *
 * @param int $parent_id Pagina madre.
 */
function vv_child_pages_cards( $parent_id ) {
	$children = vv_page_children( $parent_id );
	return vv_render_cards( $children, 'pagina', array( 'colonne' => count( $children ) >= 3 ? 3 : 2 ) );
}

/**
 * Card in fondo alla Pagina hub, a meno che il testo non le metta già altrove con [vv_sottopagine].
 *
 * @param int $page_id Pagina hub.
 */
function vv_hub_cards( $page_id ) {
	return has_shortcode( (string) get_post_field( 'post_content', $page_id ), 'vv_sottopagine' ) ? '' : vv_child_pages_cards( $page_id );
}

/**
 * [vv_sottopagine padre="ID"]
 *
 * @param array $atts Attributi.
 */
function vv_sc_sottopagine( $atts ) {
	$atts = shortcode_atts( array( 'padre' => get_the_ID() ), $atts, 'vv_sottopagine' );
	return vv_child_pages_cards( absint( $atts['padre'] ) );
}
add_shortcode( 'vv_sottopagine', 'vv_sc_sottopagine' );

/**
 * Elenco delle "Pagine elenco": in alto almeno 9 card grandi, prima gli eventi in programma (dal più
 * vicino) e, se non bastano, i più recenti già passati; sotto il resto, anno per anno. Se nessun
 * articolo ha le date dell'evento (i progetti) è un'unica griglia di schede, senza anni.
 *
 * @param string $slugs Categorie (slug separati da virgola).
 * @return string HTML.
 */
function vv_render_elenco( $slugs ) {
	$posts = vv_posts_in( $slugs, -1 );
	if ( ! $posts ) {
		return '';
	}

	$items = array();
	foreach ( $posts as $post ) {
		$items[] = array( 'post' => $post, 'dates' => vv_event_dates( $post ) );
	}

	// Schede (Progetti): nessuna data, nessun anno, il riassunto intero.
	if ( ! array_filter( $items, static fn( $i ) => $i['dates']['has_meta'] ) ) {
		return '<section class="vv-elenco vv-elenco--schede">' . vv_render_cards( $posts, 'evento' ) . '</section>';
	}

	$min      = (int) apply_filters( 'vv_elenco_grandi', 9 );
	$now      = time();
	$upcoming = array_values( array_filter( $items, static fn( $i ) => $i['dates']['has_meta'] && $i['dates']['end'] >= $now ) );
	$past     = array_values( array_filter( $items, static fn( $i ) => ! ( $i['dates']['has_meta'] && $i['dates']['end'] >= $now ) ) );
	usort( $upcoming, static fn( $a, $b ) => $a['dates']['start'] <=> $b['dates']['start'] );
	usort( $past, static fn( $a, $b ) => $b['dates']['end'] <=> $a['dates']['end'] );
	$fill = array_slice( $past, 0, max( 0, $min - count( $upcoming ) ) );
	$big  = array_merge( $upcoming, $fill );
	$rest = array_slice( $past, count( $fill ) );

	if ( $upcoming && ! $fill ) {
		$title = 'In programma';
	} elseif ( $upcoming ) {
		$title = 'In programma e ultimi appuntamenti';
	} else {
		$title = 'Ultimi appuntamenti';
	}

	ob_start();
	echo '<section class="vv-elenco vv-elenco--prossimi"><h2 class="vv-section-title">' . esc_html( $title ) . '</h2>';
	echo vv_render_cards( wp_list_pluck( $big, 'post' ), 'evento' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</section>';

	$archive = array();
	foreach ( $rest as $item ) {
		$archive[ wp_date( 'Y', $item['dates']['start'] ) ][] = $item;
	}
	krsort( $archive );
	if ( $archive ) {
		echo '<section class="vv-elenco vv-elenco--archivio"><h2 class="vv-section-title">Archivio</h2>';
		foreach ( $archive as $year => $year_items ) {
			usort( $year_items, static fn( $a, $b ) => $b['dates']['start'] <=> $a['dates']['start'] );
			printf( '<h3 class="vv-year">%s</h3>', esc_html( $year ) );
			echo vv_render_cards( wp_list_pluck( $year_items, 'post' ), 'evento', array( 'colonne' => 4, 'titolo_livello' => 'h4' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</section>';
	}
	return ob_get_clean();
}

/**
 * [vv_elenco categoria="…"]
 *
 * @param array $atts Attributi.
 */
function vv_sc_elenco( $atts ) {
	$atts = shortcode_atts( array( 'categoria' => '' ), $atts, 'vv_elenco' );
	return vv_render_elenco( vv_sanitize_slugs( $atts['categoria'] ) );
}
add_shortcode( 'vv_elenco', 'vv_sc_elenco' );

/**
 * [vv_sostienici]
 */
function vv_sc_sostienici() {
	$items = array(
		array(
			'accento' => 'giallo',
			'titolo'  => 'Diventa socio',
			'testo'   => 'La tessera vale da settembre ad agosto e apre le porte a tutte le nostre attività.',
			'link'    => vv_mod( 'vv_url_socio' ),
			'azione'  => 'Iscriviti',
		),
		array(
			'accento' => 'turchese',
			'titolo'  => 'Fai una donazione',
			'testo'   => 'Anche un piccolo contributo ci aiuta a portare letture e laboratori in città.',
			'link'    => vv_mod( 'vv_url_donazione' ),
			'azione'  => 'Dona ora',
		),
		array(
			'accento' => 'rosso',
			'titolo'  => 'Il tuo 5×1000',
			'testo'   => 'Nella dichiarazione dei redditi scrivi il nostro codice fiscale:',
			'cf'      => vv_mod( 'vv_cf' ),
		),
	);

	ob_start();
	echo '<div class="vv-grid vv-grid--3 vv-sostienici">';
	foreach ( $items as $item ) {
		printf( '<div class="vv-card vv-card--cta vv-accent--%s"><div class="vv-card__body">', esc_attr( $item['accento'] ) );
		printf( '<h3 class="vv-card__title">%s</h3><p class="vv-card__text">%s</p>', esc_html( $item['titolo'] ), esc_html( $item['testo'] ) );
		if ( ! empty( $item['cf'] ) ) {
			echo vv_copy_value( $item['cf'], 'Copia il codice fiscale' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			printf( '<a class="vv-button" href="%s">%s</a>', esc_url( $item['link'] ), esc_html( $item['azione'] ) );
		}
		echo '</div></div>';
	}
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'vv_sostienici', 'vv_sc_sostienici' );

/**
 * Valore con pulsante "copia" (il pulsante compare solo con JavaScript attivo).
 *
 * @param string $value Valore.
 * @param string $label Etichetta accessibile del pulsante.
 */
function vv_copy_value( $value, $label ) {
	return sprintf(
		'<span class="vv-copy"><code class="vv-copy__value">%1$s</code><button type="button" class="vv-copy__btn" data-vv-copy="%1$s" aria-label="%2$s" hidden>Copia</button></span>',
		esc_attr( $value ),
		esc_attr( $label )
	);
}

/**
 * [vv_dati_fiscali] per il footer; [vv_dati_fiscali mostra="iban"] o mostra="cf" solo quel dato.
 *
 * @param array $atts Attributi.
 */
function vv_sc_dati_fiscali( $atts = array() ) {
	$atts = shortcode_atts( array( 'mostra' => 'cf,piva,iban' ), $atts, 'vv_dati_fiscali' );
	$show = array_map( 'trim', explode( ',', strtolower( (string) $atts['mostra'] ) ) );
	$rows = array(
		'cf'   => array( 'CF (5×1000)', vv_mod( 'vv_cf' ), 'Copia il codice fiscale' ),
		'piva' => array( 'P.IVA', vv_mod( 'vv_piva' ), 'Copia la partita IVA' ),
		'iban' => array( 'IBAN', vv_mod( 'vv_iban' ), 'Copia l\'IBAN' ),
	);
	$out  = '<dl class="vv-dati">';
	foreach ( $rows as $key => list( $label, $value, $aria ) ) {
		if ( $value && in_array( $key, $show, true ) ) {
			$out .= '<dt>' . esc_html( $label ) . '</dt><dd>' . vv_copy_value( $value, $aria ) . '</dd>';
		}
	}
	return $out . '</dl>';
}
add_shortcode( 'vv_dati_fiscali', 'vv_sc_dati_fiscali' );
