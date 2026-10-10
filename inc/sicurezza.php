<?php
/**
 * Meno informazioni utili a chi tenta l'accesso a forza bruta: nomi utente fuori dalla sitemap e
 * dall'API pubblica, XML-RPC spento, versione di WordPress nascosta. Il filtro vv_sicurezza (false)
 * spegne tutto.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

if ( ! apply_filters( 'vv_sicurezza', true ) ) {
	return;
}

/**
 * Niente sitemap degli autori (wp-sitemap-users-1.xml elencava i nomi utente).
 *
 * @param WP_Sitemaps_Provider|false $provider Fornitore della sitemap.
 * @param string                     $name     Nome del fornitore.
 */
function vv_sitemap_senza_utenti( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'vv_sitemap_senza_utenti', 10, 2 );

/**
 * Elenco degli utenti nell'API (/wp-json/wp/v2/users) solo per chi è collegato: l'editor lo usa,
 * i visitatori no.
 *
 * @param array $endpoints Rotte dell'API.
 */
function vv_api_senza_utenti( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		foreach ( array_keys( $endpoints ) as $rotta ) {
			if ( 0 === strpos( $rotta, '/wp/v2/users' ) ) {
				unset( $endpoints[ $rotta ] );
			}
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'vv_api_senza_utenti' );

// XML-RPC: le app e i servizi che pubblicano da fuori passano ormai dall'API REST.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
remove_action( 'wp_head', 'rsd_link' );

/**
 * Niente intestazione X-Pingback.
 *
 * @param array $headers Intestazioni HTTP.
 */
function vv_senza_pingback( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'vv_senza_pingback' );

/**
 * Il <link rel="pingback"> di Sydney (header.php) punta a xmlrpc.php: con XML-RPC spento resta vuoto
 * e il browser lo ignora.
 *
 * @param string $url    Indirizzo.
 * @param string $show   Voce richiesta a bloginfo().
 */
function vv_senza_pingback_url( $url, $show ) {
	return 'pingback_url' === $show ? '' : $url;
}
add_filter( 'bloginfo_url', 'vv_senza_pingback_url', 10, 2 );

// Versione di WordPress fuori dall'HTML e dai feed.
add_filter( 'the_generator', '__return_empty_string' );
