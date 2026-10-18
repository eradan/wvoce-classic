<?php
/**
 * Email con il marchio VivaVoce: ogni email che passa da wp_mail() (tema, Forminator, Contact Form 7,
 * WordPress) va in un unico modello HTML, con il logo sul giallo dell'header e un piè di pagina con i
 * dati dell'associazione, e porta anche la versione in solo testo.
 *
 * Contro lo spam conta soprattutto l'autenticazione del dominio (SPF, DKIM, DMARC, nel DNS e in WP Mail
 * SMTP); qui le parti che dipendono dal messaggio: due versioni, HTML e testo (multipart/alternative),
 * HTML ben formato con lingua, charset e titolo, testo vero e non solo immagini, link al sito con il
 * suo dominio, Message-ID con il dominio del sito invece del nome del container.
 *
 * Un'email che è già un documento HTML completo (con <html>) resta com'è: l'ha impaginata qualcun altro.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versione in solo testo dell'email in preparazione, per phpmailer_init.
 *
 * @param string|null $testo Testo da tenere (null: solo lettura).
 */
function vv_email_testo( $testo = null ) {
	static $tenuto = '';
	if ( null !== $testo ) {
		$tenuto = $testo;
	}
	return $tenuto;
}

/**
 * Intestazioni di wp_mail() riga per riga (arrivano come elenco o come testo).
 *
 * @param string|string[] $headers Intestazioni.
 * @return string[]
 */
function vv_email_righe( $headers ) {
	return is_array( $headers ) ? $headers : explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
}

/**
 * Le intestazioni dicono che il messaggio è HTML?
 *
 * @param string|string[] $headers Intestazioni di wp_mail().
 */
