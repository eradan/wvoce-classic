<?php
/**
 * Test delle funzioni del tema che non hanno bisogno di WordPress (codice fiscale, fogli dei soci e dei corsi,
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
$vv_filtri  = array();
$vv_opzioni = array();
function apply_filters( $hook, $value ) {
	global $vv_filtri;
	return isset( $vv_filtri[ $hook ] ) ? call_user_func( $vv_filtri[ $hook ], $value ) : $value;
}
function get_option( $name, $default = false ) {
	global $vv_opzioni;
	return array_key_exists( $name, $vv_opzioni ) ? $vv_opzioni[ $name ] : $default;
}
function update_option( $name, $value ) {
	global $vv_opzioni;
	$vv_opzioni[ $name ] = $value;
}
function remove_accents( $text ) {
	return strtr( (string) $text, array( 'à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'í' => 'i', 'ò' => 'o', 'ó' => 'o', 'ù' => 'u', 'ú' => 'u', 'À' => 'A', 'È' => 'E', 'É' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U' ) );
}
function wp_strip_all_tags( $text ) {
	return trim( strip_tags( (string) $text ) );
}
function delete_option( $name ) {
	global $vv_opzioni;
	unset( $vv_opzioni[ $name ] );
}
function esc_url_raw( $url ) {
	return preg_match( '#^https?://#', (string) $url ) ? (string) $url : '';
}
function sanitize_text_field( $text ) {
	return trim( strip_tags( (string) $text ) );
}
function absint( $n ) {
	return abs( (int) $n );
}
class WP_Error {
	public $messaggio;
	public function __construct( $codice, $messaggio ) {
		$this->messaggio = $messaggio;
	}
	public function get_error_message() {
		return $this->messaggio;
	}
}
function is_wp_error( $x ) {
	return $x instanceof WP_Error;
}
function wp_json_encode( $x ) {
	return json_encode( $x );
}
$vv_richieste = array();
$vv_risposta  = array( 'ok' => true );
$vv_http      = null; // Risposta HTTP grezza al POST, al posto di quella con $vv_risposta.
function wp_remote_post( $url, $args ) {
	global $vv_richieste, $vv_risposta, $vv_http;
	$vv_richieste[] = json_decode( $args['body'], true );
	return $vv_http ? $vv_http : array( 'code' => 200, 'body' => json_encode( $vv_risposta ) );
}
function wp_remote_get( $url, $args = array() ) {
	global $vv_risposta;
	return '' === (string) $url ? new WP_Error( 'http_request_failed', 'A valid URL was not provided.' ) : array( 'code' => 200, 'body' => json_encode( $vv_risposta ) );
}
function wp_remote_retrieve_response_code( $res ) {
	return $res['code'];
}
function wp_remote_retrieve_header( $res, $name ) {
	return $res['headers'][ $name ] ?? '';
}
function wp_remote_retrieve_body( $res ) {
	return $res['body'];
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
uguale( 'Tab di settembre', 'DomandeAmmissione 2026-2027', vv_soci_tab( '2026-09-01 00:00:00' ) );
uguale( 'Tab di agosto', 'DomandeAmmissione 2025-2026', vv_soci_tab( '2026-08-31 23:59:59' ) );
uguale( 'Tab di gennaio', 'DomandeAmmissione 2026-2027', vv_soci_tab( '2027-01-15 10:00:00' ) );
uguale( 'Data come l\'export di Forminator', 'Ott 5, 2026 @ 10:08 AM', vv_soci_data( '2026-10-05 10:08:00' ) );

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
$riga  = vv_soci_riga( $entry );
uguale( 'Riga: riferimento', 'F274-12', $riga['Rif.'] );
uguale( 'Riga: tab', 'DomandeAmmissione 2026-2027', $riga['_tab'] );
uguale( 'Riga: nome', 'Maria', $riga['Nome'] );
uguale( 'Riga: colonna senza valore', '', $riga['Cognome'] );
uguale( 'Riga: tutte le colonne', count( vv_soci_colonne() ) + 3, count( $riga ) );

// Modulo dei corsi: struttura letta da Forminator (solo per i test)
class Forminator_API {
	public static $entries = array();
	public static $campi   = array();
	public static function get_entries( $form ) {
		return self::$entries;
	}
	public static function get_form_fields( $form ) {
		return self::$campi;
	}
}
$vv_opzioni_1617 = array(
	'Adulti: Laboratorio di lettura ad alta voce corale, ottobre-dicembre 2026 (martedì 18.30-20.30)',
	'Adulti: Laboratorio di tecniche attoriali, ottobre-dicembre 2026 (giovedì 18.30-20.30)',
	'Adulti: Laboratorio di messa in scena, gennaio-giugno 2027 (giovedì 18.30-20.30)',
	'Bambini (elementari): Il gioco del teatro, ottobre-dicembre 2026',
);
Forminator_API::$campi = array(
	array( 'element_id' => 'name-1', 'field_label' => 'Nome', 'type' => 'name' ),
	array(
		'element_id'  => 'checkbox-1',
		'field_label' => 'Corso',
		'type'        => 'checkbox',
		'options'     => array_map( static fn( $o ) => array( 'label' => $o, 'value' => $o ), $vv_opzioni_1617 ),
	),
);
uguale( 'Opzioni del corso lette dal modulo', $vv_opzioni_1617, vv_corsi_opzioni() );

// Foglio dei corsi: righe di un'iscrizione, una per corso, il tab lo sceglie il foglio
$vv_corsi_entry = static function ( $corso ) {
	return (object) array(
		'form_id'          => 1617,
		'entry_id'         => 40,
		'date_created_sql' => '2026-10-05 10:08:00',
		'meta_data'        => array(
			'text-1'     => array( 'value' => 'rssmra85t10a562s' ),
			'checkbox-1' => array( 'value' => $corso ),
		),
	);
};
$righe = vv_corsi_righe( $vv_corsi_entry( 'Tecnica attoriale' ) );
uguale( 'Corso singolo: una riga', 1, count( $righe ) );
uguale( 'Corso singolo: testo del corso', 'Tecnica attoriale', $righe[0]['Corso'] );
uguale( 'Corso singolo: Rif.', 'F1617-40', $righe[0]['Rif.'] );
uguale( 'Corso singolo: codice fiscale', 'RSSMRA85T10A562S', $righe[0]['Codice Fiscale'] );
uguale( 'Corso singolo: campo vuoto, colonna vuota', '', $righe[0]['Nome'] );
uguale( 'Corso singolo: chiavi, nessun _tab', array_merge( array( 'Rif.' ), array_keys( vv_corsi_colonne() ), array( 'Corso' ) ), array_keys( $righe[0] ) );
$righe = vv_corsi_righe( $vv_corsi_entry( array( 'Lettura', 'Canto', 'Lettura' ) ) );
uguale( 'Più corsi: una riga per corso, senza ripetizioni', array( 'Lettura', 'Canto' ), array_column( $righe, 'Corso' ) );
uguale( 'Più corsi: stesso Rif.', array( 'F1617-40', 'F1617-40' ), array_column( $righe, 'Rif.' ) );
$righe = vv_corsi_righe( $vv_corsi_entry( $vv_opzioni_1617[2] . ', ' . $vv_opzioni_1617[1] ) );
uguale( 'Caselle unite in un testo: opzioni del modulo', array( $vv_opzioni_1617[2], $vv_opzioni_1617[1] ), array_column( $righe, 'Corso' ) );
uguale(
	'Testo unito: opzione contenuta in un\'altra non trovata due volte',
	array( 'Il gioco del teatro avanzato', 'Dizione' ),
	vv_corsi_scelti( 'Il gioco del teatro avanzato, Dizione', array( 'Il gioco del teatro', 'Il gioco del teatro avanzato', 'Dizione' ) )
);
uguale(
	'Testo unito: opzione corta e lunga scelte entrambe',
	array( 'Il gioco del teatro avanzato', 'Il gioco del teatro' ),
	vv_corsi_scelti( 'Il gioco del teatro avanzato, Il gioco del teatro', array( 'Il gioco del teatro', 'Il gioco del teatro avanzato' ) )
);
uguale( 'Testo unito senza opzioni note: un corso', array( 'Dizione, Canto' ), vv_corsi_scelti( 'Dizione, Canto', array() ) );
uguale( 'Nessun corso: una riga con Corso vuoto', array( '' ), array_column( vv_corsi_righe( $vv_corsi_entry( '' ) ), 'Corso' ) );
uguale( 'Campo del corso indicato: requisito soddisfatto', '', vv_corsi_requisito() );
uguale(
	'Modulo 1617: colonne',
	array( 'name-1', 'name-2', 'text-1', 'email-1', 'phone-2', 'upload-1', 'consent-1', 'consent-2', 'consent-3', 'textarea-1' ),
	array_values( vv_corsi_colonne() )
);

// Foglio dei corsi: tab secondo il tab "configurazioni" (come lo script)
$vv_mappa = array(
	'mappa' => array(
		$vv_opzioni_1617[0] => 'lettura-ad-alta-voce-2026',
		$vv_opzioni_1617[3] => 'gioco-del-teatro-2026',
		'Dizione'           => 'dizione-2027',
	),
	'tab'   => array( 'lettura-ad-alta-voce-2026', 'gioco-del-teatro-2026', 'configurazioni' ),
);
uguale( 'Normalizza: accenti, maiuscole, spazi', 'adulti: lettura e perche', vv_corsi_normalizza( "  Adulti:  Lettura\tè PERCHÉ " ) );
uguale( 'Normalizza: spazio non separabile, come lo script', 'adulti: lettura', vv_corsi_normalizza( "Adulti:\u{00A0}Lettura" ) );
uguale( 'Normalizza: maiuscole oltre l\'ASCII', 'ðorð', vv_corsi_normalizza( 'ÐORÐ' ) );
uguale( 'Mappa: corso noto', 'lettura-ad-alta-voce-2026', vv_corsi_tab( $vv_opzioni_1617[0], $vv_mappa ) );
uguale( 'Mappa: scritto in altro modo', 'gioco-del-teatro-2026', vv_corsi_tab( strtoupper( 'Bambini (elementari): Il gioco del teatro,  ottobre-dicembre 2026' ), $vv_mappa ) );
uguale( 'Mappa: corso assente', 'Da smistare', vv_corsi_tab( $vv_opzioni_1617[1], $vv_mappa ) );
uguale( 'Mappa: tab inesistente', 'Da smistare', vv_corsi_tab( 'Dizione', $vv_mappa ) );
uguale( 'Mappa: corso vuoto', 'Da smistare', vv_corsi_tab( '', $vv_mappa ) );

$vv_filtri['vv_corsi_campo'] = static fn() => '';
uguale( 'Senza campo del corso: fermo', 'manca il campo del corso (filtro vv_corsi_campo)', vv_corsi_requisito() );
uguale( 'Senza campo del corso: Corso vuoto', '', vv_corsi_righe( $vv_corsi_entry( 'Tecnica attoriale' ) )[0]['Corso'] );
unset( $vv_filtri['vv_corsi_campo'] );

// Impostazioni dal pannello, non da wp-config.php
$vv_opzioni = array();
define( 'VV_SHEETS_URL', 'https://script.google.com/macros/s/vecchio/exec' );
define( 'VV_CORSI_TOKEN', 'vecchio' );
$c = vv_sheets_config( 'soci' );
uguale( 'Soci: predefiniti', array( '', '', 274, '2026-09-01', 'VV_SOCI' ), array( $c['url'], $c['token'], $c['form'], $c['dal'], $c['prefisso'] ) );
uguale( 'Costanti di wp-config.php ignorate', '', vv_sheets_config( 'corsi' )['token'] );
uguale( 'Costanti rimaste segnalate', array( 'VV_SHEETS_URL', 'VV_CORSI_TOKEN' ), vv_sheets_costanti_rimaste() );
uguale( 'Soci: opzioni con i nomi nuovi', array( 'vv_soci_impostazioni', 'vv_soci_ultimo', 'vv_soci_stato', '' ), array( $c['impostazioni'], $c['ultimo'], $c['stato'], $c['inizio'] ) );
$c = vv_sheets_config( 'corsi' );
uguale( 'Corsi: modulo, opzioni, partenza dalle nuove', array( 1617, 'vv_corsi_ultimo', 'vv_corsi_stato', 'vv_corsi_inizio', '' ), array( $c['form'], $c['ultimo'], $c['stato'], $c['inizio'], $c['dal'] ) );
uguale( 'Soci: una riga per iscrizione', array( vv_soci_riga( $entry ) ), vv_soci_righe( $entry ) );

$avvisi = vv_sheets_salva_impostazioni( 'soci', array( 'url' => 'https://script.google.com/macros/s/abc/exec', 'token' => ' segreto ', 'form' => '274', 'dal' => '2026-09-15' ) );
$c      = vv_sheets_config( 'soci' );
uguale( 'Salvataggio: nessun avviso', array(), $avvisi );
uguale( 'Salvataggio: valori', array( 'https://script.google.com/macros/s/abc/exec', 'segreto', 274, '2026-09-15' ), array( $c['url'], $c['token'], $c['form'], $c['dal'] ) );
$vv_opzioni['vv_soci_ultimo'] = 50;
$avvisi = vv_sheets_salva_impostazioni( 'soci', array( 'url' => 'https://esempio.it/x', 'token' => '', 'form' => '274', 'dal' => '15/09/2026' ) );
$c      = vv_sheets_config( 'soci' );
uguale( 'Salvataggio: codice vuoto, resta il salvato', 'segreto', $c['token'] );
uguale( 'Salvataggio: data non valida, resta la precedente', '2026-09-15', $c['dal'] );
uguale( 'Salvataggio: due avvisi (indirizzo insolito, data)', 2, count( $avvisi ) );
uguale( 'Salvataggio: stesso modulo, punto raggiunto intatto', 50, $vv_opzioni['vv_soci_ultimo'] );
vv_sheets_salva_impostazioni( 'soci', array( 'url' => '', 'form' => '300', 'dal' => '' ) );
$c = vv_sheets_config( 'soci' );
uguale( 'Salvataggio: modulo cambiato, si riparte', false, isset( $vv_opzioni['vv_soci_ultimo'] ) );
uguale( 'Salvataggio: indirizzo vuoto, collegamento spento', false, vv_sheets_configurato( 'soci' ) );
uguale( 'Salvataggio: data tolta', '', $c['dal'] );

// Migrazione delle opzioni della 0.8
$vv_opzioni = array(
	'vv_sheets_ultimo'       => 120,
	'vv_sheets_stato'        => array( 'controllo' => 1 ),
	'vv_sheets_corsi_inizio' => 31,
	'vv_corsi_ultimo'        => 40,
	'vv_sheets_corsi_ultimo' => 35,
);
vv_sheets_migra_opzioni();
uguale( 'Migrazione: soci', array( 120, array( 'controllo' => 1 ) ), array( $vv_opzioni['vv_soci_ultimo'], $vv_opzioni['vv_soci_stato'] ) );
uguale( 'Migrazione: corsi, punto di partenza', 31, $vv_opzioni['vv_corsi_inizio'] );
uguale( 'Migrazione: valore nuovo già presente non sovrascritto', 40, $vv_opzioni['vv_corsi_ultimo'] );
uguale( 'Migrazione: nomi vecchi tolti', array(), array_values( array_filter( array_keys( $vv_opzioni ), static fn( $k ) => 0 === strpos( $k, 'vv_sheets_' ) && 'vv_sheets_versione' !== $k ) ) );
$vv_opzioni['vv_sheets_ultimo'] = 999;
vv_sheets_migra_opzioni();
uguale( 'Migrazione: una volta sola', 120, $vv_opzioni['vv_soci_ultimo'] );

// Partenza dalle nuove: la prima volta si fissa l'iscrizione più recente
$vv_opzioni = array();
Forminator_API::$entries = array( (object) array( 'entry_id' => 7 ), (object) array( 'entry_id' => 31, 'is_spam' => 1 ), (object) array( 'entry_id' => 12 ) );
uguale( 'Partenza dalle nuove: iscrizione più recente', 31, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Partenza dalle nuove: salvata', 31, $vv_opzioni['vv_corsi_inizio'] );
Forminator_API::$entries[] = (object) array( 'entry_id' => 32, 'date_created_sql' => '2026-10-06 09:00:00' );
uguale( 'Partenza dalle nuove: resta fissa', 31, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Partenza dalle nuove: parte solo la nuova', array( 32 ), array_map( static fn( $e ) => $e->entry_id, vv_sheets_da_inviare( 'corsi' ) ) );
$vv_opzioni['vv_corsi_ultimo'] = 32;
uguale( 'Dopo un invio conta l\'ultima confermata', 32, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Soci: si parte da zero', 0, vv_sheets_ultimo( 'soci' ) );

// ID del foglio dal pannello, mandato allo script a ogni chiamata
$vv_id = '1TvSREeUfoeAcA1uI3Hx9qpytOVUOMQQc5664DqwxUOw';
uguale( 'ID dall\'indirizzo del foglio', $vv_id, vv_sheets_id_foglio( "https://docs.google.com/spreadsheets/d/{$vv_id}/edit#gid=0" ) );
uguale( 'ID nudo', $vv_id, vv_sheets_id_foglio( " {$vv_id} " ) );
uguale( 'ID non valido', '', vv_sheets_id_foglio( 'Albo Soci' ) );
$vv_opzioni = array();
uguale( 'Foglio: nessun avviso', array(), vv_sheets_salva_impostazioni( 'corsi', array( 'url' => 'https://script.google.com/macros/s/abc/exec', 'token' => 't', 'foglio' => "https://docs.google.com/spreadsheets/d/{$vv_id}/edit" ) ) );
uguale( 'Foglio: salvato come ID', $vv_id, vv_sheets_config( 'corsi' )['foglio_id'] );
uguale( 'Foglio: nome leggibile invariato', 'Iscrizione Corsi', vv_sheets_config( 'corsi' )['foglio'] );
uguale( 'Foglio non valido: un avviso', 1, count( vv_sheets_salva_impostazioni( 'corsi', array( 'url' => 'https://script.google.com/macros/s/abc/exec', 'foglio' => 'boh' ) ) ) );
uguale( 'Foglio non valido: resta il precedente', $vv_id, vv_sheets_config( 'corsi' )['foglio_id'] );
$vv_risposta = array( 'ok' => true, 'foglio' => 'PROVA Iscrizione Corsi', 'rif' => array() );
uguale( 'Verifica: nome del foglio raggiunto', 'PROVA Iscrizione Corsi', vv_sheets_verifica( 'corsi' ) );
uguale( 'Richiesta: codice, foglio, righe', array( 'token' => 't', 'foglio' => $vv_id, 'righe' => array() ), end( $vv_richieste ) );
vv_sheets_salva_impostazioni( 'corsi', array( 'url' => 'https://script.google.com/macros/s/abc/exec', 'foglio' => '' ) );
vv_sheets_verifica( 'corsi' );
uguale( 'Senza foglio: la richiesta lo omette', false, array_key_exists( 'foglio', end( $vv_richieste ) ) );
$vv_risposta = array( 'ok' => true, 'rif' => array() );
uguale( 'Verifica con uno script precedente: nome vuoto', '', vv_sheets_verifica( 'corsi' ) );
$vv_http = array( 'code' => 302, 'body' => '' );
uguale( 'Reindirizzamento senza indirizzo: errore chiaro', 'Il foglio ha risposto: risposta inattesa (HTTP 302 senza indirizzo)', vv_sheets_verifica( 'corsi' )->get_error_message() );
$vv_http     = array( 'code' => 302, 'body' => '', 'headers' => array( 'location' => 'https://script.googleusercontent.com/echo' ) );
$vv_risposta = array( 'ok' => true, 'foglio' => 'Iscrizione Corsi', 'rif' => array() );
uguale( 'Reindirizzamento seguito con una GET', 'Iscrizione Corsi', vv_sheets_verifica( 'corsi' ) );
$vv_http     = null;
$vv_risposta = array( 'ok' => false, 'errore' => 'foglio non raggiungibile' );
uguale( 'Verifica: errore dello script', 'Il foglio ha risposto: foglio non raggiungibile', vv_sheets_verifica( 'corsi' )->get_error_message() );

// Script da copiare nel pannello
uguale( 'Script dei soci disponibile', true, false !== strpos( vv_sheets_script( 'soci' ), "var PROPRIETA = 'VV_SOCI';" ) );
uguale( 'Script dei corsi con il tab configurazioni', true, false !== strpos( vv_sheets_script( 'corsi' ), "var CONFIGURAZIONI = 'configurazioni';" ) );

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
