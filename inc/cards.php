<?php
/**
 * Card e griglie: un solo componente per home, pagine hub, elenchi e archivi.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Date di un articolo-evento come timestamp (inizio/fine) più l'etichetta leggibile.
 *
 * @param WP_Post|int $post Articolo.
 * @return array{start:int,end:int,label:string,has_meta:bool}
 */
function vv_event_dates( $post ) {
	$post  = get_post( $post );
	$start = get_post_meta( $post->ID, 'vv_data', true );
	$end   = get_post_meta( $post->ID, 'vv_data_fine', true );
	$tz    = wp_timezone();

	// Una data salvata prima dei controlli (o scritta a mano nel database) che non è AAAA-MM-GG
	// conta come assente: DateTimeImmutable fermerebbe la pagina con un errore.
	$start = vv_sanitize_date( $start );
	$end   = vv_sanitize_date( $end );
	// Una fine scritta prima dell'inizio (errore di battitura) non fa sparire l'evento in anticipo.
	if ( $start && $end && $end < $start ) {
		$end = $start;
	}

	$has_meta = (bool) $start;
	$start_ts = $start ? ( new DateTimeImmutable( $start, $tz ) )->getTimestamp() : get_post_timestamp( $post );
	// Un evento resta "in programma" fino alla fine del suo ultimo giorno.
	$last_day = $end ? $end : $start;
	$end_ts   = $last_day ? ( new DateTimeImmutable( $last_day . ' 23:59:59', $tz ) )->getTimestamp() : $start_ts;

	$label = $has_meta && $end && $end !== $start ? vv_date_range_label( $start_ts, $end_ts ) : wp_date( 'j F Y', $start_ts );

	return array(
		'start'    => $start_ts,
		'end'      => $end_ts,
		'label'    => $label,
		'has_meta' => $has_meta,
	);
}

/**
 * Etichetta di un evento su più giorni. Fino a un mese i giorni contano ("3–5 ottobre 2026",
 * "28 settembre – 3 ottobre 2026"); oltre, per i corsi che durano una stagione, bastano i mesi
 * ("ott–dic 2026", "ott 2026 – mag 2027").
 *
 * @param int $start_ts Inizio.
 * @param int $end_ts   Fine.
 */
function vv_date_range_label( $start_ts, $end_ts ) {
	$same_year = wp_date( 'Y', $start_ts ) === wp_date( 'Y', $end_ts );
	if ( $end_ts - $start_ts <= 31 * DAY_IN_SECONDS ) {
		if ( $same_year && wp_date( 'n', $start_ts ) === wp_date( 'n', $end_ts ) ) {
			return wp_date( 'j', $start_ts ) . '–' . wp_date( 'j F Y', $end_ts );
		}
		return wp_date( $same_year ? 'j F' : 'j F Y', $start_ts ) . ' – ' . wp_date( 'j F Y', $end_ts );
	}
	return $same_year
		? wp_date( 'M', $start_ts ) . '–' . wp_date( 'M Y', $end_ts )
		: wp_date( 'M Y', $start_ts ) . ' – ' . wp_date( 'M Y', $end_ts );
}

/**
 * Data a foglietto di calendario sulla foto della card: due righe brevi. Un giorno "13 / nov",
 * più giorni nello stesso mese "3–5 / ott", a cavallo di due mesi "28 set / 3 ott"; oltre un
 * mese, per i corsi, i mesi e l'anno: "ott–dic / 2026", "ott–giu / 26–27".
 *
 * @param array $dates Risultato di vv_event_dates().
 * @return array{alto:string,basso:string}|null Null senza le date dell'evento.
 */
function vv_date_chip( $dates ) {
	if ( empty( $dates['has_meta'] ) ) {
		return null;
	}
	list( $start, $end ) = array( $dates['start'], $dates['end'] );
	if ( wp_date( 'Y-m-d', $start ) === wp_date( 'Y-m-d', $end ) ) {
		return array( 'alto' => wp_date( 'j', $start ), 'basso' => wp_date( 'M', $start ) );
	}
	if ( $end - $start <= 31 * DAY_IN_SECONDS ) {
		return wp_date( 'Y-n', $start ) === wp_date( 'Y-n', $end )
			? array( 'alto' => wp_date( 'j', $start ) . '–' . wp_date( 'j', $end ), 'basso' => wp_date( 'M', $start ) )
			: array( 'alto' => wp_date( 'j M', $start ), 'basso' => wp_date( 'j M', $end ) );
	}
	$anni = wp_date( 'Y', $start ) === wp_date( 'Y', $end ) ? wp_date( 'Y', $start ) : wp_date( 'y', $start ) . '–' . wp_date( 'y', $end );
	return array( 'alto' => wp_date( 'M', $start ) . '–' . wp_date( 'M', $end ), 'basso' => $anni );
}

