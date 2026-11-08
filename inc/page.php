<?php
/**
 * Pagine con il modello predefinito: testo e colonna laterale con "In questa sezione" e "Da non
 * perdere". Le pagine legali e quelle con le griglie del tema restano a tutta larghezza.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_sostituisci_modello_pagina() {
	if ( is_page() && vv_pagina_con_colonna( get_queried_object_id() ) ) {
		remove_action( 'sydney_page_content', 'sydney_single_page_template' );
		add_action( 'sydney_page_content', 'vv_modello_pagina' );
	}
}
add_action( 'wp', 'vv_sostituisci_modello_pagina' );

/**
 * Le griglie di card del tema sono larghe quanto la pagina e finirebbero sotto la colonna.
 */
function vv_pagina_con_colonna( $pagina_id ) {
	$pagina = get_post( $pagina_id );
	return $pagina
		&& (int) get_option( 'wp_page_for_privacy_policy' ) !== $pagina->ID
		&& ! preg_match( '/privacy|cookie/', $pagina->post_name )
		&& ! preg_match( '/\[(vv_cards|vv_elenco|vv_sottopagine|vv_sostienici)\b/', $pagina->post_content );
}

function vv_modello_pagina() {
	vv_due_colonne(
		'vv-single vv-page',
		'page',
		'Altre pagine e appuntamenti',
		static fn( $pagina_id ) => vv_in_questa_sezione( $pagina_id ) . vv_da_non_perdere()
	);
}

/**
 * La pagina madre come titolo e le pagine del ramo, con sotto la corrente le sue figlie. Una pagina di
 * primo livello mostra solo le proprie figlie.
 */
function vv_in_questa_sezione( $pagina_id ) {
	$pagina   = get_post( $pagina_id );
	$madre_id = (int) $pagina->post_parent;
	$figlie   = vv_pagine_figlie( $pagina_id );
	$sorelle  = $madre_id ? vv_pagine_figlie( $madre_id ) : array();
	// La corrente compare anche se è nascosta fra le card della madre.
	if ( $madre_id && ! in_array( $pagina_id, wp_list_pluck( $sorelle, 'ID' ), true ) ) {
		$sorelle[] = $pagina;
	}
	if ( count( $sorelle ) < 2 && ! $figlie ) {
		return '';
	}

	$titolo      = static fn( $p ) => esc_html( wp_strip_all_tags( get_the_title( $p ) ) );
	$link        = static fn( $p, $corrente = false ) => '<a href="' . esc_url( get_permalink( $p ) ) . '"' . ( $corrente ? ' aria-current="page"' : '' ) . '>' . $titolo( $p ) . '</a>';
	$voci_figlie = implode( '', array_map( static fn( $p ) => '<li>' . $link( $p ) . '</li>', $figlie ) );

	if ( $madre_id ) {
		// Una madre privata o in bozza resta senza link: darebbe 404 ai visitatori.
		$intestazione = 'publish' === get_post_status( $madre_id ) ? $link( $madre_id ) : $titolo( $madre_id );
		$voci         = '';
		foreach ( $sorelle as $sorella ) {
			$corrente = $sorella->ID === $pagina_id;
			$voci    .= '<li' . ( $corrente ? ' class="is-current"' : '' ) . '>' . $link( $sorella, $corrente )
				. ( $corrente && $voci_figlie ? '<ul>' . $voci_figlie . '</ul>' : '' ) . '</li>';
		}
	} else {
		$intestazione = $titolo( $pagina );
		$voci         = $voci_figlie;
	}

	return '<nav class="vv-card vv-card--info vv-accent--turchese vv-section-nav" aria-label="In questa sezione"><div class="vv-card__body">'
		. '<h2 class="vv-info__title">In questa sezione</h2>'
		. '<p class="vv-section-nav__parent">' . $intestazione . '</p>'
		. '<ul class="vv-section-nav__list">' . $voci . '</ul></div></nav>';
}
