<?php
/**
 * Test delle funzioni del tema che non hanno bisogno di WordPress (codice fiscale, foglio dei soci,
 * date, meta_query, lettere decorative). Si lanciano con: php tests/run.php
 *
 * Le poche funzioni di WordPress che servono sono imitate qui sotto, nel modo più semplice.
 *
 * @package VivaVoce
 */

// phpcs:disable

define( 'ABSPATH', __DIR__ . '/' );
date_default_timezone_set( 'UTC' ); // Come WordPress.

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_post_meta() {}
function apply_filters( $hook, $value ) {
	return $value;
}
function wp_strip_all_tags( $text ) {
	return trim( strip_tags( (string) $text ) );
}
function sanitize_title( $title ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $title ) ), '-' );
}

require dirname( __DIR__ ) . '/inc/meta.php';
require dirname( __DIR__ ) . '/inc/cards.php';
require dirname( __DIR__ ) . '/inc/setup.php';
require dirname( __DIR__ ) . '/inc/forminator.php';
require dirname( __DIR__ ) . '/inc/sheets.php';

$vv_falliti = 0;
$vv_totale  = 0;

/**
 * @param string $nome    Cosa si controlla.
 * @param mixed  $atteso  Valore atteso.
 * @param mixed  $ottenuto Valore ottenuto.
 */
function uguale( $nome, $atteso, $ottenuto ) {
	global $vv_falliti, $vv_totale;
	++$vv_totale;
	if ( $atteso === $ottenuto ) {
		return;
	}
	++$vv_falliti;
	echo "FALLITO: {$nome}\n  atteso:   " . var_export( $atteso, true ) . "\n  ottenuto: " . var_export( $ottenuto, true ) . "\n";
}

// Codice fiscale
uguale( 'CF valido', true, vv_codice_fiscale_valido( 'RSSMRA85T10A562S' ) );
uguale( 'CF valido, minuscolo e con spazi', true, vv_codice_fiscale_valido( ' rssmra85t10a562s ' ) );
uguale( 'CF con omocodia', true, vv_codice_fiscale_valido( 'RSSMRA85T10A56NH' ) );
uguale( 'CF con carattere di controllo sbagliato', false, vv_codice_fiscale_valido( 'RSSMRA85T10A562T' ) );
uguale( 'CF troppo corto', false, vv_codice_fiscale_valido( 'RSSMRA85T10A562' ) );
uguale( 'Partita IVA al posto del CF', false, vv_codice_fiscale_valido( '02441190507' ) );

$errori = vv_forminator_check_cf( array(), 1617, array( array( 'name' => 'text-1', 'value' => 'RSSMRA85T10A562T' ) ) );
uguale( 'Modulo 1617: CF sbagliato segnalato', array( array( 'text-1' => 'Controlla il codice fiscale: sono 16 lettere e numeri, come sulla tessera sanitaria.' ) ), $errori );
uguale( 'Modulo 1617: CF giusto passa', array(), vv_forminator_check_cf( array(), 1617, array( array( 'name' => 'text-1', 'value' => 'RSSMRA85T10A562S' ) ) ) );
uguale( 'Altro modulo: nessun controllo', array(), vv_forminator_check_cf( array(), 274, array( array( 'name' => 'text-1', 'value' => 'xxx' ) ) ) );

// Foglio dei soci: tab dell'anno sociale (settembre-agosto) e data
uguale( 'Tab di settembre', 'DomandeAmmissione 2026-2027', vv_sheets_tab( '2026-09-01 00:00:00' ) );
uguale( 'Tab di agosto', 'DomandeAmmissione 2025-2026', vv_sheets_tab( '2026-08-31 23:59:59' ) );
uguale( 'Tab di gennaio', 'DomandeAmmissione 2026-2027', vv_sheets_tab( '2027-01-15 10:00:00' ) );
uguale( 'Data come l\'export di Forminator', 'Ott 5, 2026 @ 10:08 AM', vv_sheets_data( '2026-10-05 10:08:00' ) );

