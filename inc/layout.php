<?php
/**
 * Scheletri comuni a articoli e pagine: due colonne con la colonna laterale (articoli e pagine con
 * il modello predefinito), pagina di Sydney con un blocco finale (modelli Pagina hub e Pagina elenco),
 * i blocchi della colonna laterale che servono a entrambi e le sottopagine di una pagina.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Due colonne, per articoli e pagine: il contenuto di Sydney (titolo, immagine, testo) e la colonna
 * laterale; i commenti sotto la griglia, così su mobile la colonna arriva subito dopo il testo.
 *
 * @param string   $classes Classi aggiuntive della content-area.
 * @param string   $part    Parte del contenuto di Sydney (single, page).
 * @param string   $label   Nome accessibile della colonna laterale.
 * @param callable $aside   Restituisce l'HTML della colonna, dato l'ID del contenuto.
 */
function vv_two_column_template( $classes, $part, $label, callable $aside ) {
	?>
	<div id="primary" class="content-area col-md-12 no-sidebar <?php echo esc_attr( $classes ); ?>">
		<main id="main" class="post-wrap" role="main">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<div class="vv-single__grid">
					<div class="vv-single__main">
						<?php get_template_part( 'content', $part ); ?>
					</div>
					<aside class="vv-single__aside" aria-label="<?php echo esc_attr( $label ); ?>">
						<?php echo $aside( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</aside>
				</div>
				<?php
				if ( comments_open() || get_comments_number() ) {
					echo '<div class="vv-single__comments">';
					comments_template();
					echo '</div>';
				}
				?>
			<?php endwhile; ?>
		</main>
	</div>
	<?php
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

/**
 * Sottopagine pubblicate di una pagina, nell'ordine dell'editor; quelle con "Non mostrare fra le
 * card della pagina madre" restano fuori anche qui.
 *
 * @param int $parent_id Pagina madre.
 * @return WP_Post[]
 */
function vv_page_children( $parent_id ) {
	$pages = get_pages( array(
		'parent'      => $parent_id,
		'sort_column' => 'menu_order,post_title',
		'post_status' => 'publish',
	) );
	return array_values( array_filter( $pages, static fn( $p ) => ! get_post_meta( $p->ID, 'vv_nascondi', true ) ) );
}

/**
 * "Da non perdere": i prossimi 3 eventi; se non ce ne sono, gli "In evidenza", poi i più recenti.
 * Nella colonna laterale degli articoli e delle pagine.
 *
 * @param int $exclude_id Articolo da escludere (0 nelle pagine).
 * @return string HTML.
 */
function vv_aside_more( $exclude_id = 0 ) {
	$extra = $exclude_id ? array( 'post__not_in' => array( $exclude_id ) ) : array();
	$more  = vv_posts_by_period( 'eventi', 'futuro', 3, $extra );
	if ( ! $more ) {
		$more = vv_posts_in( '', 3, vv_add_meta_query( $extra, vv_featured_clause() ) );
	}
	if ( ! $more ) {
		$more = vv_posts_in( '', 3, $extra );
	}
	return vv_aside_list( 'Da non perdere', $more );
}

/**
 * Elenco di link con miniatura nella colonna laterale.
 *
 * @param string    $title Titolo del blocco.
 * @param WP_Post[] $posts Articoli.
 * @return string HTML (vuoto senza articoli).
 */
function vv_aside_list( $title, $posts ) {
	if ( ! $posts ) {
		return '';
	}
	$html = '<div class="vv-aside-more"><h2 class="vv-info__title">' . esc_html( $title ) . '</h2><ul class="vv-aside-list">';
	foreach ( $posts as $p ) {
		// Senza immagine un riquadro della stessa misura, così i titoli restano in colonna.
		$thumb = get_the_post_thumbnail( $p, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) );
		$thumb = $thumb ? $thumb : '<span class="vv-aside-list__vuoto" aria-hidden="true"></span>';
		$html .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . $thumb . '<span>' . esc_html( wp_strip_all_tags( get_the_title( $p ) ) ) . '</span></a></li>';
	}
	return $html . '</ul></div>';
}
