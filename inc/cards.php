<?php
/**
 * Card e griglie: un solo componente per home, pagine hub, elenchi e archivi, con le query che le
 * riempiono. Le date degli eventi sono in inc/eventi.php.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Eventi di un periodo, dalle date dell'evento (vv_data, vv_data_fine), non da quella di pubblicazione.
 * Nell'ordine di vv_ordina_eventi(): in programma, in corso, conclusi.
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
 * Articoli spuntati "In evidenza", dal più recente: la riserva quando non ci sono eventi in calendario.
 *
 * @param int   $number Quanti.
 * @param array $extra  Argomenti aggiuntivi per WP_Query.
 * @return WP_Post[]
 */
function vv_in_evidenza( $number, $extra = array() ) {
	return vv_posts_in( '', $number, vv_add_meta_query( $extra, vv_featured_clause() ) );
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
 * @param string    $variante evento | persona | stampa | citazione | pagina.
 * @param array     $opts     colonne (int), titolo_livello (h2|h3|h4), layout (griglia|carosello|agenda), autoplay (bool, solo
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
	$livello  = in_array( $opts['titolo_livello'], array( 'h2', 'h3', 'h4' ), true ) ? $opts['titolo_livello'] : 'h3';
	// Le miniature di tutte le card con una query sola, invece di una per card.
	_prime_post_caches( array_filter( array_map( 'get_post_thumbnail_id', $posts ) ) );

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
			'post'           => $post,
			'variante'       => $variante,
			'accento'        => vv_accent( $i ),
			'titolo_livello' => $livello,
		) );
	}
	echo $carousel ? '</div></div>' : '</div>';
	return ob_get_clean();
}

/**
 * Articoli di una o più categorie.
 *
 * @param string $slugs  Slug separati da virgola.
 * @param int    $number Quanti (0 o meno: tutti).
 * @param array  $extra  Argomenti aggiuntivi per WP_Query.
 * @return WP_Post[]
 */
function vv_posts_in( $slugs, $number = 6, $extra = array() ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		// get_posts() con 0 ne darebbe 5.
		'posts_per_page'      => (int) $number > 0 ? (int) $number : -1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $slugs ) {
		$args['category_name'] = str_replace( ' ', '', $slugs );
	}
	return get_posts( array_merge( $args, $extra ) );
}

/**
 * Categorie di un articolo, senza quella predefinita (Impostazioni → Scrittura, "Nessuna categoria").
 *
 * @param int $post_id Articolo.
 * @return WP_Term[]
 */
function vv_post_categories( $post_id ) {
	$predefinita = (int) get_option( 'default_category' );
	$categorie   = array_filter( get_the_category( $post_id ), static fn( $c ) => $c->term_id !== $predefinita );
	return array_values( $categorie );
}