// Foglio dei soci: valori dei campi
uguale( 'Consenso dato', 'TRUE', vv_sheets_valore( 'checked', 'consent-1' ) );
uguale( 'Consenso assente', '', vv_sheets_valore( '', 'consent-1' ) );
uguale( 'Parte di un indirizzo', 'Pisa', vv_sheets_valore( array( 'city' => ' Pisa ' ), 'address-1', 'city' ) );
uguale( 'Data a tendina', '07/03/1990', vv_sheets_valore( array( 'day' => '7', 'month' => '3', 'year' => '1990' ), 'date-1' ) );
uguale( 'Caselle', 'Teatro, Letture', vv_sheets_valore( array( 'Teatro', '', 'Letture' ), 'checkbox-2' ) );
uguale( 'File caricato', 'https://esempio.it/a.pdf', vv_sheets_valore( array( 'file' => array( 'file_url' => 'https://esempio.it/a.pdf' ) ), 'upload-1' ) );
uguale( 'Codice fiscale in maiuscolo', 'RSSMRA85T10A562S', vv_sheets_valore( 'rssmra85t10a562s', 'text-1' ) );
uguale( 'Tag HTML tolti', 'Mario', vv_sheets_valore( '<b>Mario</b>', 'name-1' ) );

$entry = (object) array(
	'form_id'          => 274,
	'entry_id'         => 12,
	'date_created_sql' => '2026-10-05 10:08:00',
	'meta_data'        => array(
		'name-1' => array( 'value' => 'Maria' ),
		'text-1' => array( 'value' => 'rssmra85t10a562s' ),
	),
);
$riga  = vv_sheets_riga( $entry );
uguale( 'Riga: riferimento', 'F274-12', $riga['Rif.'] );
uguale( 'Riga: tab', 'DomandeAmmissione 2026-2027', $riga['_tab'] );
uguale( 'Riga: nome', 'Maria', $riga['Nome'] );
uguale( 'Riga: colonna senza valore', '', $riga['Cognome'] );
uguale( 'Riga: tutte le colonne', count( vv_sheets_colonne() ) + 3, count( $riga ) );

// Date degli eventi e slug
uguale( 'Data valida', '2026-10-06', vv_sanitize_date( ' 2026-10-06 ' ) );
uguale( 'Data in altro formato', '', vv_sanitize_date( '06/10/2026' ) );
uguale( 'Slug', 'eventi,corsi-e-laboratori', vv_sanitize_slugs( 'Eventi, Corsi e laboratori,,' ) );

// meta_query: le condizioni si sommano, nessuna va persa
$date = array( array( 'key' => 'vv_data', 'value' => '2026-10-06', 'compare' => '>=' ) );
uguale( 'meta_query su argomenti vuoti', array( 'meta_query' => $date ), vv_add_meta_query( array(), $date ) );
uguale(
	'meta_query in AND con quella che c\'era',
	array(
		'post__not_in' => array( 3 ),
		'meta_query'   => array( 'relation' => 'AND', vv_featured_clause(), $date ),
	),
	vv_add_meta_query( array( 'post__not_in' => array( 3 ), 'meta_query' => vv_featured_clause() ), $date )
);

// Lettere decorative incollate dai social
uguale( 'Lettere decorative', '<strong class="vv-plain">Voce</strong> e testo', vv_plain_letters( '𝕍𝕠𝕔𝕖 e testo' ) );
uguale( 'Lettere decorative senza grassetto', 'Bold', vv_plain_letters( '𝐁𝐨𝐥𝐝', false ) );
uguale( 'Testo normale invariato', 'Ciao', vv_plain_letters( 'Ciao' ) );

echo $vv_falliti ? "\n{$vv_falliti} su {$vv_totale} controlli falliti.\n" : "{$vv_totale} controlli superati.\n";
exit( $vv_falliti ? 1 : 0 );
