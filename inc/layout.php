<?php
/**
 * Scheletri delle pagine (due colonne, blocco finale), blocchi della colonna laterale, sottopagine.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Il contenuto di Sydney e la colonna laterale; i commenti sotto la griglia, così su telefono la
 * colonna arriva subito dopo il testo.
 *
 * @param string   $parte   Parte del contenuto di Sydney: single o page.
 * @param callable $colonna Riceve l'ID del contenuto e restituisce l'HTML della colonna.
 */
function vv_due_colonne( $classi, $parte, $etichetta, callable $colonna ) {
	?>
	<div id="primary" class="content-area col-md-12 no-sidebar <?php echo esc_attr( $classi ); ?>">
		<main id="main" class="post-wrap" role="main">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<div class="vv-single__grid">
					<div class="vv-single__main">
						<?php get_template_part( 'content', $parte ); ?>
					</div>
					<aside class="vv-single__aside" aria-label="<?php echo esc_attr( $etichetta ); ?>">
						<?php echo $colonna( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
 * La pagina di Sydney più un blocco finale: modelli Pagina hub e Pagina elenco.
 *
 * @param callable $blocco Riceve l'ID della pagina e restituisce l'HTML del blocco.
 */
function vv_pagina_con_blocco_finale( callable $blocco ) {
	$classi = sydney_sidebar_position() . ' ' . apply_filters( 'sydney_content_area_class', 'col-md-9' );
	?>
	<div id="primary" class="content-area <?php echo esc_attr( $classi ); ?>">
		<main id="main" class="post-wrap" role="main">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'content', 'page' );
				echo '<div class="vv-page-extra">' . $blocco( get_the_ID() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endwhile;
			?>
		</main>
	</div>
	<?php
}

/**
 * Pubblicate, nell'ordine dell'editor, senza quelle con "Non mostrare fra le card della pagina madre".
 *
 * @return WP_Post[]
 */
function vv_pagine_figlie( $madre_id ) {
	$pagine = get_pages( array(
		'parent'      => $madre_id,
		'sort_column' => 'menu_order,post_title',
		'post_status' => 'publish',
	) );
	update_postmeta_cache( wp_list_pluck( $pagine, 'ID' ) ); // get_pages() non carica i campi
	return array_values( array_filter( $pagine, static fn( $p ) => ! get_post_meta( $p->ID, 'vv_nascondi', true ) ) );
}

/**
 * I prossimi 3 eventi; senza, gli "In evidenza"; senza, i più recenti.
 */
function vv_da_non_perdere( $escluso_id = 0 ) {
	$extra    = $escluso_id ? array( 'post__not_in' => array( $escluso_id ) ) : array();
	$articoli = vv_eventi_del_periodo( 'eventi', 'futuro', 3, $extra );
	if ( ! $articoli ) {
		$articoli = vv_in_evidenza( 3, $extra );
	}
	if ( ! $articoli ) {
		$articoli = vv_articoli( '', 3, $extra );
	}
	return vv_elenco_laterale( 'Da non perdere', $articoli );
}

function vv_elenco_laterale( $titolo, $articoli ) {
	if ( ! $articoli ) {
		return '';
	}
	_prime_post_caches( array_filter( array_map( 'get_post_thumbnail_id', $articoli ) ) );
	$html = '<div class="vv-aside-more"><h2 class="vv-info__title">' . esc_html( $titolo ) . '</h2><ul class="vv-aside-list">';
	foreach ( $articoli as $articolo ) {
		// Senza immagine un riquadro della stessa misura, così i titoli restano in colonna.
		$miniatura = get_the_post_thumbnail( $articolo, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) );
		$miniatura = $miniatura ? $miniatura : '<span class="vv-aside-list__vuoto" aria-hidden="true"></span>';
		$html     .= '<li><a href="' . esc_url( get_permalink( $articolo ) ) . '">' . $miniatura . '<span>' . esc_html( wp_strip_all_tags( get_the_title( $articolo ) ) ) . '</span></a></li>';
	}
	return $html . '</ul></div>';
}

function vv_card_sottopagine( $madre_id ) {
	$figlie = vv_pagine_figlie( $madre_id );
	return vv_griglia_card( $figlie, 'pagina', array( 'colonne' => count( $figlie ) >= 3 ? 3 : 2 ) );
}

/**
 * Le card in fondo alla Pagina hub, a meno che il testo non le metta altrove con [vv_sottopagine].
 */
function vv_card_hub( $pagina_id ) {
	return has_shortcode( (string) get_post_field( 'post_content', $pagina_id ), 'vv_sottopagine' ) ? '' : vv_card_sottopagine( $pagina_id );
}
