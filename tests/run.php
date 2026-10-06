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

// Foglio dei corsi: tab di un corso
uguale( 'Chiave di un nome', 'gioco-del-teatro-3-6-anni', vv_corsi_chiave( 'Gioco del Teatro (3-6 anni)' ) );
uguale( 'Chiave con accenti', 'attivita-e-perche', vv_corsi_chiave( 'Attività e perché' ) );
uguale( 'Corso uguale al tab', 'tecnica-attoriale-2026', vv_corsi_tab( 'tecnica-attoriale-2026' ) );
uguale( 'Corso senza anno', 'lettura-ad-alta-voce-2026', vv_corsi_tab( 'Lettura ad alta voce' ) );
uguale( 'Corso con dettagli', 'gioco-del-teatro-2206', vv_corsi_tab( 'Gioco del teatro (3-6 anni)' ) );
uguale( 'Corso con lo stesso anno', 'messa-in-scena-2027', vv_corsi_tab( 'Messa in scena 2027' ) );
uguale( 'Corso con un altro anno', 'Da smistare', vv_corsi_tab( 'Messa in scena 2028' ) );
uguale( 'Corso sconosciuto', 'Da smistare', vv_corsi_tab( 'Dizione' ) );
uguale( 'Corso vuoto', 'Da smistare', vv_corsi_tab( '' ) );

// Foglio dei corsi: righe di un'iscrizione
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
$vv_filtri['vv_corsi_campo'] = static fn() => 'checkbox-1';
$righe = vv_corsi_righe( $vv_corsi_entry( 'Tecnica attoriale' ) );
uguale( 'Corso singolo: una riga', 1, count( $righe ) );
uguale( 'Corso singolo: tab', 'tecnica-attoriale-2026', $righe[0]['_tab'] );
uguale( 'Corso singolo: Rif.', 'F1617-40', $righe[0]['Rif.'] );
uguale( 'Corso singolo: codice fiscale', 'RSSMRA85T10A562S', $righe[0]['Codice Fiscale'] );
uguale( 'Corso singolo: campo vuoto, colonna vuota', '', $righe[0]['Nome'] );
uguale( 'Corso singolo: chiavi', array_merge( array( '_tab', 'Rif.' ), array_keys( vv_corsi_colonne() ), array( 'Corso' ) ), array_keys( $righe[0] ) );
$righe = vv_corsi_righe( $vv_corsi_entry( array( 'Lettura ad alta voce', 'Messa in scena' ) ) );
uguale( 'Due corsi: due righe', array( 'lettura-ad-alta-voce-2026', 'messa-in-scena-2027' ), array_column( $righe, '_tab' ) );
uguale( 'Due corsi: stesso Rif.', array( 'F1617-40', 'F1617-40' ), array_column( $righe, 'Rif.' ) );
$righe = vv_corsi_righe( $vv_corsi_entry( array( 'Dizione', 'Canto', 'Dizione' ) ) );
uguale( 'Corsi sconosciuti: Da smistare, uno per corso', array( 'Da smistare', 'Da smistare' ), array_column( $righe, '_tab' ) );
uguale( 'Corsi sconosciuti: colonna Corso', array( 'Dizione', 'Canto' ), array_column( $righe, 'Corso' ) );
$vv_filtri['vv_corsi_tabs'] = static fn() => array( 'Canto' => 'tecnica-attoriale-2026' );
uguale( 'Tabella dei tab prima del confronto', 'tecnica-attoriale-2026', vv_corsi_tab( 'Canto' ) );
uguale( 'Tabella dei tab, valore scritto in altro modo', 'tecnica-attoriale-2026', vv_corsi_tab( 'CANTO' ) );
unset( $vv_filtri['vv_corsi_tabs'] );
uguale( 'Campo del corso indicato: requisito soddisfatto', '', vv_corsi_requisito() );
unset( $vv_filtri['vv_corsi_campo'] );

