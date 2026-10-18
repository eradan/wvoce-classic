<?php
/**
 * Redirect 301 degli indirizzi spariti con la riclassificazione dei contenuti (0.6): categorie
 * eliminate, vecchie sottopagine di Formazione, pagine diventate articoli, articoli uniti.
 *
 * Scattano solo quando l'indirizzo darebbe "pagina non trovata": se un giorno lì torna una
 * pagina vera, vince la pagina. Per aggiungerne basta una riga nella tabella.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vecchio percorso → nuovo percorso (entrambi relativi alla home, con la barra finale).
 *
 * @return array<string,string>
 */
function vv_redirects() {
	return array(
		// Categorie eliminate
		'/category/appuntamenti/'                       => '/in-programma/',
		'/category/notizie/'                            => '/cosa-facciamo/eventi/',
		'/category/nessuna-categoria/'                  => '/cosa-facciamo/eventi/',
		'/category/alice/'                              => '/alice/',
		// Progetti: le schede da pagine ad articoli
		'/cosa-facciamo/progetti/la-bicicletta-gialla/' => '/la-bicicletta-gialla-spettacolo-per-bambini/',
		'/cosa-facciamo/alice-in-lucca/'                => '/alice/',
		// Vecchie sottopagine di Formazione e Laboratori (2022-2023)
		'/cosa-facciamo/formazione-e-laboratori/abitare-la-scena/' => '/abitare-la-scena-corso-di-teatro/',
		'/cosa-facciamo/formazione-e-laboratori/a-sbagliare-le-storie/' => '/a-sbagliare-le-storie/',
		'/cosa-facciamo/formazione-e-laboratori/lettere-agli-antenati/' => '/lettera-agli-antenati-laboratorio-di-letture-e-scrittura/',
		'/cosa-facciamo/formazione-e-laboratori/ti-daro-voce/' => '/ti-daro-voce/',
		'/cosa-facciamo/formazione-e-laboratori/corso-di-formazione-sulla-voce/' => '/corso-di-formazione-vivavoce/',
		'/cosa-facciamo/formazione-e-laboratori/il-gioco-del-teatro-3-6-anni/' => '/il-gioco-del-teatro-2022/',
		'/cosa-facciamo/formazione-e-laboratori/abitare-la-scena-14-19-anni/' => '/abitare-la-scena-ragazzi-2023/',
		// Pagina nascosta con due ritagli: ora c'è l'archivio
		'/chi-siamo/dicono-di-noi/'                     => '/category/rassegna-stampa/',
		// Articoli uniti a un altro
		'/vivavoce-a-if-2021/'                          => '/vivavoce-a-internet-festival/',
		'/alice_pop/'                                   => '/cartoline-dal-paese-delle-meraviglie/',
		// Modello di privacy policy di WordPress, mai compilato: ora in bozza
		'/privacy-policy/'                              => '/informativa-sulla-privacy/',
	);
}

/**
 * Sui 404, se il percorso è nella tabella, 301 verso il nuovo.
 */
function vv_redirect_old_paths() {
	if ( ! is_404() ) {
		return;
	}
	$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $base && '/' !== $base && 0 === strpos( $path, rtrim( $base, '/' ) ) ) {
		$path = substr( $path, strlen( rtrim( $base, '/' ) ) );
	}
	$path = '/' . trim( strtolower( rawurldecode( $path ) ), '/' ) . '/';
	$path = (string) preg_replace( '#page/\d+/$#', '', $path ); // le pagine 2, 3… di un archivio vanno all'inizio del nuovo
	$map  = vv_redirects();
	if ( isset( $map[ $path ] ) ) {
		wp_safe_redirect( home_url( $map[ $path ] ), 301, 'VivaVoce' );
		exit;
	}
}
add_action( 'template_redirect', 'vv_redirect_old_paths', 1 );
