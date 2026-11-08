<?php
/**
 * Pagine con il modello predefinito: testo + colonna laterale, come gli articoli.
 * Nella colonna "In questa sezione" (le pagine dello stesso ramo) e "Da non perdere" (inc/layout.php).
 * Home, Pagina hub e Pagina elenco hanno modelli propri e restano a tutta larghezza.
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
		&& ! preg_match( '/\[(vv_cards|vv_elenco|vv_sottopagine|vv_sostienici)\b/', $page->post_content );
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

	$titolo = static fn( $p ) => esc_html( wp_strip_all_tags( get_the_title( $p ) ) );
	$link   = static function ( $p, $current = false ) use ( $titolo ) {
		return '<a href="' . esc_url( get_permalink( $p ) ) . '"' . ( $current ? ' aria-current="page"' : '' ) . '>' . $titolo( $p ) . '</a>';
	};
	$figlie = '';
	foreach ( $children as $child ) {
		$figlie .= '<li>' . $link( $child ) . '</li>';
	}
	// Una madre non pubblicata (privata, bozza) resta come titolo: il link darebbe 404 ai visitatori.
	$madre = $parent_id && 'publish' === get_post_status( $parent_id ) ? $link( $parent_id ) : $titolo( $parent_id ? $parent_id : $page );

	$html  = '<nav class="vv-card vv-card--info vv-accent--turchese vv-section-nav" aria-label="In questa sezione"><div class="vv-card__body">';
	$html .= '<h2 class="vv-info__title">In questa sezione</h2>';
	$html .= '<p class="vv-section-nav__parent">' . $madre . '</p>';
	$html .= '<ul class="vv-section-nav__list">';
	foreach ( $siblings as $sibling ) {
		$current = $sibling->ID === $page_id;
		// Una pagina di primo livello non ripete sé stessa: si vedono solo le sue sottopagine.
		if ( $current && ! $parent_id ) {
			$html .= $figlie;
			continue;
		}
		$html .= '<li' . ( $current ? ' class="is-current"' : '' ) . '>' . $link( $sibling, $current );
		if ( $current && $figlie ) {
			$html .= '<ul>' . $figlie . '</ul>';
		}
		$html .= '</li>';
	}
	return $html . '</ul></div></nav>';
}
