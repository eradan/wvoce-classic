<?php
/**
 * Shortcode per le pagine e i widget del footer.
 *
 *   [vv_cards categoria="appuntamenti" numero="3" variante="evento" colonne="3"]
 *                                        (non ripete articoli già mostrati sopra; doppioni="si" per permetterlo)
 *   [vv_cards in_evidenza="si" numero="3"]                 solo gli articoli spuntati "In evidenza"
 *   [vv_cards fissati="si" numero="10" layout="carosello"] i fissati + i 10 più recenti, dal più recente, a carosello
 *                                        (con periodo, i fissati davanti agli eventi, che restano nel loro ordine)
 *   [vv_cards categoria="eventi" periodo="futuro"]        solo gli eventi non ancora finiti (date dell'evento), dal più vicino
 *   [vv_cards categoria="eventi" periodo="dal-3-mesi" layout="carosello" autoplay="si"]
 *                                        anche quelli finiti da meno di 3 mesi, dopo i prossimi; senza nessuno, gli "In evidenza"
 *                                        (con periodo="futuro", invece, una frase "niente in programma").
 *                                        autoplay="si": il carosello scorre da solo (con pausa)
 *   [vv_cards categoria="eventi" periodo="dal-3-mesi" numero="9" layout="agenda" lotto="3"]
 *                                        agenda: righe compatte su telefono, griglia sopra; lotto="3" ne mostra 3 e poi
 *                                        "Mostra altri" ne apre altre 3 alla volta
 *   [vv_cards ids="12,34,56"]            quegli articoli, nell'ordine scritto (al massimo numero, e solo quelli di
 *                                        categoria e in_evidenza se indicati); periodo, fissati, ordine e doppioni
 *                                        non contano
 *   [vv_cards categoria="rassegna-stampa" variante="citazione" numero="6" layout="carosello"]
 *                                        ritagli solo testo: testata e data, poi il titolo fra virgolette
 *   [vv_sottopagine]                     card delle pagine figlie della pagina corrente
 *   [vv_elenco categoria="laboratori"]   almeno 9 card grandi (in programma, poi le più recenti) + archivio per anno;
 *                                        senza date (progetti) un'unica griglia di schede
 *   [vv_sostienici]                      socio / donazione / 5×1000
 *   [vv_dati_fiscali]                    CF, P.IVA, IBAN con pulsante "copia" (mostra="iban" o "cf": uno solo)
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

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
		'lotto'       => 0,
	), $atts, 'vv_cards' );

	static $gia_mostrati = array(); // dai [vv_cards] precedenti nella stessa pagina

	$categorie = sanitize_text_field( $atts['categoria'] );
	$numero    = (int) $atts['numero'];
	$scelti    = '' !== $atts['ids']; // articoli scelti uno per uno: niente periodo, fissati né doppioni
	$periodo   = ! $scelti && in_array( $atts['periodo'], array( 'futuro', 'dal-3-mesi' ), true ) ? $atts['periodo'] : '';
	$esclusi   = ! $scelti && 'si' !== $atts['doppioni'] ? $gia_mostrati : array();
	$extra     = vv_argomenti_cards( $atts );
	$fissati   = ! $scelti && 'si' === $atts['fissati'] ? vv_fissati( $categorie, $extra, $esclusi ) : array();
	$esclusi   = array_merge( $esclusi, wp_list_pluck( $fissati, 'ID' ) );
	if ( $esclusi ) {
		$extra['post__not_in'] = array_values( array_unique( $esclusi ) );
	}

	if ( $periodo ) {
		$articoli = vv_eventi_del_periodo( $categorie, $periodo, $numero, $extra );
		// Nella pagina "In programma" gli "In evidenza" sembrerebbero in programma: meglio dirlo.
		if ( ! $articoli && 'futuro' === $periodo ) {
			return '<p class="vv-empty">Al momento non ci sono appuntamenti in programma: torna a trovarci presto!</p>';
		}
		if ( ! $articoli ) {
			$articoli = vv_in_evidenza( $numero, $extra );
		}
	} else {
		$articoli = vv_articoli( $categorie, $numero, $extra );
	}

	$articoli = array_merge( $fissati, $articoli );
	// I fissati in ordine di data con gli altri; con un periodo restano davanti, per non disfare il calendario.
	if ( $fissati && ! $periodo && 'data' === $atts['ordine'] ) {
		usort( $articoli, static fn( $a, $b ) => strcmp( $b->post_date, $a->post_date ) );
	}
	$gia_mostrati = array_merge( $gia_mostrati, wp_list_pluck( $articoli, 'ID' ) );

	return vv_griglia_card( $articoli, sanitize_key( $atts['variante'] ), vv_opzioni_cards( $atts ) );
}
add_shortcode( 'vv_cards', 'vv_sc_cards' );

function vv_argomenti_cards( $atts ) {
	$argomenti = array();
	if ( $atts['ids'] ) {
		$argomenti['post__in'] = array_map( 'absint', explode( ',', $atts['ids'] ) );
		$argomenti['orderby']  = 'post__in';
	} elseif ( 'titolo' === $atts['ordine'] ) {
		$argomenti['orderby'] = 'title';
		$argomenti['order']   = 'ASC';
	}
	if ( 'si' === $atts['in_evidenza'] ) {
		$argomenti = vv_aggiungi_meta_query( $argomenti, vv_condizione_in_evidenza() );
	}
	return $argomenti;
}

/**
 * Gli articoli "Fissati in cima al blog" delle categorie, come nel vecchio carosello.
 *
 * @return WP_Post[]
 */
