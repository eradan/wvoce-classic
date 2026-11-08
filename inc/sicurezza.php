<?php
/**
 * Meno appigli per l'accesso a forza bruta: nomi utente fuori da sitemap e API pubblica, XML-RPC
 * spento, versione di WordPress nascosta.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_sitemap_senza_utenti( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'vv_sitemap_senza_utenti', 10, 2 );

/**
 * L'editor usa l'elenco degli utenti, i visitatori no.
 */
function vv_api_senza_utenti( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		foreach ( array_keys( $endpoints ) as $rotta ) {
			if ( str_starts_with( $rotta, '/wp/v2/users' ) ) {
				unset( $endpoints[ $rotta ] );
			}
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'vv_api_senza_utenti' );

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
remove_action( 'wp_head', 'rsd_link' );

function vv_senza_pingback( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'vv_senza_pingback' );

/**
 * Il <link rel="pingback"> di Sydney punta a xmlrpc.php, che è spento.
 */
function vv_senza_pingback_url( $url, $show ) {
	return 'pingback_url' === $show ? '' : $url;
}
add_filter( 'bloginfo_url', 'vv_senza_pingback_url', 10, 2 );

add_filter( 'the_generator', '__return_empty_string' );
