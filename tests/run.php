<?php
/**
 * Test delle funzioni del tema che non hanno bisogno di WordPress (date e ordine degli eventi,
 * meta_query, contenuti, anteprime, dati strutturati, email).
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
function apply_filters( $hook, $value ) {
	return $value;
}
function strip_shortcodes( $text ) {
	return preg_replace( '/\[[^\]]*\]/', '', (string) $text );
}
function wp_strip_all_tags( $text ) {
	return trim( strip_tags( (string) $text ) );
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}
function esc_url( $url ) {
	return (string) $url;
}
function home_url( $path = '' ) {
	return 'https://wvoce.it' . $path;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function get_theme_mod( $name, $default = false ) {
	return $default;
}
define( 'VV_URI', 'https://wvoce.it/wp-content/themes/wvoce-classic' );
define( 'DAY_IN_SECONDS', 86400 );
function sanitize_title( $title ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $title ) ), '-' );
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
function get_bloginfo( $show ) {
	return array( 'name' => 'VivaVoce', 'description' => 'Lettura ad alta voce, teatro e creatività a Pisa' )[ $show ] ?? '';
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
require dirname( __DIR__ ) . '/inc/eventi.php';
require dirname( __DIR__ ) . '/inc/cards.php';
require dirname( __DIR__ ) . '/inc/contenuti.php';
require dirname( __DIR__ ) . '/inc/condivisione.php';
require dirname( __DIR__ ) . '/inc/dati-strutturati.php';
require dirname( __DIR__ ) . '/inc/email.php';

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
uguale( 'Data valida', '2026-10-06', vv_pulisci_data( ' 2026-10-06 ' ) );
uguale( 'Data in altro formato', '', vv_pulisci_data( '06/10/2026' ) );
uguale( 'Data inesistente: mese 13', '', vv_pulisci_data( '2026-13-01' ) );
uguale( 'Data inesistente: 30 febbraio', '', vv_pulisci_data( '2026-02-30' ) );
uguale( 'Date evento: fine prima dell\'inizio diventa l\'inizio', array( '2026-10-14', '2026-10-14' ), vv_date_evento( '2026-10-14', '2026-10-01' ) );
uguale( 'Date evento: senza fine, un giorno', array( '2026-10-14', '2026-10-14' ), vv_date_evento( '2026-10-14', '' ) );
uguale( 'Date evento: inizio inesistente, niente date', array( '', '2026-10-20' ), vv_date_evento( '2026-02-30', '2026-10-20' ) );
uguale( 'Slug', 'eventi,corsi-e-laboratori', vv_pulisci_slug( 'Eventi, Corsi e laboratori,,' ) );

// Etichette degli eventi su più giorni: i giorni fino a un mese, poi i mesi
$vv_giorni = static fn( $da, $a ) => vv_etichetta_periodo( strtotime( $da ), strtotime( $a . ' 23:59:59' ) );
uguale( 'Evento di tre giorni', '3–5 ottobre 2026', $vv_giorni( '2026-10-03', '2026-10-05' ) );
uguale( 'Evento a cavallo di due mesi', '28 settembre – 3 ottobre 2026', $vv_giorni( '2026-09-28', '2026-10-03' ) );
uguale( 'Evento a cavallo dell\'anno', '30 dicembre 2026 – 2 gennaio 2027', $vv_giorni( '2026-12-30', '2027-01-02' ) );
uguale( 'Mese intero', '1–31 ottobre 2026', $vv_giorni( '2026-10-01', '2026-10-31' ) );
uguale( 'Corso di una stagione', 'ott–dic 2026', $vv_giorni( '2026-10-01', '2026-12-15' ) );
uguale( 'Corso su due anni', 'ott 2026 – mag 2027', $vv_giorni( '2026-10-01', '2027-05-31' ) );

// Data a foglietto sulle card degli eventi
$vv_chip = static fn( $da, $a ) => vv_foglietto_data( array( 'inizio' => strtotime( $da ), 'fine' => strtotime( $a . ' 23:59:59' ), 'con_date' => true ) );
uguale( 'Foglietto: un giorno', array( 'alto' => '13', 'basso' => 'nov' ), $vv_chip( '2026-11-13', '2026-11-13' ) );
uguale( 'Foglietto: più giorni nel mese', array( 'alto' => '3–5', 'basso' => 'ott' ), $vv_chip( '2026-10-03', '2026-10-05' ) );
uguale( 'Foglietto: due mesi', array( 'alto' => '28 set', 'basso' => '3 ott' ), $vv_chip( '2026-09-28', '2026-10-03' ) );
uguale( 'Foglietto: corso di una stagione', array( 'alto' => 'ott–dic', 'basso' => '2026' ), $vv_chip( '2026-10-01', '2026-12-15' ) );
uguale( 'Foglietto: corso su due anni', array( 'alto' => 'ott–giu', 'basso' => '26–27' ), $vv_chip( '2026-10-01', '2027-06-30' ) );
uguale( 'Foglietto: senza date dell\'evento', null, vv_foglietto_data( array( 'inizio' => 0, 'fine' => 0, 'con_date' => false ) ) );

// meta_query: le condizioni si sommano, nessuna va persa
$date = array( array( 'key' => 'vv_data', 'value' => '2026-10-06', 'compare' => '>=' ) );
uguale( 'meta_query su argomenti vuoti', array( 'meta_query' => $date ), vv_aggiungi_meta_query( array(), $date ) );
uguale(
	'meta_query in AND con quella che c\'era',
	array(
		'post__not_in' => array( 3 ),
		'meta_query'   => array( 'relation' => 'AND', vv_condizione_in_evidenza(), $date ),
	),
	vv_aggiungi_meta_query( array( 'post__not_in' => array( 3 ), 'meta_query' => vv_condizione_in_evidenza() ), $date )
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
uguale(
	'Conclusi dal più recente inizio, come l\'archivio',
	array( 'Corso autunnale', 'Festa', 'Corso primaverile' ),
	array_column(
		vv_ordina_eventi(
			array(
				$vv_ev( 'Corso primaverile', '2026-03-01', '2026-09-30' ),
				$vv_ev( 'Festa', '2026-09-20', '2026-09-20' ),
				$vv_ev( 'Corso autunnale', '2026-10-01', '2026-10-05' ),
			),
			$vv_oggi
		),
		0
	)
);

// Fascetta della card: a breve, in corso dal primo all'ultimo giorno, poi conclusa
$vv_fase = static fn( $da, $a ) => vv_fase_evento( array( 'inizio' => strtotime( $da ), 'fine' => strtotime( $a . ' 23:59:59' ) ), $vv_oggi );
uguale( 'Fascetta: evento di domani è a breve', 'a-breve', $vv_fase( '2026-10-11', '2026-10-11' ) );
uguale( 'Fascetta: corso cominciato è in corso', 'in-corso', $vv_fase( '2026-10-01', '2027-06-30' ) );
uguale( 'Fascetta: evento di oggi è in corso', 'in-corso', $vv_fase( '2026-10-10', '2026-10-10' ) );
uguale( 'Fascetta: evento finito ieri è concluso', 'concluso', $vv_fase( '2026-10-09', '2026-10-09' ) );

// Palette del sito: solo quelle dell'elenco, ognuna col suo foglio di stile
uguale( 'Palette conosciuta', 'natale', vv_pulisci_palette( 'natale' ) );
uguale( 'Palette predefinita', '', vv_pulisci_palette( '' ) );
uguale( 'Palette sconosciuta o percorso: niente', '', vv_pulisci_palette( '../tokens' ) );
uguale( 'Palette non testuale: niente', '', vv_pulisci_palette( array( 'natale' ) ) );
uguale(
	'Ogni palette ha il suo file in assets/css/palette/',
	array(),
	array_values( array_filter( array_keys( vv_palette_disponibili() ), static fn( $v ) => '' !== $v && ! is_readable( dirname( __DIR__ ) . "/assets/css/palette/{$v}.css" ) ) )
);

// Emoji incollate da Facebook e Gmail, immagini delle gallerie
uguale( 'Emoji di Facebook diventa testo', 'Prenota 👉 qui', vv_emoji_esterne( 'Prenota <img decoding="async" data-src="https://static.xx.fbcdn.net/images/emoji.php/v9/t51/1/16/1f449.png" alt="👉" width="16" height="16" src="data:image/svg+xml;base64,AAA" class="lazyload" /> qui' ) );
uguale( 'Emoji dal proxy di Gmail diventa testo', 'Teatro 🎭', vv_emoji_esterne( 'Teatro <img class="CToWUd" src="https://ci3.googleusercontent.com/meips/ADKq=s0-d-e1-ft#https://static.xx.fbcdn.net/images/emoji.php/v9/t4d/2/16/1f3ad.png" alt="🎭" width="16" height="16">' ) );
$vv_grande = '<img src="https://scontent.xx.fbcdn.net/v/foto.jpg" alt="Foto" width="800" height="600">';
uguale( 'Foto esterna grande intatta', $vv_grande, vv_emoji_esterne( $vv_grande ) );
uguale( 'Contenuto senza immagini esterne invariato', '<p>Ciao <img src="/a.png" alt="a"></p>', vv_emoji_esterne( '<p>Ciao <img src="/a.png" alt="a"></p>' ) );
uguale( 'Galleria: link alla pagina dell\'allegato diventa link al file', 'file', vv_gallerie_al_file( array( 'link' => '' ) )['link'] );
uguale( 'Righe di trattini: paragrafo di trattini diventa il divisore', '<p>a</p><hr class="vv-rule">', vv_righe_di_trattini( '<p>a</p><p><strong>__________</strong></p>' ) );
uguale( 'Righe di trattini: anche spezzate da spazi', '<hr class="vv-rule">', vv_righe_di_trattini( '<p>____ ____</p>' ) );
$vv_spazi = "<p>___</p><p>\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}\u{00A0}</p><p><img src=\"/a.png\">         </p>";
uguale( 'Righe di trattini: spazi e immagini non diventano divisori', $vv_spazi, vv_righe_di_trattini( $vv_spazi ) );
uguale( 'Righe di trattini: UTF-8 non valido lascia il contenuto', "<p>________\xC3</p>", vv_righe_di_trattini( "<p>________\xC3</p>" ) );
uguale( 'Galleria senza link resta senza', 'none', vv_gallerie_al_file( array( 'link' => 'none' ) )['link'] );

// Descrizione per motori di ricerca e social
uguale( 'Descrizione corta ripulita', 'Laboratorio di lettura & teatro.', vv_condivisione_taglia( "<p>Laboratorio  di lettura &amp; teatro.</p>\n[vv_cards]" ) );
uguale( 'Descrizione lunga tagliata sulla parola', 'Uno due tre quattro…', vv_condivisione_taglia( 'Uno due tre quattro cinque sei', 22 ) );
uguale( 'Descrizione senza lettere decorative', 'Laboratorio Viva Voce', vv_condivisione_taglia( 'Laboratorio 𝕍𝕚𝕧𝕒 𝕍𝕠𝕔𝕖' ) );

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
uguale( 'Evento: data inesistente niente evento', array(), vv_ds_evento( array( 'inizio' => '2026-13-14' ) + $vv_ev ) );

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
$vv_mail = vv_email_avvolgi( array( 'subject' => 'Modulo', 'message' => "Messaggio: <a href=\"https://esempio.test/\">Rinnova la tessera</a>", 'headers' => '' ) );
uguale( 'Email di testo con tag scritti da un visitatore: restano testo', array( false, true ), array( strpos( $vv_mail['message'], '<a href="https://esempio.test/">' ), false !== strpos( $vv_mail['message'], '&lt;a href=' ) ) );
uguale( 'Email: piè di pagina con il dominio del sito', true, false !== strpos( $vv_mail['message'], '>wvoce.it</a>' ) );
$vv_doc = array( 'subject' => 'X', 'message' => '<html><body>già impaginata</body></html>', 'headers' => '' );
uguale( 'Email già impaginata: invariata, senza testo alternativo', array( $vv_doc, '' ), array( vv_email_avvolgi( $vv_doc ), vv_email_testo() ) );

// Lettere decorative incollate dai social
uguale( 'Lettere decorative', '<strong class="vv-plain">Voce</strong> e testo', vv_lettere_semplici( '𝕍𝕠𝕔𝕖 e testo' ) );
uguale( 'Lettere decorative senza grassetto', 'Bold', vv_lettere_semplici( '𝐁𝐨𝐥𝐝', false ) );
uguale( 'Testo normale invariato', 'Ciao', vv_lettere_semplici( 'Ciao' ) );
uguale( 'Testo semplice: tag, entità e lettere decorative', 'Voce – l’altra', vv_testo_semplice( '<b>𝕍𝕠𝕔𝕖</b> &#8211; l&#8217;altra' ) );

echo $vv_falliti ? "\n{$vv_falliti} su {$vv_totale} controlli falliti.\n" : "{$vv_totale} controlli superati.\n";
exit( $vv_falliti ? 1 : 0 );
