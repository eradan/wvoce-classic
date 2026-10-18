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
$vv_opzioni = array();
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
function strip_shortcodes( $text ) {
	return preg_replace( '/\[[^\]]*\]/', '', (string) $text );
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
$vv_adesso = '2026-10-07 10:00:00';
function current_time( $type ) {
	global $vv_adesso;
	return $vv_adesso;
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}
function esc_url( $url ) {
	return (string) $url;
}
function home_url( $path = '' ) {
	return 'https://wvoce.it' . $path;
}
function get_theme_mod( $name, $default = false ) {
	return $default;
}
define( 'VV_URI', 'https://wvoce.it/wp-content/themes/wvoce-classic' );
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}
function wpautop( $text ) {
	$blocchi = preg_split( '/\n\s*\n/', trim( (string) $text ) );
	return implode( "\n", array_map( static fn( $b ) => '<p>' . nl2br( $b, false ) . '</p>', $blocchi ) );
}
function make_clickable( $text ) {
	return preg_replace( '#(https?://[^\s<]+)#', '<a href="$1">$1</a>', (string) $text );
}
function get_privacy_policy_url() {
	return 'https://wvoce.it/informativa-sulla-privacy/';
}
$vv_admin = false;
function current_user_can( $cap ) {
	global $vv_admin;
	return $vv_admin;
}
function get_bloginfo( $show ) {
	return array( 'name' => 'VivaVoce', 'description' => 'Lettura ad alta voce, teatro e creatività a Pisa' )[ $show ] ?? '';
}
function sanitize_title( $title ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $title ) ), '-' );
}