function vv_email_intestazioni_html( $headers ) {
	foreach ( vv_email_righe( $headers ) as $riga ) {
		if ( preg_match( '#^\s*content-type:\s*text/html#i', (string) $riga ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Intestazioni senza Content-Type, con quello HTML in UTF-8.
 *
 * @param string|string[] $headers Intestazioni di wp_mail().
 * @return string[]
 */
function vv_email_intestazioni( $headers ) {
	$righe = array_filter(
		array_map( 'trim', vv_email_righe( $headers ) ),
		static function ( $riga ) {
			return '' !== $riga && ! preg_match( '#^content-type:#i', $riga );
		}
	);

	$righe[] = 'Content-Type: text/html; charset=UTF-8';
	return array_values( $righe );
}

/**
 * Testo semplice da un frammento HTML: i link diventano "testo (indirizzo)", i blocchi vanno a capo.
 *
 * @param string $html Frammento HTML.
 */
function vv_email_da_html( $html ) {
	$html  = preg_replace_callback(
		'#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is',
		static function ( $m ) {
			$testo = trim( wp_strip_all_tags( $m[2] ) );
			return $testo && $testo !== $m[1] ? "{$testo} ({$m[1]})" : $m[1];
		},
		(string) $html
	);
	$html  = preg_replace( '#<br\s*/?>|</(p|div|tr|h[1-6]|li)>#i', "\n", $html );
	$testo = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$testo = preg_replace( "/[ \t]+/", ' ', $testo );
	return trim( (string) preg_replace( "/\n\s*\n\s*\n+/", "\n\n", $testo ) );
}

/**
 * Piè di pagina: dati dell'associazione dal Customizer, link al sito e all'informativa.
 *
 * @return array{html:string,testo:string}
 */
function vv_email_piede() {
	$sede  = trim( vv_mod( 'vv_indirizzo' ) . ', ' . trim( vv_mod( 'vv_cap' ) . ' ' . vv_mod( 'vv_citta' ) ), ', ' );
	$email = vv_mod( 'vv_email' );
	$link  = array_filter( array(
		'wvoce.it'  => home_url( '/' ),
		'Facebook'  => vv_mod( 'vv_facebook' ),
		'Instagram' => vv_mod( 'vv_instagram' ),
		'Privacy'   => get_privacy_policy_url(),
	) );

	$html = array();
	foreach ( $link as $testo => $url ) {
		$html[] = sprintf( '<a href="%s" style="color:#007a87;text-decoration:underline;">%s</a>', esc_url( $url ), esc_html( $testo ) );
	}
	$riga = array_filter( array( vv_mod( 'vv_nome_legale' ), $sede, $email ) );
	return array(
		'html'  => sprintf(
			'%s<br>%s',
			esc_html( implode( ' · ', $riga ) ),
			implode( ' · ', $html )
		),
		'testo' => "--\n" . ( $riga ? implode( "\n", $riga ) . "\n" : '' ) . home_url( '/' ),
	);
}

/**
 * Documento HTML dell'email: header giallo con il logo, corpo bianco, piè di pagina grigio.
 * Tabelle e stili in linea, come vogliono i programmi di posta, che non leggono le variabili CSS:
 * i colori sono quelli del tema chiaro di assets/css/tokens.css (giallo, inchiostro, link, riquadro).
 *
 * @param string $oggetto   Oggetto, anche come <title>.
 * @param string $corpo     Corpo, frammento HTML.
 * @param string $anteprima Testo d'anteprima nascosto (la riga grigia accanto all'oggetto).
 */
function vv_email_documento( $oggetto, $corpo, $anteprima ) {
	$piede = vv_email_piede();
	$nome  = get_bloginfo( 'name' );
	return '<!DOCTYPE html>
<html lang="it" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>' . esc_html( $oggetto ) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . esc_html( $anteprima ) . '</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff;border-collapse:collapse;">
<tr><td align="center" style="background-color:#ffd923;padding:20px 24px;">
<a href="' . esc_url( home_url( '/' ) ) . '"><img src="' . esc_url( VV_URI . '/assets/img/logo-email.png' ) . '" width="240" height="92" alt="' . esc_attr( $nome ) . '" style="display:block;border:0;width:240px;height:auto;font-family:Arial,Helvetica,sans-serif;font-size:28px;font-weight:bold;color:#00102e;"></a>
</td></tr>
<tr><td style="padding:28px 32px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.55;color:#00102e;">
' . $corpo . '
</td></tr>
<tr><td style="padding:18px 32px;background-color:#d7e0e9;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#384a5e;">
' . $piede['html'] . '
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
}

/**
 * Avvolge ogni email nel modello e tiene da parte la versione in solo testo.
 *
 * @param array $atts Argomenti di wp_mail(): to, subject, message, headers, attachments.
 */
function vv_email_avvolgi( $atts ) {
	$messaggio = (string) ( $atts['message'] ?? '' );
	$headers   = $atts['headers'] ?? '';
	vv_email_testo( '' );
	if ( '' === trim( $messaggio ) || false !== stripos( $messaggio, '<html' ) ) {
		return $atts;
	}

	if ( vv_email_intestazioni_html( $headers ) || preg_match( '#<(p|br|div|table|a|strong|ul|ol)\b#i', $messaggio ) ) {
		$corpo = $messaggio;
		$testo = vv_email_da_html( $messaggio );
	} else {
		$testo = trim( $messaggio );
		$corpo = make_clickable( wpautop( esc_html( $testo ) ) );
		$corpo = preg_replace( '#<a #', '<a style="color:#007a87;" ', $corpo );
	}
	$corpo = str_replace( '<p>', '<p style="margin:0 0 14px;">', $corpo );

	$anteprima = mb_substr( (string) preg_replace( '/\s+/u', ' ', $testo ), 0, 110, 'UTF-8' );
	$oggetto   = (string) ( $atts['subject'] ?? '' );

	$atts['message'] = vv_email_documento( $oggetto, $corpo, $anteprima );
	$atts['headers'] = vv_email_intestazioni( $headers );
	vv_email_testo( $testo . "\n\n" . vv_email_piede()['testo'] );
	return $atts;
}
add_filter( 'wp_mail', 'vv_email_avvolgi', 20 );

/**
 * Versione in solo testo e Message-ID con il dominio del sito. PHPMailer è lo stesso oggetto per
 * tutte le email della richiesta: si riscrive sempre, anche vuoto, per non lasciare il testo di
 * un'email alla successiva.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mailer PHPMailer di WordPress.
 */
function vv_email_phpmailer( $mailer ) {
	$mailer->AltBody = vv_email_testo(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- proprietà di PHPMailer.
	$dominio         = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	if ( $dominio ) {
		$mailer->Hostname = $dominio; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- proprietà di PHPMailer.
	}
	vv_email_testo( '' );
}
add_action( 'phpmailer_init', 'vv_email_phpmailer' );