/**
 * Eventi di un periodo, dalle date dell'evento (vv_data, vv_data_fine), non da quella di pubblicazione.
 * Prima quelli in programma, dal più vicino; poi quelli già passati, dal più recente.
 *
 * @param string $slugs   Categorie separate da virgola (le figlie sono comprese).
 * @param string $periodo futuro (non ancora finiti) | dal-3-mesi (finiti da meno di 3 mesi, o non ancora finiti).
 * @param int    $number  Quanti al massimo (0 o meno: tutti).
 * @param array  $extra   Argomenti aggiuntivi per WP_Query (per esempio post__not_in).
 * @return WP_Post[]
 */
function vv_posts_by_period( $slugs, $periodo, $number, $extra = array() ) {
	$now  = time();
	$from = 'dal-3-mesi' === $periodo ? strtotime( '-3 months', $now ) : $now;
	$day  = wp_date( 'Y-m-d', $from );
	// Prefiltro nel database (date AAAA-MM-GG confrontabili come testo), poi il conto esatto su fine giornata.
	$posts = vv_posts_in( $slugs, -1, vv_add_meta_query( $extra, array(
		'relation' => 'OR',
		array( 'key' => 'vv_data_fine', 'value' => $day, 'compare' => '>=' ),
		array( 'key' => 'vv_data', 'value' => $day, 'compare' => '>=' ),
	) ) );

	$eventi = array();
	foreach ( $posts as $post ) {
		$dates = vv_event_dates( $post );
		if ( $dates['has_meta'] && $dates['end'] >= $from ) {
			$eventi[] = array( $post, $dates['start'], $dates['end'] );
		}
	}
	$posts = array_column( vv_ordina_eventi( $eventi, $now ), 0 );
	return (int) $number > 0 ? array_slice( $posts, 0, (int) $number ) : $posts;
}

/**
 * Ordine degli eventi in calendario:
 * 1. gli appuntamenti da non perdere: quelli che devono ancora cominciare e quelli brevi (fino a un
 *    mese) in corso, dal più vicino;
 * 2. corsi e rassegne lunghi già cominciati, dal più recente inizio: restano visibili, ma un evento
 *    di domani non finisce dietro un corso partito una settimana fa;
 * 3. quelli già finiti, dal più recente.
 *
 * @param array $eventi array( contenuto, inizio, fine ) con i timestamp di vv_event_dates().
 * @param int   $now    Adesso.
 * @return array Gli stessi elementi, ordinati.
 */
function vv_ordina_eventi( $eventi, $now ) {
	$prossimi = array();
	$in_corso = array();
	$passati  = array();
	foreach ( $eventi as $e ) {
		list( , $start, $end ) = $e;
		if ( $end < $now ) {
			$passati[] = $e;
		} elseif ( $start > $now || $end - $start <= 31 * DAY_IN_SECONDS ) {
			$prossimi[] = $e;
		} else {
			$in_corso[] = $e;
		}
	}
	// Un evento breve già cominciato conta da adesso: davanti a quelli che cominciano dopo.
	usort( $prossimi, static fn( $a, $b ) => array( max( $a[1], $now ), $a[2] ) <=> array( max( $b[1], $now ), $b[2] ) );
	usort( $in_corso, static fn( $a, $b ) => $b[1] <=> $a[1] );
	usort( $passati, static fn( $a, $b ) => $b[2] <=> $a[2] );
	return array_merge( $prossimi, $in_corso, $passati );
}

/**
 * Aggiunge una condizione sui meta agli argomenti di WP_Query, in AND con quella che c'è già
 * (per esempio "In evidenza" più le date dell'evento): con $extra + array( 'meta_query' => … )
 * una delle due andrebbe persa.
 *
 * @param array $args   Argomenti per WP_Query.
 * @param array $clause Condizione da aggiungere.
 * @return array
 */
