<?php
/**
 * Articolo singolo: testo e scheda "In breve" a lato con correlati e "Da non perdere", link
 * all'articolo precedente e successivo, gli altri soci fondatori in fondo alle pagine dei soci.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_sostituisci_modello_articolo() {
	if ( is_singular( 'post' ) ) {
		remove_action( 'sydney_single_content', 'sydney_single_template' );
		add_action( 'sydney_single_content', 'vv_modello_articolo' );
	}
}
add_action( 'wp', 'vv_sostituisci_modello_articolo' );

function vv_modello_articolo() {
	vv_due_colonne( 'vv-single', 'single', 'Informazioni', 'vv_colonna_articolo' );
}

function vv_colonna_articolo( $articolo_id ) {
	$date      = vv_date_articolo( $articolo_id );
	$luogo     = get_post_meta( $articolo_id, 'vv_luogo', true );
	$categorie = vv_categorie_articolo( $articolo_id );

	$html  = '<div class="vv-card vv-card--info vv-accent--giallo"><div class="vv-card__body">';
	$html .= '<h2 class="vv-info__title">In breve</h2><dl class="vv-info">';
	$html .= '<dt>' . ( $date['con_date'] ? 'Quando' : 'Pubblicato' ) . '</dt><dd><time datetime="' . esc_attr( wp_date( 'Y-m-d', $date['inizio'] ) ) . '">' . esc_html( $date['etichetta'] ) . '</time></dd>';
	if ( $luogo ) {
		$html .= '<dt>Dove</dt><dd>' . esc_html( $luogo ) . '</dd>';
	}
	if ( $categorie ) {
		$html .= '<dt>' . ( count( $categorie ) > 1 ? 'Categorie' : 'Categoria' ) . '</dt><dd class="vv-info__chips">';
		foreach ( $categorie as $categoria ) {
			$pagina = vv_pagina_del_termine( $categoria );
			$html  .= '<a class="vv-chip" href="' . esc_url( $pagina ? get_permalink( $pagina ) : get_category_link( $categoria ) ) . '">' . esc_html( $categoria->name ) . '</a>';
		}
		$html .= '</dd>';
	}
	$html .= '</dl></div></div>';

	return $html . vv_correlati( $articolo_id ) . vv_da_non_perdere( $articolo_id );
}

/**
 * I tag di un percorso (Il gioco del teatro, Corre Voce…), senza quelli generici su pubblico e
 * linguaggio, che legherebbero fra loro mezzo sito.
 *
 * @return int[]
 */
function vv_tag_di_percorso( $articolo_id ) {
	$generici = array( 'bambini', 'ragazzi', 'adulti', 'teatro', 'lettura-ad-alta-voce', 'narrazione', 'scrittura', 'musica', 'multimedia' );
	$tag      = get_the_tags( $articolo_id );
	if ( ! $tag ) {
		return array();
	}
	$di_percorso = array_filter( $tag, static fn( $t ) => ! in_array( $t->slug, $generici, true ) );
	return array_values( wp_list_pluck( $di_percorso, 'term_id' ) );
}

/**
 * "Altre edizioni" e "Ne hanno parlato" dello stesso percorso; su un ritaglio, "Di cosa parla".
 */
function vv_correlati( $articolo_id ) {
	$tag = vv_tag_di_percorso( $articolo_id );
	if ( ! $tag ) {
		return '';
	}
	$stampa   = get_category_by_slug( 'rassegna-stampa' );
	$percorso = array(
		'tag__in'      => $tag,
		'post__not_in' => array( $articolo_id ),
	);
	$edizioni = vv_articoli( '', 4, $percorso + ( $stampa ? array( 'category__not_in' => array( $stampa->term_id ) ) : array() ) );
	if ( $stampa && has_category( $stampa->term_id, $articolo_id ) ) {
		return vv_elenco_laterale( 'Di cosa parla', $edizioni );
	}
	$ritagli = $stampa ? vv_articoli( '', 4, $percorso + array( 'cat' => $stampa->term_id ) ) : array();
	return vv_elenco_laterale( 'Altre edizioni', $edizioni ) . vv_elenco_laterale( 'Ne hanno parlato', $ritagli );
}

// Funzione "pluggable" di Sydney: il child si carica prima e la sostituisce.
if ( ! function_exists( 'sydney_post_navigation' ) ) :
	function sydney_post_navigation() {
		if ( ! apply_filters( 'sydney_single_post_nav_enable', true ) || ! get_theme_mod( 'single_post_show_post_nav', 1 ) ) {
			return;
		}
		$precedente = is_attachment() ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
		$successivo = get_adjacent_post( false, '', false );
		if ( ! $successivo && ! $precedente ) {
			return;
		}

		echo '<nav class="vv-post-nav" aria-label="Altri articoli">';
		foreach ( array( 'prev' => $precedente, 'next' => $successivo ) as $verso => $articolo ) {
			if ( ! $articolo ) {
				echo '<span class="vv-post-nav__empty"></span>';
				continue;
			}
			printf(
				'<a class="vv-post-nav__link vv-post-nav__link--%1$s" href="%2$s" rel="%1$s"><span class="vv-post-nav__label">%3$s</span><span class="vv-post-nav__title">%4$s</span></a>',
				esc_attr( $verso ),
				esc_url( get_permalink( $articolo ) ),
				'prev' === $verso ? '‹ Articolo precedente' : 'Articolo successivo ›',
				esc_html( wp_strip_all_tags( get_the_title( $articolo ) ) )
			);
		}
		echo '</nav>';
	}
endif;

function vv_altri_soci( $contenuto ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || ! has_category( 'soci' ) ) {
		return $contenuto;
	}
	$altri = vv_articoli( 'soci', 12, array(
		'post__not_in' => array( get_the_ID() ),
		'orderby'      => 'title',
		'order'        => 'ASC',
	) );
	if ( ! $altri ) {
		return $contenuto;
	}
	return $contenuto . '<section class="vv-founders"><h2 class="vv-section-title">Gli altri soci fondatori</h2>'
		. vv_griglia_card( $altri, 'persona', array( 'colonne' => 3 ) ) . '</section>';
}
add_filter( 'the_content', 'vv_altri_soci', 30 );