// Foglio dei corsi: le opzioni reali del modulo 1617
$vv_opzioni_1617 = array_keys( vv_corsi_tabs() );
uguale( 'Modulo 1617: campo del corso', 'checkbox-1', vv_corsi_campo() );
uguale( 'Modulo 1617: ogni opzione nel suo tab', vv_corsi_tab_nomi(), array_map( 'vv_corsi_tab', $vv_opzioni_1617 ) );
$righe = vv_corsi_righe( $vv_corsi_entry( array( $vv_opzioni_1617[0], $vv_opzioni_1617[3] ) ) );
uguale( 'Modulo 1617: due caselle, due tab', array( 'lettura-ad-alta-voce-2026', 'gioco-del-teatro-2206' ), array_column( $righe, '_tab' ) );
$righe = vv_corsi_righe( $vv_corsi_entry( $vv_opzioni_1617[2] . ', ' . $vv_opzioni_1617[1] ) );
uguale( 'Modulo 1617: caselle unite in un testo', array( 'messa-in-scena-2027', 'tecnica-attoriale-2026' ), array_column( $righe, '_tab' ) );
uguale( 'Modulo 1617: opzione riscritta', 'Da smistare', vv_corsi_tab( 'Adulti: Laboratorio di dizione, 2027' ) );
uguale(
	'Modulo 1617: colonne',
	array( 'name-1', 'name-2', 'text-1', 'email-1', 'phone-2', 'upload-1', 'consent-1', 'consent-2', 'consent-3', 'textarea-1' ),
	array_values( vv_corsi_colonne() )
);

$vv_filtri['vv_corsi_campo'] = static fn() => '';
uguale( 'Senza campo del corso: fermo', 'manca il campo del corso (filtro vv_corsi_campo)', vv_corsi_requisito() );
uguale( 'Senza campo del corso: riga in Da smistare', 'Da smistare', vv_corsi_righe( $vv_corsi_entry( 'Tecnica attoriale' ) )[0]['_tab'] );
unset( $vv_filtri['vv_corsi_campo'] );

// Collegamenti: i soci restano come prima
$c = vv_sheets_config( 'soci' );
uguale( 'Soci: opzioni invariate', array( 'vv_sheets_ultimo', 'vv_sheets_stato', '' ), array( $c['ultimo'], $c['stato'], $c['inizio'] ) );
uguale( 'Soci: modulo e data', array( 274, '2026-09-01' ), array( $c['form'], $c['dal'] ) );
uguale( 'Soci: una riga per iscrizione', array( vv_sheets_riga( $entry ) ), vv_sheets_righe_soci( $entry ) );
$c = vv_sheets_config( 'corsi' );
uguale( 'Corsi: modulo, opzioni, partenza dalle nuove', array( 1617, 'vv_sheets_corsi_ultimo', 'vv_sheets_corsi_stato', 'vv_sheets_corsi_inizio', '' ), array( $c['form'], $c['ultimo'], $c['stato'], $c['inizio'], $c['dal'] ) );

// Partenza dalle nuove: la prima volta si fissa l'iscrizione più recente
class Forminator_API {
	public static $entries = array();
	public static function get_entries( $form ) {
		return self::$entries;
	}
}
$vv_opzioni = array();
Forminator_API::$entries = array( (object) array( 'entry_id' => 7 ), (object) array( 'entry_id' => 31, 'is_spam' => 1 ), (object) array( 'entry_id' => 12 ) );
uguale( 'Partenza dalle nuove: iscrizione più recente', 31, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Partenza dalle nuove: salvata', 31, $vv_opzioni['vv_sheets_corsi_inizio'] );
Forminator_API::$entries[] = (object) array( 'entry_id' => 32, 'date_created_sql' => '2026-10-06 09:00:00' );
uguale( 'Partenza dalle nuove: resta fissa', 31, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Partenza dalle nuove: parte solo la nuova', array( 32 ), array_map( static fn( $e ) => $e->entry_id, vv_sheets_da_inviare( 'corsi' ) ) );
$vv_opzioni['vv_sheets_corsi_ultimo'] = 32;
uguale( 'Dopo un invio conta l\'ultima confermata', 32, vv_sheets_ultimo( 'corsi' ) );
uguale( 'Soci: si parte da zero', 0, vv_sheets_ultimo( 'soci' ) );

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
