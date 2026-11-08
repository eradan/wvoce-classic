<?php
/**
 * Hero della home al posto dello slider di Sydney.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_immagine_hero() {
	return (string) vv_mod( 'vv_hero_image' );
}

/**
 * Cercata una volta per pagina: la vogliono l'hero e l'anteprima social. 0 se non è nella libreria.
 */
function vv_id_immagine_hero() {
	static $id = null;

	$id ??= vv_immagine_hero() ? attachment_url_to_postid( vv_immagine_hero() ) : 0;
	return $id;
}

/**
 * Solo l'illustrazione originale ha i livelli separati in assets/img/hero/.
 */
function vv_hero_animata() {
	return str_contains( basename( (string) wp_parse_url( vv_immagine_hero(), PHP_URL_PATH ) ), 'vivavoce-logo-pearl' );
}

function vv_niente_slider_home( $tipo ) {
	return is_front_page() ? 'nothing' : $tipo;
}
add_filter( 'theme_mod_front_header_type', 'vv_niente_slider_home' );

function vv_classe_hero( $classi ) {
	if ( is_front_page() ) {
		$classi[] = 'vv-has-hero';
	}
	return $classi;
}
add_filter( 'body_class', 'vv_classe_hero' );

function vv_stampa_hero() {
	if ( is_front_page() ) {
		get_template_part( 'template-parts/home-hero' );
	}
}
add_action( 'sydney_after_hero', 'vv_stampa_hero' );

/**
 * L'h1 della home è quello dell'hero; con un titolo vuoto the_title() non stampa neanche i tag.
 */
function vv_home_senza_titolo( $titolo, $id = 0 ) {
	if ( in_the_loop() && is_main_query() && (int) get_option( 'page_on_front' ) === (int) $id ) {
		return '';
	}
	return $titolo;
}
add_filter( 'the_title', 'vv_home_senza_titolo', 10, 2 );
