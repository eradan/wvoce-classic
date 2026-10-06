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

	$has_meta = (bool) $start;
	$start_ts = $start ? ( new DateTimeImmutable( $start, $tz ) )->getTimestamp() : get_post_timestamp( $post );
	// Un evento resta "in programma" fino alla fine del suo ultimo giorno.
	$last_day = $end ? $end : $start;
	$end_ts   = $last_day ? ( new DateTimeImmutable( $last_day . ' 23:59:59', $tz ) )->getTimestamp() : $start_ts;

	if ( ! $has_meta ) {
		$label = wp_date( 'j F Y', $start_ts );
	} elseif ( ! $end || $end === $start ) {
		$label = wp_date( 'j F Y', $start_ts );
	} elseif ( wp_date( 'Y', $start_ts ) === wp_date( 'Y', $end_ts ) ) {
		$label = wp_date( 'F', $start_ts ) === wp_date( 'F', $end_ts )
			? wp_date( 'F Y', $start_ts )
			: wp_date( 'M', $start_ts ) . '–' . wp_date( 'M Y', $end_ts );
	} else {
		$label = wp_date( 'M Y', $start_ts ) . ' – ' . wp_date( 'M Y', $end_ts );
	}

	return array(
		'start'    => $start_ts,
		'end'      => $end_ts,
		'label'    => $label,
		'has_meta' => $has_meta,
	);
}

/**
 * Eventi di un periodo, dalle date dell'evento (vv_data, vv_data_fine), non da quella di pubblicazione.
 * Prima quelli in programma, dal più vicino; poi quelli già passati, dal più recente.
 *
 * @param string $slugs   Categorie separate da virgola (le figlie sono comprese).
 * @param string $periodo futuro (non ancora finiti) | dal-3-mesi (finiti da meno di 3 mesi, o non ancora finiti).
 * @param int    $number  Quanti al massimo.
 * @param array  $extra   Argomenti aggiuntivi per WP_Query (per esempio post__not_in).
 * @return WP_Post[]
 */
function vv_posts_by_period( $slugs, $periodo, $number, $extra = array() ) {
	$now  = time();
	$from = 'dal-3-mesi' === $periodo ? strtotime( '-3 months', $now ) : $now;
	$day  = wp_date( 'Y-m-d', $from );
	// Prefiltro nel database (date AAAA-MM-GG confrontabili come testo), poi il conto esatto su fine giornata.
	$posts = vv_posts_in( $slugs, -1, $extra + array(
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array( 'key' => 'vv_data_fine', 'value' => $day, 'compare' => '>=' ),
			array( 'key' => 'vv_data', 'value' => $day, 'compare' => '>=' ),
		),
	) );

	$next = array();
	$past = array();
	foreach ( $posts as $post ) {
		$dates = vv_event_dates( $post );
		if ( ! $dates['has_meta'] || $dates['end'] < $from ) {
			continue;
		}
		if ( $dates['end'] >= $now ) {
			$next[] = array( $post, $dates['start'] );
		} else {
			$past[] = array( $post, $dates['end'] );
		}
	}
	usort( $next, static fn( $a, $b ) => $a[1] <=> $b[1] );
	usort( $past, static fn( $a, $b ) => $b[1] <=> $a[1] );
	return array_slice( array_column( array_merge( $next, $past ), 0 ), 0, max( 1, (int) $number ) );
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
 * @param array     $opts     colonne (int), titolo_livello (h2|h3), layout (griglia|carosello), autoplay (bool, solo carosello).
 * @return string HTML.
 */
function vv_render_cards( $posts, $variante = 'evento', $opts = array() ) {
	if ( ! $posts ) {
		return '';
	}
	$opts = wp_parse_args( $opts, array(
		'colonne'        => 'persona' === $variante ? 4 : 3,
		'titolo_livello' => 'h3',
		'layout'         => 'griglia',
		'autoplay'       => false,
	) );
	$carousel = 'carosello' === $opts['layout'];

	ob_start();
	if ( $carousel ) {
		// Frecce (e, con autoplay, scorrimento automatico e pausa) aggiunte da vv.js; senza JavaScript
		// il carosello scorre comunque col dito o la rotella.
		echo '<div class="vv-carousel" data-vv-carousel' . ( $opts['autoplay'] ? ' data-vv-autoplay' : '' ) . '>';
		printf( '<div class="vv-grid vv-grid--carosello vv-grid--%1$s" style="--vv-visibili:%2$d" tabindex="0" role="region" aria-label="%3$s">', esc_attr( $variante ), (int) $opts['colonne'], esc_attr__( 'Carosello: scorri per vedere altri contenuti', 'vivavoce' ) );
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
 * Corpo comune dei modelli di pagina del child: identico alla pagina di Sydney,
 * più un blocco finale.
 *
 * @param callable $after Restituisce l'HTML da aggiungere dopo il contenuto.
 */
function vv_page_with_extra( callable $after ) {
	$classes = apply_filters( 'sydney_content_area_class', 'col-md-9' );
	?>
	<div id="primary" class="content-area <?php echo esc_attr( sydney_sidebar_position() . ' ' . $classes ); ?>">
		<main id="main" class="post-wrap" role="main">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'content', 'page' );
				echo '<div class="vv-page-extra">' . $after( get_the_ID() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endwhile;
			?>
		</main>
	</div>
	<?php
}