function vv_add_meta_query( $args, $clause ) {
	$args['meta_query'] = empty( $args['meta_query'] ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		? $clause
		: array( 'relation' => 'AND', $args['meta_query'], $clause );
	return $args;
}

/**
 * Condizione "spuntato In evidenza" per vv_add_meta_query().
 */
function vv_featured_clause() {
	return array( array( 'key' => VV_FEATURED_KEY, 'value' => '1' ) );
}

/**
 * Colore d'accento della card a rotazione, mai due uguali vicini.
 *
 * @param int $index Posizione nella griglia.
 */
function vv_accent( $index ) {
	$accents = array( 'giallo', 'turchese', 'rosso' );
	return $accents[ $index % 3 ];
}

/**
 * Griglia di card.
 *
 * @param WP_Post[] $posts    Contenuti da mostrare.
 * @param string    $variante evento | persona | stampa | pagina.
 * @param array     $opts     colonne (int), titolo_livello (h2|h3), layout (griglia|carosello|agenda), autoplay (bool, solo
 *                            carosello), lotto (int, solo agenda: quante card alla volta, le altre con "Mostra altri").
 * @return string HTML.
 */
function vv_render_cards( $posts, $variante = 'evento', $opts = array() ) {
	if ( ! $posts ) {
		return '';
	}
	$opts     = wp_parse_args( $opts, array(
		'colonne'        => 'persona' === $variante ? 4 : 3,
		'titolo_livello' => 'h3',
		'layout'         => 'griglia',
		'autoplay'       => false,
		'lotto'          => 0,
	) );
	$carousel = 'carosello' === $opts['layout'];

	ob_start();
	if ( $carousel ) {
		// Frecce (e, con autoplay, scorrimento automatico e pausa) aggiunte da vv.js; senza JavaScript
		// il carosello scorre comunque col dito o la rotella.
		echo '<div class="vv-carousel" data-vv-carousel' . ( $opts['autoplay'] ? ' data-vv-autoplay' : '' ) . '>';
		printf( '<div class="vv-grid vv-grid--carosello vv-grid--%1$s" style="--vv-visibili:%2$d" tabindex="0" role="region" aria-label="%3$s">', esc_attr( $variante ), (int) $opts['colonne'], esc_attr__( 'Carosello: scorri per vedere altri contenuti', 'vivavoce' ) );
	} elseif ( 'agenda' === $opts['layout'] ) {
		// Righe compatte su telefono, griglia sopra (components.css). Escono tutte: vv.js nasconde quelle oltre
		// il primo lotto e aggiunge "Mostra altri"; senza JavaScript si vedono tutte.
		printf( '<div class="vv-grid vv-grid--%1$d vv-grid--%2$s vv-grid--agenda"%3$s>', (int) $opts['colonne'], esc_attr( $variante ), $opts['lotto'] > 0 ? ' data-vv-lotto="' . (int) $opts['lotto'] . '"' : '' );
	} else {
		printf( '<div class="vv-grid vv-grid--%1$d vv-grid--%2$s">', (int) $opts['colonne'], esc_attr( $variante ) );
	}
	foreach ( array_values( $posts ) as $i => $post ) {
		get_template_part( 'template-parts/card', null, array(
			'post'     => $post,
			'variante' => $variante,
			'accento'  => vv_accent( $i ),
			'hx'       => in_array( $opts['titolo_livello'], array( 'h2', 'h3', 'h4' ), true ) ? $opts['titolo_livello'] : 'h3',
		) );
	}
	echo $carousel ? '</div></div>' : '</div>';
	return ob_get_clean();
}

/**
 * Articoli di una o più categorie.
 *
 * @param string $slugs  Slug separati da virgola.
 * @param int    $number Quanti (-1 = tutti).
 * @param array  $extra  Argomenti aggiuntivi per WP_Query.
 * @return WP_Post[]
 */
function vv_posts_in( $slugs, $number = 6, $extra = array() ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $number,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $slugs ) {
		$args['category_name'] = str_replace( ' ', '', $slugs );
	}
	return get_posts( array_merge( $args, $extra ) );
}

/**
 * Categorie di un articolo, senza quella predefinita ("Nessuna categoria").
 *
 * @param int $post_id Articolo.
 * @return WP_Term[]
 */
function vv_post_categories( $post_id ) {
	return array_values( array_filter( get_the_category( $post_id ), static fn( $c ) => 'nessuna-categoria' !== $c->slug && 'uncategorized' !== $c->slug ) );
}
