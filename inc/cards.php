<?php
/**
 * Griglie di card e le query che le riempiono.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dalle date dell'evento, non da quella di pubblicazione, nell'ordine di vv_ordina_eventi().
 *
 * @param string $periodo futuro (non ancora finiti) | dal-3-mesi (anche finiti da meno di 3 mesi) | tutti.
 * @param int    $numero  0 o meno: tutti.
 * @return WP_Post[]
 */
function vv_eventi_del_periodo( $categorie, $periodo, $numero, $extra = array() ) {
	$adesso = time();
	$dal    = match ( $periodo ) {
		'futuro'     => $adesso,
		'dal-3-mesi' => strtotime( '-3 months', $adesso ),
		default      => 0,
	};
	$giorno = wp_date( 'Y-m-d', $dal );
	// Prima il filtro nel database sulle date AAAA-MM-GG, confrontabili come testo; poi quello esatto.
	$candidati = vv_articoli( $categorie, -1, array_merge( $extra, array(
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array( 'key' => 'vv_data_fine', 'value' => $giorno, 'compare' => '>=' ),
			array( 'key' => 'vv_data', 'value' => $giorno, 'compare' => '>=' ),
		),
	) ) );

	$eventi = array();
	foreach ( $candidati as $articolo ) {
		$date = vv_date_articolo( $articolo );
		if ( $date['con_date'] && $date['fine'] >= $dal ) {
			$eventi[] = array( $articolo, $date['inizio'], $date['fine'] );
		}
	}
	$ordinati = array_column( vv_ordina_eventi( $eventi, $adesso ), 0 );
	return (int) $numero > 0 ? array_slice( $ordinati, 0, (int) $numero ) : $ordinati;
}

/**
 * A rotazione, mai due uguali vicini.
 */
function vv_accento( $posizione ) {
	return array( 'giallo', 'turchese', 'rosso' )[ $posizione % 3 ];
}

/**
 * Carosello e agenda li completa vv.js (frecce, "Mostra altri"); senza JavaScript il carosello
 * scorre col dito e l'agenda mostra tutte le card.
 *
 * @param string $variante evento | persona | stampa | citazione | pagina.
 * @param array  $opzioni  colonne, titolo_livello (h2|h3|h4), layout (griglia|carosello|agenda),
 *                         lotto (agenda: quante card alla volta).
 */
function vv_griglia_card( $contenuti, $variante = 'evento', $opzioni = array() ) {
	if ( ! $contenuti ) {
		return '';
	}
	$opzioni = wp_parse_args( $opzioni, array(
		'colonne'        => 'persona' === $variante ? 4 : 3,
		'titolo_livello' => 'h3',
		'layout'         => 'griglia',
		'lotto'          => 0,
	) );
	$colonne = (int) $opzioni['colonne'];
	$livello = in_array( $opzioni['titolo_livello'], array( 'h2', 'h3', 'h4' ), true ) ? $opzioni['titolo_livello'] : 'h3';

	$apertura = match ( $opzioni['layout'] ) {
		'carosello' => sprintf(
			'<div class="vv-carousel" data-vv-carousel><div class="vv-grid vv-grid--carosello vv-grid--%1$s" style="--vv-visibili:%2$d" tabindex="0" role="region" aria-label="Carosello: scorri per vedere altri contenuti">',
			esc_attr( $variante ),
			$colonne
		),
		'agenda'    => sprintf(
			'<div class="vv-grid vv-grid--%1$d vv-grid--%2$s vv-grid--agenda"%3$s>',
			$colonne,
			esc_attr( $variante ),
			$opzioni['lotto'] > 0 ? ' data-vv-lotto="' . (int) $opzioni['lotto'] . '"' : ''
		),
		default     => sprintf( '<div class="vv-grid vv-grid--%1$d vv-grid--%2$s">', $colonne, esc_attr( $variante ) ),
	};

	_prime_post_caches( array_filter( array_map( 'get_post_thumbnail_id', $contenuti ) ) ); // le miniature con una query sola
	ob_start();
	echo $apertura; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	foreach ( array_values( $contenuti ) as $posizione => $contenuto ) {
		get_template_part( 'template-parts/card', null, array(
			'post'           => $contenuto,
			'variante'       => $variante,
			'accento'        => vv_accento( $posizione ),
			'titolo_livello' => $livello,
		) );
	}
	echo 'carosello' === $opzioni['layout'] ? '</div></div>' : '</div>';
	return ob_get_clean();
}

/**
 * @param string $categorie Slug separati da virgola; vuoto: tutte.
 * @param int    $numero    0 o meno: tutti.
 * @return WP_Post[]
 */
function vv_articoli( $categorie, $numero = 6, $extra = array() ) {
	$argomenti = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $numero > 0 ? (int) $numero : -1, // con 0 get_posts() ne darebbe 5
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $categorie ) {
		$argomenti['category_name'] = str_replace( ' ', '', $categorie );
	}
	return get_posts( array_merge( $argomenti, $extra ) );
}

/**
 * Senza la categoria predefinita ("Nessuna categoria").
 *
 * @return WP_Term[]
 */
function vv_categorie_articolo( $articolo_id ) {
	$predefinita = (int) get_option( 'default_category' );
	return array_values( array_filter( get_the_category( $articolo_id ), static fn( $c ) => $c->term_id !== $predefinita ) );
}
