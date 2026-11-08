<?php
/**
 * Dati strutturati schema.org: in home il sito e l'associazione, negli articoli con data e luogo
 * l'evento. Con un plugin SEO attivo lascia fare al plugin.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Con i nomi alternativi con cui Google può chiamarlo: ragione sociale e dominio ("wvoce").
 */
function vv_ds_sito() {
	$nome      = get_bloginfo( 'name' );
	$alternati = array();
	foreach ( array( vv_mod( 'vv_nome_legale' ), strtok( vv_dominio(), '.' ) ) as $alt ) {
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

function vv_ds_associazione( $immagine = '' ) {
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
		'image'       => $immagine,
		'description' => get_bloginfo( 'description' ),
		'email'       => vv_mod( 'vv_email' ),
		'telephone'   => vv_mod( 'vv_telefono' ),
		'address'     => count( $sede ) > 2 ? $sede : array(), // con solo tipo e paese non è una sede
		'taxID'       => vv_mod( 'vv_cf' ),
		'vatID'       => $piva ? 'IT' . $piva : '',
		'sameAs'      => array_values( array_filter( array( vv_mod( 'vv_facebook' ), vv_mod( 'vv_instagram' ) ) ) ),
	) );
}

/**
 * Vuoto senza data o senza luogo: Google li vuole entrambi.
 *
 * @param array $evento titolo, descrizione, url, immagine, inizio e fine (AAAA-MM-GG), luogo ("Nome, indirizzo").
 */
function vv_ds_evento( $evento ) {
	list( $inizio, $fine ) = vv_date_evento( $evento['inizio'] ?? '', $evento['fine'] ?? '' );
	$luogo                 = trim( (string) ( $evento['luogo'] ?? '' ) );
	if ( ! $inizio || '' === $luogo ) {
		return array();
	}
	return array_filter( array(
		'@type'               => 'Event',
		'name'                => $evento['titolo'] ?? '',
		'description'         => $evento['descrizione'] ?? '',
		'url'                 => $evento['url'] ?? '',
		'image'               => $evento['immagine'] ?? '',
		'startDate'           => $inizio,
		'endDate'             => $fine,
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		// "Spazio Mumu, via Lenin 155/b, Pisa": il nome è la parte prima della virgola.
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

function vv_dati_strutturati() {
	if ( ! is_front_page() && ! is_singular( 'post' ) ) {
		return array();
	}
	$dati = vv_condivisione_dati();
	if ( is_front_page() ) {
		return array( vv_ds_sito(), vv_ds_associazione( $dati['immagine']['url'] ?? '' ) );
	}
	$articolo_id = get_queried_object_id();
	$evento      = vv_ds_evento( array(
		'titolo'      => vv_testo_semplice( $dati['titolo'] ),
		'descrizione' => $dati['descrizione'],
		'url'         => $dati['url'],
		'immagine'    => $dati['immagine']['url'] ?? '',
		'inizio'      => get_post_meta( $articolo_id, 'vv_data', true ),
		'fine'        => get_post_meta( $articolo_id, 'vv_data_fine', true ),
		'luogo'       => get_post_meta( $articolo_id, 'vv_luogo', true ),
	) );
	return $evento ? array( $evento ) : array();
}

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
