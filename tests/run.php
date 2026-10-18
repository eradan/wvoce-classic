<?php
/**
 * Test delle funzioni del tema che non hanno bisogno di WordPress (date e ordine degli eventi,
 * meta_query, contenuti, dati strutturati, email, passaggio delle iscrizioni al plugin wvoce-iscrizioni).
 * Si lanciano con: php tests/run.php
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
require dirname( __DIR__ ) . '/inc/iscrizioni.php';

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

// Passaggio delle iscrizioni al plugin: configurazione di VivaVoce e stato del tema
$vv_opzioni = array(
	'vv_soci_impostazioni'  => array( 'url' => 'https://script.google.com/macros/s/s/exec', 'token' => 'ts', 'form' => 274, 'dal' => '2026-09-01', 'foglio' => 'idsoci' ),
	'vv_corsi_impostazioni' => array( 'url' => 'https://script.google.com/macros/s/c/exec', 'token' => 'tc', 'form' => 1617, 'foglio' => 'idcorsi' ),
	'vv_soci_ultimo'        => 389,
	'vv_corsi_ultimo'       => 1650,
	'vv_corsi_inizio'       => 1600,
	'vv_corsi_sospese'      => array( 1640 ),
);
$vv_conf    = vv_iscrizioni_configurazione();
uguale( 'Passaggio: collegamenti con indirizzo, codice, foglio, modulo', array( 'ts', 'idsoci', 274, '2026-09-01', 'tc', 1617, '' ), array( $vv_conf['soci']['token'], $vv_conf['soci']['foglio'], $vv_conf['soci']['form'], $vv_conf['soci']['dal'], $vv_conf['corsi']['token'], $vv_conf['corsi']['form'], $vv_conf['corsi']['dal'] ) );
uguale( 'Passaggio: stato dell\'invio, nessuna voce inventata', array( 'soci' => array( 'ultimo' => 389 ), 'corsi' => array( 'ultimo' => 1650, 'sospese' => array( 1640 ), 'inizio' => 1600 ) ), $vv_conf['stato'] );
uguale( 'Passaggio: intestazioni del foglio dei soci di oggi', array( 21, array( 'address-2', 'state' ) ), array( count( $vv_conf['soci']['colonne'] ), $vv_conf['soci']['colonne']['ADDRESS - PROVINCIA DI NASCITA'] ) );
uguale( 'Passaggio: tab e pagine di VivaVoce', array( 'DomandeAmmissione ', 'configurazioni', 'Da smistare', 9, 'https://wvoce.it/cosa-puoi-fare/diventa-socio/' ), array( $vv_conf['soci']['tab_prefisso'], $vv_conf['corsi']['tab_configurazioni'], $vv_conf['corsi']['tab_riserva'], $vv_conf['generali']['mese_inizio'], $vv_conf['generali']['pagina_soci'] ) );
vv_iscrizioni_passaggio();
uguale( 'Passaggio senza il plugin: niente da fare', false, isset( $vv_opzioni['vv_iscrizioni_passate'] ) );
$vv_opzioni = array();

// Lettere decorative incollate dai social
uguale( 'Lettere decorative', '<strong class="vv-plain">Voce</strong> e testo', vv_plain_letters( '𝕍𝕠𝕔𝕖 e testo' ) );
uguale( 'Lettere decorative senza grassetto', 'Bold', vv_plain_letters( '𝐁𝐨𝐥𝐝', false ) );
uguale( 'Testo normale invariato', 'Ciao', vv_plain_letters( 'Ciao' ) );

echo $vv_falliti ? "\n{$vv_falliti} su {$vv_totale} controlli falliti.\n" : "{$vv_totale} controlli superati.\n";
exit( $vv_falliti ? 1 : 0 );
