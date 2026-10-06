<?php
/**
 * Archivi (categorie, tag, blog) con le card del child al posto dei layout di Sydney.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sostituisce il contenuto degli archivi di Sydney.
 */
function vv_swap_archive_template() {
	remove_action( 'sydney_archive_content', 'sydney_archive_template' );
	add_action( 'sydney_archive_content', 'vv_archive_template' );
}
add_action( 'wp', 'vv_swap_archive_template' );

/**
 * 12 articoli per pagina negli archivi a card, invece dei 10 di Impostazioni → Lettura: la griglia
 * ha 1, 2 o 3 colonne secondo lo schermo e con 12 le righe sono sempre piene (con 10, a 3 colonne,
 * l'ultima riga aveva una card sola).
 *
 * @param WP_Query $query Query in corso.
 */
function vv_archive_posts_per_page( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_date() || $query->is_author() ) {
		$query->set( 'posts_per_page', 12 );
	}
}
add_action( 'pre_get_posts', 'vv_archive_posts_per_page' );

/**
 * Archivio a griglia di card; la Rassegna stampa usa la variante "stampa".
 */
function vv_archive_template() {
	global $wp_query;
	$variante = is_category( 'rassegna-stampa' ) ? 'stampa' : 'evento';
	?>
	<div id="primary" class="content-area <?php echo esc_attr( apply_filters( 'sydney_content_area_class', 'col-md-12' ) ); ?>">
		<main id="main" class="post-wrap" role="main">
			<?php if ( have_posts() ) : ?>
				<?php if ( ! is_home() ) : ?>
					<header class="page-header archive-header">
						<?php
						the_archive_title( '<h1 class="archive-title">', '</h1>' );
						the_archive_description( '<div class="taxonomy-description">', '</div>' );
						?>
					</header>
				<?php endif; ?>
				<?php echo vv_render_cards( $wp_query->posts, $variante, array( 'titolo_livello' => 'h2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php
				the_posts_pagination( array(
					'mid_size'           => 1,
					'prev_text'          => '‹ <span class="screen-reader-text">Precedenti</span>',
					'next_text'          => '<span class="screen-reader-text">Successivi</span> ›',
					'before_page_number' => '<span class="screen-reader-text">Pagina </span>',
				) );
				?>
			<?php else : ?>
				<?php get_template_part( 'content', 'none' ); ?>
			<?php endif; ?>
		</main>
	</div>
	<?php
}
