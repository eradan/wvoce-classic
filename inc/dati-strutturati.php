<?php
/**
 * Dati strutturati schema.org (JSON-LD) per i motori di ricerca. In home: il sito, con i nomi con cui
 * Google può chiamarlo nei risultati, e l'associazione. Negli articoli con data e luogo: l'evento.
 *
 * Nessun valore scritto qui: nome e motto vengono da Impostazioni → Generali, i dati dell'associazione da
 * Customizer → VivaVoce → Dati dell'associazione, il logo da quello del sito, date e luogo dai campi
 * dell'articolo. Con un plugin SEO attivo lascia fare al plugin, come inc/condivisione.php.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Il sito: nome e nomi alternativi (ragione sociale e dominio, "wvoce").
 */
function vv_ds_sito() {
	$nome      = get_bloginfo( 'name' );
	$dominio   = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	$alternati = array();
	foreach ( array( vv_mod( 'vv_nome_legale' ), strtok( $dominio, '.' ) ) as $alt ) {
		if ( $alt && 0 !== strcasecmp( $alt, $nome ) ) {
			$alternati[] = $alt;
		}
	}
	return array_filter( array(
		'@type'         => 'WebSite',
		'@id'           => home_url( '/#sito' ),
		'name'          => $nome,
		'alternateName' => $alternati,
		'url'           => home_url( '/' ),
		'inLanguage'    => 'it-IT',
		'publisher'     => array( '@id' => home_url( '/#associazione' ) ),
	) );
}

/**
 * L'associazione: recapiti, sede, codici fiscali, profili social.
 */
function vv_ds_associazione() {
	$sede = array_filter( array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => vv_mod( 'vv_indirizzo' ),
		'postalCode'      => vv_mod( 'vv_cap' ),
		'addressLocality' => vv_mod( 'vv_citta' ),
		'addressCountry'  => 'IT',
	) );
	$piva = vv_mod( 'vv_piva' );
	return array_filter( array(
		'@type'       => 'Organization',
		'@id'         => home_url( '/#associazione' ),
		'name'        => get_bloginfo( 'name' ),
		'legalName'   => vv_mod( 'vv_nome_legale' ),
		'url'         => home_url( '/' ),
		'logo'        => get_theme_mod( 'site_logo', '' ),
		'description' => get_bloginfo( 'description' ),
		'email'       => vv_mod( 'vv_email' ),
		'telephone'   => vv_mod( 'vv_telefono' ),
		'address'     => count( $sede ) > 2 ? $sede : array(), // Solo tipo e paese: niente sede.
		'taxID'       => vv_mod( 'vv_cf' ),
		'vatID'       => $piva ? 'IT' . $piva : '',
		'sameAs'      => array_values( array_filter( array( vv_mod( 'vv_facebook' ), vv_mod( 'vv_instagram' ) ) ) ),
	) );
}

/**
 * Un evento, dai dati di un articolo. Vuoto senza data o senza luogo: Google li vuole entrambi.
 *
 * @param array $e titolo, descrizione, url, immagine, inizio e fine (AAAA-MM-GG), luogo ("Nome, indirizzo").
 */
function vv_ds_evento( $e ) {
	$inizio = vv_sanitize_date( $e['inizio'] ?? '' );
	$luogo  = trim( (string) ( $e['luogo'] ?? '' ) );
	if ( ! $inizio || '' === $luogo ) {
		return array();
	}
	// Come vv_event_dates(): senza fine, o con la fine prima dell'inizio, l'evento dura un giorno.
	$fine = vv_sanitize_date( $e['fine'] ?? '' );
	$fine = $fine && $fine >= $inizio ? $fine : $inizio;
	return array_filter( array(
		'@type'               => 'Event',
		'name'                => $e['titolo'] ?? '',
		'description'         => $e['descrizione'] ?? '',
		'url'                 => $e['url'] ?? '',
		'image'               => $e['immagine'] ?? '',
		'startDate'           => $inizio,
		'endDate'             => $fine,
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		// "Spazio Mumu, via Lenin 155/b, San Giuliano Terme": il nome è la parte prima della virgola.
		'location'            => array(
			'@type'   => 'Place',
			'name'    => trim( explode( ',', $luogo, 2 )[0] ),
			'address' => $luogo,
		),
		'organizer'           => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	) );
}

/**
 * Dati strutturati della pagina corrente.
 */
function vv_dati_strutturati() {
	if ( is_front_page() ) {
		return array( vv_ds_sito(), vv_ds_associazione() );
	}
	if ( ! is_singular( 'post' ) ) {
		return array();
	}
	$id     = get_queried_object_id();
	$d      = vv_condivisione_dati();
	$evento = vv_ds_evento( array(
		'titolo'      => html_entity_decode( wp_strip_all_tags( $d['titolo'] ), ENT_QUOTES, 'UTF-8' ),
		'descrizione' => $d['descrizione'],
		'url'         => $d['url'],
		'immagine'    => $d['immagine']['url'] ?? '',
		'inizio'      => get_post_meta( $id, 'vv_data', true ),
		'fine'        => get_post_meta( $id, 'vv_data_fine', true ),
		'luogo'       => get_post_meta( $id, 'vv_luogo', true ),
	) );
	return $evento ? array( $evento ) : array();
}

/**
 * Stampa i dati strutturati nell'<head>.
 */
function vv_dati_strutturati_head() {
	if ( vv_condivisione_plugin_seo() ) {
		return;
	}
	$nodi = vv_dati_strutturati();
	if ( ! $nodi ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array(
		'@context' => 'https://schema.org',
		'@graph'   => $nodi,
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . "</script>\n";
}
add_action( 'wp_head', 'vv_dati_strutturati_head', 6 );
