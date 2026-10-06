<?php
/*
Template Name: Page builder ready
Template Post Type: post, projects, employees
*/

/**
 * Sostituisce il modello "Page builder ready" di Sydney (usato dalle pagine dei soci):
 * aggiunge breadcrumb e titolo, che il modello originale non stampa.
 *
 * @package VivaVoce
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
		while ( have_posts() ) :
			the_post();
			echo vv_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			the_title( '<header class="entry-header vv-builder-header"><h1 class="title-post entry-title">', '</h1></header>' );
			the_content();
		endwhile;
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