function vv_fissati( $categorie, $extra, $esclusi ) {
	$fissati_id = array_diff( array_map( 'absint', (array) get_option( 'sticky_posts', array() ) ), $esclusi );
	return $fissati_id ? vv_articoli( $categorie, -1, array_merge( $extra, array( 'post__in' => array_values( $fissati_id ) ) ) ) : array();
}

function vv_opzioni_cards( $atts ) {
	$opzioni = array(
		'layout'   => in_array( $atts['layout'], array( 'carosello', 'agenda' ), true ) ? $atts['layout'] : 'griglia',
		'autoplay' => 'si' === $atts['autoplay'],
		'lotto'    => max( 0, (int) $atts['lotto'] ),
	);
	if ( $atts['colonne'] ) {
		$opzioni['colonne'] = max( 1, min( 4, (int) $atts['colonne'] ) );
	}
	return $opzioni;
}

function vv_sc_sottopagine( $atts ) {
	$atts  = shortcode_atts( array( 'padre' => get_the_ID() ), $atts, 'vv_sottopagine' );
	$madre = absint( $atts['padre'] );
	// Fuori da una pagina la madre sarebbe 0: tutte le pagine di primo livello.
	return $madre ? vv_card_sottopagine( $madre ) : '';
}
add_shortcode( 'vv_sottopagine', 'vv_sc_sottopagine' );

/**
 * Almeno 9 card grandi in alto, nell'ordine di vv_ordina_eventi(): prima quelli in programma, poi se
 * non bastano i più recenti; sotto il resto, anno per anno. Senza date (i progetti) una sola griglia.
 */
function vv_elenco_html( $categorie ) {
	$articoli = vv_articoli( $categorie, -1 );
	if ( ! $articoli ) {
		return '';
	}
	$adesso = time();
	$eventi = array();
	foreach ( $articoli as $articolo ) {
		$date     = vv_date_articolo( $articolo );
		$eventi[] = array( $articolo, $date['inizio'], $date['fine'], $date );
	}
	if ( ! array_filter( $eventi, static fn( $e ) => $e[3]['con_date'] ) ) {
		return '<section class="vv-elenco vv-elenco--schede">' . vv_griglia_card( $articoli, 'evento' ) . '</section>';
	}

	$minimo       = 9;
	$ordinati     = vv_ordina_eventi( $eventi, $adesso ); // senza date un articolo finisce fra i conclusi
	$in_programma = array_filter( $ordinati, static fn( $e ) => $e[3]['con_date'] && 'concluso' !== vv_fase_evento( $e[3], $adesso ) );
	$passati      = array_values( array_diff_key( $ordinati, $in_programma ) );
	$riempitivi   = array_slice( $passati, 0, max( 0, $minimo - count( $in_programma ) ) );
	$titolo       = match ( true ) {
		! $in_programma => 'Ultimi appuntamenti',
		! $riempitivi   => 'In programma',
		default         => 'In programma e ultimi appuntamenti',
	};

	$html = '<section class="vv-elenco vv-elenco--prossimi"><h2 class="vv-section-title">' . esc_html( $titolo ) . '</h2>'
		. vv_griglia_card( array_column( array_merge( $in_programma, $riempitivi ), 0 ), 'evento' ) . '</section>';
	return $html . vv_archivio_per_anno( array_slice( $passati, count( $riempitivi ) ) );
}

/**
 * @param array $eventi array( contenuto, inizio, fine ), già in ordine.
 */
function vv_archivio_per_anno( $eventi ) {
	if ( ! $eventi ) {
		return '';
	}
	$per_anno = array();
	foreach ( $eventi as $evento ) {
		$per_anno[ wp_date( 'Y', $evento[1] ) ][] = $evento[0];
	}
	krsort( $per_anno );
	$html = '<section class="vv-elenco vv-elenco--archivio"><h2 class="vv-section-title">Archivio</h2>';
	foreach ( $per_anno as $anno => $articoli ) {
		$html .= '<h3 class="vv-year">' . esc_html( $anno ) . '</h3>'
			. vv_griglia_card( $articoli, 'evento', array( 'colonne' => 4, 'titolo_livello' => 'h4' ) );
	}
	return $html . '</section>';
}

