<?php
/**
 * Home: hero VivaVoce al posto dello slider di Sydney.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * L'hero VivaVoce è in uso nella richiesta corrente?
 */
function vv_hero_active() {
	return is_front_page() && vv_mod( 'vv_hero_enable' );
}

/**
 * L'hero usa l'illustrazione originale (la faccia che parla)? Allora la mostra a livelli separati
 * (assets/img/hero/) per animare testa, mano e trattini. Con un'altra immagine resta l'immagine sola.
 */
function vv_hero_animata() {
	return false !== strpos( basename( (string) wp_parse_url( vv_hero_image_url(), PHP_URL_PATH ) ), 'vivavoce-logo-pearl' );
}

/**
 * Spegne lo slider di Sydney nella home.
 *
 * @param string $value Tipo di header della home.
 */
function vv_front_header_type( $value ) {
	return vv_hero_active() ? 'nothing' : $value;
}
add_filter( 'theme_mod_front_header_type', 'vv_front_header_type' );

/**
 * @param string[] $classes Classi del body.
 */
function vv_hero_body_class( $classes ) {
	if ( vv_hero_active() ) {
		$classes[] = 'vv-has-hero';
	}
	return $classes;
}
add_filter( 'body_class', 'vv_hero_body_class' );

/**
 * Stampa l'hero.
 */
function vv_render_hero() {
	if ( vv_hero_active() ) {
		get_template_part( 'template-parts/home-hero' );
	}
}
add_action( 'sydney_after_hero', 'vv_render_hero' );

/**
 * Con l'hero il titolo della pagina home ("Benvenuto") non si stampa: l'h1 è quello dell'hero, e una
 * pagina deve averne uno solo. the_title() con un titolo vuoto non stampa neanche i tag.
 *
 * @param string $title Titolo.
 * @param int    $id    ID del contenuto.
 */
function vv_home_senza_titolo( $title, $id = 0 ) {
	if ( in_the_loop() && is_main_query() && (int) get_option( 'page_on_front' ) === (int) $id && vv_hero_active() ) {
		return '';
	}
	return $title;
}
add_filter( 'the_title', 'vv_home_senza_titolo', 10, 2 );
