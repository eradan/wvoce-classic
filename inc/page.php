<?php
/**
 * Pagine con il modello predefinito: testo + colonna laterale, come gli articoli.
 * Nella colonna "In questa sezione" (le pagine dello stesso ramo) e "Da non perdere".
 * Home, Pagina hub e Pagina elenco hanno modelli propri e restano a tutta larghezza.
 * Qui anche gli schemi comuni: due colonne (articoli e pagine) e pagina con un blocco finale
 * (modelli Pagina hub e Pagina elenco).
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sostituisce il contenuto della pagina di Sydney quando c'è la colonna laterale.
 */
function vv_swap_page_template() {
	if ( is_page() && vv_page_has_aside( get_queried_object_id() ) ) {
		remove_action( 'sydney_page_content', 'sydney_single_page_template' );
		add_action( 'sydney_page_content', 'vv_page_template' );
	}
}
add_action( 'wp', 'vv_swap_page_template' );

/**
 * La colonna resta fuori dalle pagine legali (privacy, cookie) e da quelle con le griglie di card del
 * tema, che sono larghe quanto la pagina e ci finirebbero sotto.
 *
 * @param int $page_id Pagina.
 */
function vv_page_has_aside( $page_id ) {
	$page = get_post( $page_id );
	return $page
		&& (int) get_option( 'wp_page_for_privacy_policy' ) !== $page->ID
		&& ! preg_match( '/privacy|cookie/', $page->post_name )
		&& ! preg_match( '/\[(vv_cards|vv_elenco|vv_sottopagine)\b/', $page->post_content );
}

/**
 * Due colonne: la pagina di Sydney (titolo, immagine, testo) e la colonna laterale.
 */
function vv_page_template() {
	vv_two_column_template(
		'vv-single vv-page',
		'page',
		'Altre pagine e appuntamenti',
		static fn( $page_id ) => vv_page_section_nav( $page_id ) . vv_aside_more()
	);
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
 * "In questa sezione": la pagina madre come titolo, sotto le sue sottopagine con quella corrente
 * evidenziata e, se ne ha, le sottopagine di quella corrente. Una pagina di primo livello
 * mostra le proprie sottopagine; senza né madre né figlie il riquadro non c'è.
 *
 * @param int $page_id Pagina corrente.
 * @return string HTML.
 */
function vv_page_section_nav( $page_id ) {
	$page      = get_post( $page_id );
	$parent_id = (int) $page->post_parent;
	$children  = vv_page_children( $page_id );
	$siblings  = $parent_id ? vv_page_children( $parent_id ) : array();

	// La pagina corrente compare sempre, anche se nascosta dalle card della madre.
	if ( $parent_id && ! in_array( $page_id, wp_list_pluck( $siblings, 'ID' ), true ) ) {
		$siblings[] = $page;
	}
	if ( ! $parent_id ) {
		$siblings = array( $page );
	}
	if ( count( $siblings ) < 2 && ! $children ) {
		return '';
	}

	$link = static function ( $p, $current = false ) {
		return '<a href="' . esc_url( get_permalink( $p ) ) . '"' . ( $current ? ' aria-current="page"' : '' ) . '>' . esc_html( wp_strip_all_tags( get_the_title( $p ) ) ) . '</a>';
	};

	$html  = '<nav class="vv-card vv-card--info vv-accent--turchese vv-section-nav" aria-label="In questa sezione"><div class="vv-card__body">';
	$html .= '<h2 class="vv-info__title">In questa sezione</h2>';
	$html .= '<p class="vv-section-nav__parent">' . ( $parent_id ? $link( get_post( $parent_id ) ) : esc_html( wp_strip_all_tags( get_the_title( $page ) ) ) ) . '</p>';
	$html .= '<ul class="vv-section-nav__list">';
	foreach ( $siblings as $sibling ) {
		$current = $sibling->ID === $page_id;
		// Una pagina di primo livello non ripete sé stessa: si vedono solo le sue sottopagine.
		if ( $current && ! $parent_id ) {
			foreach ( $children as $child ) {
				$html .= '<li>' . $link( $child ) . '</li>';
			}
			continue;
		}
		$html .= '<li' . ( $current ? ' class="is-current"' : '' ) . '>' . $link( $sibling, $current );
		if ( $current && $children ) {
			$html .= '<ul>';
			foreach ( $children as $child ) {
				$html .= '<li>' . $link( $child ) . '</li>';
			}
			$html .= '</ul>';
		}
		$html .= '</li>';
	}
	return $html . '</ul></div></nav>';
}

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