function vv_sc_elenco( $atts ) {
	$atts = shortcode_atts( array( 'categoria' => '' ), $atts, 'vv_elenco' );
	return vv_elenco_html( vv_pulisci_slug( $atts['categoria'] ) );
}
add_shortcode( 'vv_elenco', 'vv_sc_elenco' );

/**
 * "Diventa socio" e "Fai una donazione" con titolo, riassunto e immagine delle pagine scelte nel
 * Customizer; il 5×1000, che una pagina non ce l'ha, con codice fiscale e immagine scelti lì.
 */
function vv_sc_sostienici() {
	$immagine = array(
		'class'   => 'vv-card__img',
		'loading' => 'lazy',
		'alt'     => '',
		'sizes'   => '(max-width: 991px) 100vw, 380px',
	);
	$pagina   = static function ( $quale, $accento, $azione ) use ( $immagine ) {
		$id = vv_pagina_id( $quale );
		return $id ? array(
			'accento' => $accento,
			'titolo'  => wp_strip_all_tags( get_the_title( $id ) ),
			'testo'   => has_excerpt( $id ) ? get_the_excerpt( $id ) : '',
			'link'    => get_permalink( $id ),
			'azione'  => $azione,
			'foto'    => get_the_post_thumbnail( $id, 'vv-card', $immagine ),
		) : null;
	};
	$cf       = vv_mod( 'vv_cf' );
	$riquadri = array_filter( array(
		$pagina( 'socio', 'giallo', 'Iscriviti' ),
		$pagina( 'donazione', 'turchese', 'Dona ora' ),
		$cf ? array(
			'accento' => 'rosso',
			'titolo'  => 'Il tuo 5×1000',
			'testo'   => 'Nella dichiarazione dei redditi scrivi il nostro codice fiscale:',
			'cf'      => $cf,
			'foto'    => wp_get_attachment_image( (int) vv_mod( 'vv_img_5x1000' ), 'vv-card', false, $immagine ),
		) : null,
	) );

	$html = '<div class="vv-grid vv-grid--3 vv-sostienici">';
	foreach ( $riquadri as $riquadro ) {
		$html .= '<div class="vv-card vv-card--cta vv-accent--' . esc_attr( $riquadro['accento'] ) . '">';
		$html .= $riquadro['foto'] ? '<div class="vv-card__media">' . $riquadro['foto'] . '</div>' : '';
		$html .= '<div class="vv-card__body"><h3 class="vv-card__title">' . esc_html( $riquadro['titolo'] ) . '</h3>';
		$html .= $riquadro['testo'] ? '<p class="vv-card__text">' . esc_html( $riquadro['testo'] ) . '</p>' : '';
		$html .= isset( $riquadro['cf'] )
			? vv_valore_copiabile( $riquadro['cf'], 'Copia il codice fiscale' )
			: '<a class="vv-button" href="' . esc_url( $riquadro['link'] ) . '">' . esc_html( $riquadro['azione'] ) . '</a>';
		$html .= '</div></div>';
	}
	return $html . '</div>';
}
add_shortcode( 'vv_sostienici', 'vv_sc_sostienici' );

/**
 * Il pulsante "Copia" compare solo con JavaScript.
 */
function vv_valore_copiabile( $valore, $etichetta ) {
	return sprintf(
		'<span class="vv-copy"><code class="vv-copy__value">%1$s</code><button type="button" class="vv-copy__btn" data-vv-copy="%1$s" aria-label="%2$s" hidden>Copia</button></span>',
		esc_attr( $valore ),
		esc_attr( $etichetta )
	);
}

function vv_sc_dati_fiscali( $atts = array() ) {
	$atts   = shortcode_atts( array( 'mostra' => 'cf,piva,iban' ), $atts, 'vv_dati_fiscali' );
	$mostra = array_map( 'trim', explode( ',', strtolower( (string) $atts['mostra'] ) ) );
	$dati   = array(
		'cf'   => array( 'CF (5×1000)', vv_mod( 'vv_cf' ), 'Copia il codice fiscale' ),
		'piva' => array( 'P.IVA', vv_mod( 'vv_piva' ), 'Copia la partita IVA' ),
		'iban' => array( 'IBAN', vv_mod( 'vv_iban' ), 'Copia l\'IBAN' ),
	);
	$elenco = '<dl class="vv-dati">';
	foreach ( $dati as $chiave => list( $nome, $valore, $etichetta ) ) {
		if ( $valore && in_array( $chiave, $mostra, true ) ) {
			$elenco .= '<dt>' . esc_html( $nome ) . '</dt><dd>' . vv_valore_copiabile( $valore, $etichetta ) . '</dd>';
		}
	}
	return $elenco . '</dl>';
}
add_shortcode( 'vv_dati_fiscali', 'vv_sc_dati_fiscali' );