define( 'DAY_IN_SECONDS', 86400 );
function wp_list_pluck( $list, $field ) {
	return array_map( static fn( $x ) => is_object( $x ) ? $x->$field : $x[ $field ], $list );
}
$vv_email = array();
function wp_mail( $a, $oggetto, $testo ) {
	global $vv_email;
	$vv_email[] = array( 'a' => $a, 'oggetto' => $oggetto, 'testo' => $testo );
	return true;
}
function is_email( $email ) {
	return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
}
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
function admin_url( $path = '' ) {
	return 'https://wvoce.it/wp-admin/' . $path;
}
$vv_transient = array();
function set_transient( $name, $value, $durata ) {
	global $vv_transient;
	$vv_transient[ $name ] = $value;
}
function get_transient( $name ) {
	global $vv_transient;
	return $vv_transient[ $name ] ?? false;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function trailingslashit( $text ) {
	return rtrim( (string) $text, '/' ) . '/';
}
function add_query_arg( $key, $value, $url ) {
	return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $key . '=' . $value;
}
/**
 * Date in italiano come wp_date(), per i soli formati del tema (j, n, Y, F, M).
 */
function wp_date( $format, $ts ) {
	$mesi = array( 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre' );
	$out  = '';
	foreach ( str_split( $format ) as $c ) {
		$n    = (int) gmdate( 'n', $ts ) - 1;
		$out .= 'F' === $c ? $mesi[ $n ] : ( 'M' === $c ? substr( $mesi[ $n ], 0, 3 ) : ( ctype_alpha( $c ) ? gmdate( $c, $ts ) : $c ) );
	}
	return $out;
}

require dirname( __DIR__ ) . '/inc/customizer.php';
require dirname( __DIR__ ) . '/inc/meta.php';
require dirname( __DIR__ ) . '/inc/cards.php';
require dirname( __DIR__ ) . '/inc/contenuti.php';
require dirname( __DIR__ ) . '/inc/condivisione.php';
require dirname( __DIR__ ) . '/inc/dati-strutturati.php';
require dirname( __DIR__ ) . '/inc/email.php';
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
uguale( 'Modulo 274: CF sbagliato segnalato', 1, count( vv_forminator_check_cf( array(), 274, array( array( 'name' => 'text-1', 'value' => 'RSSMRA85T10A562T' ) ) ) ) );
uguale( 'Altro modulo: nessun controllo', array(), vv_forminator_check_cf( array(), 999, array( array( 'name' => 'text-1', 'value' => 'xxx' ) ) ) );

// Foglio dei soci: tab dell'anno sociale (settembre-agosto) e data
uguale( 'Tab di settembre', 'DomandeAmmissione 2026-2027', vv_soci_tab( '2026-09-01 00:00:00' ) );
uguale( 'Tab di agosto', 'DomandeAmmissione 2025-2026', vv_soci_tab( '2026-08-31 23:59:59' ) );
uguale( 'Tab di gennaio', 'DomandeAmmissione 2026-2027', vv_soci_tab( '2027-01-15 10:00:00' ) );
uguale( 'Anno sociale: 31 agosto, 1° settembre, gennaio', array( 2025, 2026, 2026 ), array( vv_anno_sociale( '2026-08-31 23:59:59' ), vv_anno_sociale( '2026-09-01 00:00:00' ), vv_anno_sociale( '2027-01-15 10:00:00' ) ) );
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
	public static function get_entry( $form, $id ) {
		foreach ( self::$entries as $e ) {
			if ( (int) $e->entry_id === (int) $id ) {
				return $e;
			}
		}
		return null;
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
uguale( 'Mappa: tab "configurazioni" non è di un corso', 'Da smistare', vv_corsi_tab( 'Canto', array( 'mappa' => array( 'Canto' => 'configurazioni' ), 'tab' => array( 'configurazioni' ) ) ) );

// Corsi riservati ai soci dell'anno: codice fiscale fra le iscrizioni del modulo dei soci
$vv_socio = static function ( $id, $data, $cf, $extra = array() ) {
	return (object) ( array( 'entry_id' => $id, 'date_created_sql' => $data, 'meta_data' => array( 'text-1' => array( 'value' => $cf ) ) ) + $extra );
};
$vv_cf_corso = static fn( $cf ) => vv_forminator_check_cf( array(), 1617, array( array( 'name' => 'text-1', 'value' => $cf ) ) );
$vv_msg_socio = 'Questo codice fiscale manca ancora fra i soci 2026-2027: i corsi sono riservati ai soci. Prima occorre iscriversi all\'associazione con il <a href="https://wvoce.it/cosa-puoi-fare/diventa-socio/" target="_blank" rel="noopener">modulo di iscrizione a socio</a>, poi si torna a questa pagina.';
Forminator_API::$entries = array(
	$vv_socio( 1, '2026-09-03 18:00:00', ' rssmra85t10a562s ' ),
	$vv_socio( 2, '2026-06-10 18:00:00', 'BNCLRA90A41G702N' ),
	$vv_socio( 3, '2026-09-20 18:00:00', 'VRDGPP80A01H501U', array( 'is_spam' => 1 ) ),
);
uguale( 'Socio dell\'anno: iscrizione al corso accettata', array(), $vv_cf_corso( 'RSSMRA85T10A562S' ) );
uguale( 'Socio dell\'anno precedente: errore con il link', array( array( 'text-1' => $vv_msg_socio ) ), $vv_cf_corso( 'BNCLRA90A41G702N' ) );
uguale( 'Iscrizione a socio nello spam: errore', array( array( 'text-1' => $vv_msg_socio ) ), $vv_cf_corso( 'VRDGPP80A01H501U' ) );
uguale( 'CF sbagliato: solo il messaggio sul formato', 1, count( $vv_cf_corso( 'RSSMRA85T10A562T' ) ) );
uguale( 'Modulo soci: iscrizione dell\'anno precedente, si rinnova', array(), vv_forminator_check_cf( array(), 274, array( array( 'name' => 'text-1', 'value' => 'BNCLRA90A41G702N' ) ) ) );
$vv_msg_doppio = 'Questo codice fiscale risulta già fra i soci 2026-2027: l\'iscrizione di quest\'anno è già registrata in anagrafica. Per domande o per correggere i dati scrivi a <a href="mailto:info@wvoce.it">info@wvoce.it</a>.';
$vv_cf_socio   = static fn( $cf ) => vv_forminator_check_cf( array(), 274, array( array( 'name' => 'text-1', 'value' => $cf ) ) );
uguale( 'Modulo soci: già iscritto quest\'anno, errore con l\'email', array( array( 'text-1' => $vv_msg_doppio ) ), $vv_cf_socio( 'RSSMRA85T10A562S' ) );
uguale( 'Modulo soci: CF minuscolo con spazi riconosciuto', array( array( 'text-1' => $vv_msg_doppio ) ), $vv_cf_socio( 'rssmra85t10a562s ' ) );
uguale( 'Modulo soci: iscrizione nello spam, si iscrive', array(), $vv_cf_socio( 'VRDGPP80A01H501U' ) );
$vv_adesso = '2026-08-20 10:00:00';
uguale( 'Agosto: vale ancora l\'anno precedente', array(), $vv_cf_corso( 'BNCLRA90A41G702N' ) );
$vv_adesso = '2026-10-07 10:00:00';
Forminator_API::$entries = array();

// Controlli dei moduli: seguono il modulo scelto nel pannello, non un numero scritto nel codice
uguale( 'Moduli predefiniti: corsi e soci', array( 1617 => 'richiesto', 274 => 'unico' ), vv_forminator_moduli() );
$vv_opzioni['vv_corsi_impostazioni'] = array( 'form' => 1800 );
uguale( 'Modulo dei corsi cambiato: CF controllato sul nuovo', 1, count( vv_forminator_check_cf( array(), 1800, array( array( 'name' => 'text-1', 'value' => 'RSSMRA85T10A562T' ) ) ) ) );
uguale( 'Modulo dei corsi cambiato: socio richiesto sul nuovo', array( 1800 => 'richiesto', 274 => 'unico' ), vv_forminator_moduli() );
uguale( 'Modulo dei corsi cambiato: il vecchio non si controlla più', array(), vv_forminator_check_cf( array(), 1617, array( array( 'name' => 'text-1', 'value' => 'xxx' ) ) ) );
unset( $vv_opzioni['vv_corsi_impostazioni'] );

// Dal modulo dei soci a quello dei corsi: campi ripresi, codice nel rinvio, codice letto
$vv_inviati = array(
	array( 'name' => 'name-1', 'value' => 'Mario' ),
	array( 'name' => 'name-2', 'value' => 'Rossi' ),
	array( 'name' => 'text-1', 'value' => 'rssmra85t10a562t' ),
	array( 'name' => 'email-1', 'value' => 'mario@example.com' ),
	array( 'name' => 'phone-2', 'value' => '+39 333 1234567' ),
	array( 'name' => 'address-1', 'value' => array( 'city' => 'Pisa' ) ),
);
$vv_ok = array( 'success' => true, 'url' => 'https://wvoce.it/cosa-puoi-fare/iscrizione-corso/' );
vv_corso_tieni_campi( $vv_inviati, 1617 );
uguale( 'Modulo dei corsi: campi non tenuti', array(), vv_corso_inviati() );
uguale( 'Modulo dei corsi: risposta invariata', $vv_ok, vv_corso_rinvio( $vv_ok, 1617 ) );
uguale( 'Campi inviati restituiti a Forminator', $vv_inviati, vv_corso_tieni_campi( $vv_inviati, 274 ) );
uguale( 'Modulo dei soci: campi ripresi, CF maiuscolo', array( 'name-1' => 'Mario', 'name-2' => 'Rossi', 'text-1' => 'RSSMRA85T10A562T', 'email-1' => 'mario@example.com', 'phone-2' => '+39 333 1234567' ), vv_corso_inviati() );
uguale( 'Senza rinvio: nessun codice', array( 'success' => true ), vv_corso_rinvio( array( 'success' => true ), 274 ) );
uguale( 'Rinvio altrove: nessun codice', array( 'success' => true, 'url' => 'https://wvoce.it/grazie/' ), vv_corso_rinvio( array( 'success' => true, 'url' => 'https://wvoce.it/grazie/' ), 274 ) );
uguale( 'Invio non riuscito: nessun codice', array( 'success' => false, 'url' => $vv_ok['url'] ), vv_corso_rinvio( array( 'success' => false, 'url' => $vv_ok['url'] ), 274 ) );
$vv_rinvio = vv_corso_rinvio( $vv_ok, 274 );
$vv_codice = preg_match( '/\?vv=([0-9a-f]{32})$/', $vv_rinvio['url'], $vv_m ) ? $vv_m[1] : '';
uguale( 'Rinvio alla pagina dei corsi: codice nell\'indirizzo', 32, strlen( $vv_codice ) );
uguale( 'Nell\'indirizzo solo il codice, niente dati', false, strpos( $vv_rinvio['url'], 'RSSMRA' ) );
uguale( 'Codice valido: dati ripresi', 'RSSMRA85T10A562T', vv_corso_dati( $vv_codice )['text-1'] ?? '' );
uguale( 'Pagina dei corsi senza la barra finale', true, vv_corso_e_pagina( 'https://wvoce.it/cosa-puoi-fare/iscrizione-corso' ) );
uguale( 'Codice sconosciuto', null, vv_corso_dati( str_repeat( 'a', 32 ) ) );
uguale( 'Codice malformato', null, vv_corso_dati( '../x' ) );
uguale( 'Dopo il rinvio i campi non restano per l\'invio seguente', array( 'success' => true, 'url' => $vv_ok['url'] ), vv_corso_rinvio( $vv_ok, 274 ) );

// Impostazioni dal pannello
$vv_opzioni = array();
$c = vv_sheets_config( 'soci' );
uguale( 'Soci: predefiniti', array( '', '', 274, '2026-09-01', 'VV_SOCI' ), array( $c['url'], $c['token'], $c['form'], $c['dal'], $c['prefisso'] ) );
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

// Partenza dalle nuove: la prima volta si fissa l'iscrizione più recente
$vv_opzioni = array();
Forminator_API::$entries = array( (object) array( 'entry_id' => 7 ), (object) array( 'entry_id' => 31, 'is_spam' => 1 ), (object) array( 'entry_id' => 12 ) );
uguale( 'Partenza dalle nuove: iscrizione più recente', 31, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Partenza dalle nuove: salvata', 31, $vv_opzioni['vv_corsi_inizio'] );
Forminator_API::$entries[] = (object) array( 'entry_id' => 32, 'date_created_sql' => '2026-10-06 09:00:00' );
uguale( 'Partenza dalle nuove: resta fissa', 31, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Partenza dalle nuove: parte solo la nuova', array( 32 ), array_map( static fn( $e ) => $e->entry_id, vv_sheets_iscrizioni( 'corsi' )['da_inviare'] ) );
$vv_opzioni['vv_corsi_ultimo'] = 32;
uguale( 'Dopo un invio conta l\'ultima confermata', 32, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Soci: si parte da zero', 0, vv_sheets_ultimo( 'soci' ) );

// Modulo dei corsi cambiato (anno nuovo): si parte dalla prima iscrizione del modulo nuovo, comprese
// quelle arrivate prima del cambio nel pannello
$vv_opzioni = array( 'vv_corsi_ultimo' => 32, 'vv_corsi_inizio' => 31 );
vv_sheets_salva_impostazioni( 'corsi', array( 'url' => 'https://script.google.com/macros/s/abc/exec', 'token' => 't', 'form' => '1800' ) );
Forminator_API::$entries = array( (object) array( 'entry_id' => 3, 'date_created_sql' => '2026-08-20 10:00:00' ), (object) array( 'entry_id' => 5, 'date_created_sql' => '2026-08-21 10:00:00' ) );
uguale( 'Modulo cambiato: partono tutte le iscrizioni del nuovo', array( 3, 5 ), array_map( static fn( $e ) => $e->entry_id, vv_sheets_iscrizioni( 'corsi' )['da_inviare'] ) );

// Iscrizioni passate oltre perché bozze o spam: ripartono quando tornano valide
$vv_opzioni = array( 'vv_soci_ultimo' => 10 );
Forminator_API::$entries = array(
	(object) array( 'form_id' => 274, 'entry_id' => 8, 'date_created_sql' => '2026-09-10 10:00:00', 'draft_id' => 'abc' ),
	(object) array( 'form_id' => 274, 'entry_id' => 11, 'date_created_sql' => '2026-09-12 10:00:00' ),
);
uguale( 'Bozza sotto il punto raggiunto: in attesa', array( 11 ), array_map( static fn( $e ) => $e->entry_id, vv_sheets_iscrizioni( 'soci' )['da_inviare'] ) );
uguale( 'Bozza sotto il punto raggiunto: annotata', array( 8 ), vv_sheets_iscrizioni( 'soci' )['sospese'] );
$vv_opzioni['vv_soci_sospese'] = array( 8 );
Forminator_API::$entries[0]->draft_id = '';
uguale( 'Bozza completata: parte', array( 8, 11 ), array_map( static fn( $e ) => $e->entry_id, vv_sheets_iscrizioni( 'soci' )['da_inviare'] ) );
// Invio: la ripresa non fa tornare indietro il punto raggiunto; se il foglio non risponde resta annotata
$vv_opzioni['vv_soci_impostazioni'] = array( 'url' => 'https://script.google.com/macros/s/abc/exec', 'token' => 't' );
$vv_risposta = array( 'ok' => false, 'errore' => 'foglio occupato' );
vv_sheets_sync( 'soci' );
uguale( 'Invio non riuscito: la ripresa resta annotata', array( 8 ), $vv_opzioni['vv_soci_sospese'] );
uguale( 'Invio non riuscito: punto raggiunto fermo', 10, $vv_opzioni['vv_soci_ultimo'] );
$vv_risposta = array( 'ok' => true, 'rif' => array( 'F274-8', 'F274-11' ) );
uguale( 'Invio riuscito: due iscrizioni', 2, vv_sheets_sync( 'soci' )['inviate'] );
uguale( 'Invio riuscito: punto raggiunto avanti, mai indietro', 11, $vv_opzioni['vv_soci_ultimo'] );
uguale( 'Invio riuscito: nessuna sospesa', array(), $vv_opzioni['vv_soci_sospese'] );
Forminator_API::$entries = array();

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

// Etichette degli eventi su più giorni: i giorni fino a un mese, poi i mesi
$vv_giorni = static fn( $da, $a ) => vv_date_range_label( strtotime( $da ), strtotime( $a . ' 23:59:59' ) );
uguale( 'Evento di tre giorni', '3–5 ottobre 2026', $vv_giorni( '2026-10-03', '2026-10-05' ) );
uguale( 'Evento a cavallo di due mesi', '28 settembre – 3 ottobre 2026', $vv_giorni( '2026-09-28', '2026-10-03' ) );
uguale( 'Evento a cavallo dell\'anno', '30 dicembre 2026 – 2 gennaio 2027', $vv_giorni( '2026-12-30', '2027-01-02' ) );
uguale( 'Mese intero', '1–31 ottobre 2026', $vv_giorni( '2026-10-01', '2026-10-31' ) );
uguale( 'Corso di una stagione', 'ott–dic 2026', $vv_giorni( '2026-10-01', '2026-12-15' ) );
uguale( 'Corso su due anni', 'ott 2026 – mag 2027', $vv_giorni( '2026-10-01', '2027-05-31' ) );

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

// Ordine degli eventi: il caso della home del 10 ottobre 2026
$vv_ev   = static fn( $nome, $da, $a ) => array( $nome, strtotime( $da ), strtotime( $a . ' 23:59:59' ) );
$vv_oggi = strtotime( '2026-10-10 12:00' );
uguale(
	'Appuntamento di domani davanti ai corsi già cominciati',
	array( 'Festival in corso', 'Internet Festival', 'Laboratorio di gennaio', 'Abitare la scena', 'Ti darò voce', 'Corsi 2026-2027', 'Pinocchio' ),
	array_column(
		vv_ordina_eventi(
			array(
				$vv_ev( 'Corsi 2026-2027', '2026-10-01', '2027-06-30' ),
				$vv_ev( 'Ti darò voce', '2026-10-06', '2026-12-15' ),
				$vv_ev( 'Abitare la scena', '2026-10-08', '2026-12-15' ),
				$vv_ev( 'Laboratorio di gennaio', '2027-01-10', '2027-04-30' ),
				$vv_ev( 'Internet Festival', '2026-10-11', '2026-10-11' ),
				$vv_ev( 'Festival in corso', '2026-10-09', '2026-10-12' ),
				$vv_ev( 'Pinocchio', '2026-06-01', '2026-09-30' ),
			),
			$vv_oggi
		),
		0
	)
);

// Emoji incollate da Facebook e Gmail, immagini delle gallerie
uguale( 'Emoji di Facebook diventa testo', 'Prenota 👉 qui', vv_emoji_esterne( 'Prenota <img decoding="async" data-src="https://static.xx.fbcdn.net/images/emoji.php/v9/t51/1/16/1f449.png" alt="👉" width="16" height="16" src="data:image/svg+xml;base64,AAA" class="lazyload" /> qui' ) );
uguale( 'Emoji dal proxy di Gmail diventa testo', 'Teatro 🎭', vv_emoji_esterne( 'Teatro <img class="CToWUd" src="https://ci3.googleusercontent.com/meips/ADKq=s0-d-e1-ft#https://static.xx.fbcdn.net/images/emoji.php/v9/t4d/2/16/1f3ad.png" alt="🎭" width="16" height="16">' ) );
$vv_grande = '<img src="https://scontent.xx.fbcdn.net/v/foto.jpg" alt="Foto" width="800" height="600">';
uguale( 'Foto esterna grande intatta', $vv_grande, vv_emoji_esterne( $vv_grande ) );
uguale( 'Contenuto senza immagini esterne invariato', '<p>Ciao <img src="/a.png" alt="a"></p>', vv_emoji_esterne( '<p>Ciao <img src="/a.png" alt="a"></p>' ) );
uguale( 'Galleria: link alla pagina dell\'allegato diventa link al file', 'file', vv_gallery_link_file( array( 'link' => '' ) )['link'] );
uguale( 'Galleria senza link resta senza', 'none', vv_gallery_link_file( array( 'link' => 'none' ) )['link'] );

// Descrizione per motori di ricerca e social
uguale( 'Descrizione corta ripulita', 'Laboratorio di lettura & teatro.', vv_condivisione_taglia( "<p>Laboratorio  di lettura &amp; teatro.</p>\n[vv_cards]" ) );
uguale( 'Descrizione lunga tagliata sulla parola', 'Uno due tre quattro…', vv_condivisione_taglia( 'Uno due tre quattro cinque sei', 22 ) );

// Dati strutturati: sito, associazione, eventi
$vv_sito = vv_ds_sito();
uguale( 'Sito: nome dalle impostazioni', 'VivaVoce', $vv_sito['name'] );
uguale( 'Sito: nomi alternativi, ragione sociale e dominio', array( 'Associazione VivaVoce APS', 'wvoce' ), $vv_sito['alternateName'] );
$vv_ass = vv_ds_associazione();
uguale( 'Associazione: sede completa', array( '@type' => 'PostalAddress', 'streetAddress' => 'Via G. Mazzini 112', 'postalCode' => '56125', 'addressLocality' => 'Pisa', 'addressCountry' => 'IT' ), $vv_ass['address'] );
uguale( 'Associazione: partita IVA con il prefisso del paese', 'IT02441190507', $vv_ass['vatID'] );
uguale( 'Associazione: profili social', 2, count( $vv_ass['sameAs'] ) );
uguale( 'Associazione: senza logo né immagine nessun campo vuoto', array( false, false ), array( array_key_exists( 'logo', $vv_ass ), array_key_exists( 'image', $vv_ass ) ) );
uguale( 'Associazione: immagine dell\'anteprima della home', 'https://wvoce.it/icona.png', vv_ds_associazione( 'https://wvoce.it/icona.png' )['image'] );
$vv_ev = array( 'titolo' => 'Peter Pan', 'url' => 'https://wvoce.it/peter-pan/', 'inizio' => '2026-10-14', 'fine' => '2026-12-16', 'luogo' => 'Spazio Mumu, via Lenin 155/b, San Giuliano Terme' );
$vv_evento = vv_ds_evento( $vv_ev );
uguale( 'Evento: date', array( '2026-10-14', '2026-12-16' ), array( $vv_evento['startDate'], $vv_evento['endDate'] ) );
uguale( 'Evento: nome del luogo prima della virgola', 'Spazio Mumu', $vv_evento['location']['name'] );
uguale( 'Evento: indirizzo del luogo intero', 'Spazio Mumu, via Lenin 155/b, San Giuliano Terme', $vv_evento['location']['address'] );
uguale( 'Evento: senza descrizione né immagine, campi assenti', array( false, false ), array( isset( $vv_evento['description'] ), isset( $vv_evento['image'] ) ) );
uguale( 'Evento: fine prima dell\'inizio, dura un giorno', '2026-10-14', vv_ds_evento( array( 'fine' => '2026-10-01' ) + $vv_ev )['endDate'] );
uguale( 'Evento: senza fine, dura un giorno', '2026-10-14', vv_ds_evento( array( 'fine' => '' ) + $vv_ev )['endDate'] );
uguale( 'Evento: senza luogo niente evento', array(), vv_ds_evento( array( 'luogo' => ' ' ) + $vv_ev ) );
uguale( 'Evento: data non valida niente evento', array(), vv_ds_evento( array( 'inizio' => '14/10/2026' ) + $vv_ev ) );

// Recupera i dati dell'ultima iscrizione: email all'indirizzo dell'ultima iscrizione, a video sempre lo stesso
$vv_adesso    = '2026-10-07 10:00:00';
$vv_transient = array();
$vv_email     = array();
$vv_iscritto  = static function ( $id, $data, $cf, $email, $extra = array() ) {
	return (object) ( array(
		'entry_id'         => $id,
		'form_id'          => 274,
		'date_created_sql' => $data,
		'meta_data'        => array(
			'text-1'    => array( 'value' => $cf ),
			'email-1'   => array( 'value' => $email ),
			'name-1'    => array( 'value' => 'Laura' ),
			'radio-6'   => array( 'value' => 'individuo' ),
			'address-1' => array( 'value' => array( 'street_address' => 'Via Roma 1', 'city' => 'Pisa', 'zip' => '56125' ) ),
			'upload-1'  => array( 'value' => array( 'file' => array( 'file_url' => 'https://wvoce.it/ricevuta.pdf' ) ) ),
			'consent-1' => array( 'value' => 'checked' ),
			'radio-4'   => array( 'value' => 'autorizzo' ),
		),
	) + $extra );
};
Forminator_API::$entries = array(
	$vv_iscritto( 40, '2024-10-01 10:00:00', 'BNCLRA90A41G702N', 'vecchia@example.com' ),
	$vv_iscritto( 41, '2025-09-15 10:00:00', 'bnclra90a41g702n ', 'laura@example.com' ),
	$vv_iscritto( 42, '2025-11-15 10:00:00', 'BNCLRA90A41G702N', 'spam@example.com', array( 'is_spam' => 1 ) ),
	$vv_iscritto( 43, '2026-09-10 10:00:00', 'RSSMRA85T10A562S', 'mario@example.com' ),
	$vv_iscritto( 44, '2022-10-01 10:00:00', 'VRDGPP80A01H501U', 'troppo@example.com' ),
);
uguale( 'Recupera: l\'iscrizione più recente, non spam', 41, vv_socio_ultima_iscrizione( 'BNCLRA90A41G702N', $vv_adesso )->entry_id ?? 0 );
uguale( 'Recupera: oltre tre anni sociali non si cerca', null, vv_socio_ultima_iscrizione( 'VRDGPP80A01H501U', $vv_adesso ) );
$vv_ripresi = vv_socio_dati_recuperati( Forminator_API::$entries[1] );
uguale( 'Recupera: anagrafica, CF maiuscolo, indirizzo per parti', array( 'radio-6' => 'individuo', 'name-1' => 'Laura', 'text-1' => 'BNCLRA90A41G702N', 'email-1' => 'laura@example.com', 'address-1-street_address' => 'Via Roma 1', 'address-1-city' => 'Pisa', 'address-1-zip' => '56125' ), $vv_ripresi );
uguale( 'Recupera: ricevuta, accettazioni e autorizzazioni escluse', array(), array_intersect_key( $vv_ripresi, array_flip( array( 'upload-1', 'consent-1', 'radio-4' ) ) ) );
uguale( 'Recupera: CF sbagliato', 'formato', vv_socio_richiesta( 'BNCLRA90A41G702X', '1.2.3.4', $vv_adesso ) );
uguale( 'Recupera: CF sbagliato, nessuna email', array(), $vv_email );
uguale( 'Recupera: CF sconosciuto, nessuna email', array( 'nessuno', array() ), array( vv_socio_richiesta( 'VRDGPP80A01H501U', '1.2.3.4', $vv_adesso ), $vv_email ) );
uguale( 'Recupera: socio dell\'anno scorso, email con il link', 'link', vv_socio_richiesta( 'BNCLRA90A41G702N', '1.2.3.4', $vv_adesso ) );
uguale( 'Recupera: all\'indirizzo dell\'ultima iscrizione', 'laura@example.com', $vv_email[0]['a'] ?? '' );
$vv_codice = preg_match( '/vv-socio=([0-9a-f]{32})/', $vv_email[0]['testo'] ?? '', $vv_m ) ? $vv_m[1] : '';
uguale( 'Recupera: nel link solo il codice', array( 32, false ), array( strlen( $vv_codice ), strpos( $vv_email[0]['testo'] ?? '', 'Via Roma' ) ) );
uguale( 'Recupera: il link riapre il modulo con i dati', $vv_ripresi, vv_socio_dati( $vv_codice ) );
uguale( 'Recupera: codice sconosciuto o malformato', array( null, null ), array( vv_socio_dati( str_repeat( 'b', 32 ) ), vv_socio_dati( '../x' ) ) );
uguale( 'Recupera: seconda richiesta entro 5 minuti, nessuna email', array( 'attesa', 1 ), array( vv_socio_richiesta( 'BNCLRA90A41G702N', '1.2.3.4', $vv_adesso ), count( $vv_email ) ) );
uguale( 'Recupera: già iscritto quest\'anno, email senza link', 'iscritto', vv_socio_richiesta( 'RSSMRA85T10A562S', '1.2.3.4', $vv_adesso ) );
uguale( 'Recupera: email "già registrata" senza link', array( 'mario@example.com', false ), array( $vv_email[1]['a'] ?? '', strpos( $vv_email[1]['testo'] ?? '', 'vv-socio=' ) ) );
Forminator_API::$entries[1]->is_spam = 1;
uguale( 'Recupera: iscrizione spostata nello spam, il link non vale più', null, vv_socio_dati( $vv_codice ) );
uguale( 'Recupera: quinta richiesta dallo stesso IP, ancora ammessa', 'nessuno', vv_socio_richiesta( 'VRDGPP80A01H501U', '1.2.3.4', $vv_adesso ) );
uguale( 'Recupera: sesta richiesta dallo stesso IP, troppe', 'troppe', vv_socio_richiesta( 'VRDGPP80A01H501U', '1.2.3.4', $vv_adesso ) );
uguale( 'Recupera: un altro IP può ancora', 'nessuno', vv_socio_richiesta( 'VRDGPP80A01H501U', '5.6.7.8', $vv_adesso ) );
uguale( 'Recupera: stesso messaggio a video con o senza socio', vv_socio_messaggio( 'link' ), vv_socio_messaggio( 'nessuno' ) );
uguale( 'Recupera: stesso messaggio anche se già iscritto o in attesa', array( vv_socio_messaggio( 'link' ), vv_socio_messaggio( 'link' ) ), array( vv_socio_messaggio( 'iscritto' ), vv_socio_messaggio( 'attesa' ) ) );
$vv_admin = true;
uguale( 'Recupera: agli amministratori l\'esito vero', array( true, true ), array( false !== strpos( vv_socio_messaggio( 'attesa' ), '5 minuti' ), vv_socio_messaggio( 'link' ) !== vv_socio_messaggio( 'nessuno' ) ) );
$vv_admin = false;
Forminator_API::$entries = array();
$vv_transient = array();

// Email con il marchio: modello HTML, versione in solo testo, Message-ID del dominio
$vv_mail = vv_email_avvolgi( array( 'to' => 'a@example.com', 'subject' => 'Prova', 'message' => "Ciao <Laura>,\n\napri https://wvoce.it/x/?a=1\n\nVivaVoce", 'headers' => '' ) );
uguale( 'Email di testo: documento HTML con lingua e titolo', array( true, true ), array( false !== strpos( $vv_mail['message'], '<html lang="it"' ), false !== strpos( $vv_mail['message'], '<title>Prova</title>' ) ) );
uguale( 'Email di testo: testo protetto, link cliccabile', array( true, true ), array( false !== strpos( $vv_mail['message'], 'Ciao &lt;Laura&gt;' ), false !== strpos( $vv_mail['message'], 'href="https://wvoce.it/x/?a=1' ) ) );
uguale( 'Email di testo: niente testo nascosto', false, strpos( $vv_mail['message'], 'display:none' ) );
uguale( 'Email di testo: intestazione HTML in UTF-8', array( 'Content-Type: text/html; charset=UTF-8' ), $vv_mail['headers'] );
uguale( 'Email di testo: versione in solo testo con il piè di pagina', true, 0 === strpos( vv_email_testo(), "Ciao <Laura>,\n\napri https://wvoce.it/x/?a=1" ) && false !== strpos( vv_email_testo(), 'Via G. Mazzini 112' ) );
$vv_pm = new stdClass();
vv_email_phpmailer( $vv_pm );
uguale( 'PHPMailer: testo alternativo e dominio del sito nel Message-ID', array( true, 'wvoce.it' ), array( false !== strpos( $vv_pm->AltBody, 'Ciao <Laura>' ), $vv_pm->Hostname ) );
vv_email_phpmailer( $vv_pm );
uguale( 'PHPMailer: il testo non passa all\'email seguente', '', $vv_pm->AltBody );
$vv_mail2 = vv_email_avvolgi( array( 'subject' => 'Link', 'message' => "Apri questo link:\n\nhttps://wvoce.it/y/\n\nVivaVoce", 'headers' => '' ) );
uguale( 'Email di testo: indirizzo da solo diventa il pulsante "Apri il link"', array( true, false ), array( false !== strpos( $vv_mail2['message'], '>Apri il link</a>' ), strpos( $vv_mail2['message'], '>https://wvoce.it/y/</a>' ) ) );
uguale( 'Email di testo: nella versione in solo testo l\'indirizzo resta', true, false !== strpos( vv_email_testo(), 'https://wvoce.it/y/' ) );
$vv_mail = vv_email_avvolgi( array( 'subject' => 'Nuova iscrizione', 'message' => '<p>Nome: <strong>Laura</strong></p><p><a href="https://wvoce.it/">Sito</a></p>', 'headers' => array( 'From: VivaVoce <info@wvoce.it>', 'Content-Type: text/html; charset=UTF-8' ) ) );
uguale( 'Email HTML (Forminator): frammento dentro il modello', true, false !== strpos( $vv_mail['message'], '<strong>Laura</strong>' ) && false !== strpos( $vv_mail['message'], 'logo-email.png' ) );
uguale( 'Email HTML: intestazioni tenute, un solo Content-Type', array( 'From: VivaVoce <info@wvoce.it>', 'Content-Type: text/html; charset=UTF-8' ), $vv_mail['headers'] );
uguale( 'Email HTML: testo ricavato con i link', "Nome: Laura\nSito (https://wvoce.it/)", strstr( vv_email_testo(), "\n\n--", true ) );
$vv_doc = array( 'subject' => 'X', 'message' => '<html><body>già impaginata</body></html>', 'headers' => '' );
uguale( 'Email già impaginata: invariata, senza testo alternativo', array( $vv_doc, '' ), array( vv_email_avvolgi( $vv_doc ), vv_email_testo() ) );

// Lettere decorative incollate dai social
uguale( 'Lettere decorative', '<strong class="vv-plain">Voce</strong> e testo', vv_plain_letters( '𝕍𝕠𝕔𝕖 e testo' ) );
uguale( 'Lettere decorative senza grassetto', 'Bold', vv_plain_letters( '𝐁𝐨𝐥𝐝', false ) );
uguale( 'Testo normale invariato', 'Ciao', vv_plain_letters( 'Ciao' ) );

echo $vv_falliti ? "\n{$vv_falliti} su {$vv_totale} controlli falliti.\n" : "{$vv_totale} controlli superati.\n";
exit( $vv_falliti ? 1 : 0 );
