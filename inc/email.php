<?php
/**
 * Ogni email di wp_mail() (tema, plugin, WordPress) in un modello HTML con il marchio, più la versione in
 * solo testo. Contro lo spam conta soprattutto l'autenticazione del dominio (SPF, DKIM, DMARC); qui le
 * parti che dipendono dal messaggio: due versioni, HTML ben formato, testo vero, link con il dominio del
 * sito, niente testo nascosto (SpamAssassin: FONT_INVIS_MSGID). Un documento HTML completo resta com'è.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versione in solo testo dell'email in preparazione, passata a phpmailer_init. Senza argomento la legge.
 */
function vv_email_testo( $testo = null ) {
	static $tenuto = '';
	if ( null !== $testo ) {
		$tenuto = $testo;
	}
	return $tenuto;
}

/**
 * Le intestazioni di wp_mail() arrivano come elenco o come testo.
 *
 * @return string[]
 */
function vv_email_righe( $intestazioni ) {
	return is_array( $intestazioni ) ? $intestazioni : explode( "\n", str_replace( "\r\n", "\n", (string) $intestazioni ) );
}

/**
 * HTML solo se dichiarato, nelle intestazioni o con il filtro wp_mail_content_type: i tag in un testo
 * semplice possono averli scritti i visitatori in un modulo, e non devono diventare link veri.
 */
function vv_email_intestazioni_html( $intestazioni ) {
	foreach ( vv_email_righe( $intestazioni ) as $riga ) {
		if ( preg_match( '#^\s*content-type:\s*text/html#i', (string) $riga ) ) {
			return true;
		}
	}
	return str_contains( strtolower( (string) apply_filters( 'wp_mail_content_type', 'text/plain' ) ), 'text/html' );
}

/**
 * @return string[] Le stesse intestazioni, con un solo Content-Type: HTML in UTF-8.
 */
function vv_email_intestazioni( $intestazioni ) {
	$righe = array_filter(
		array_map( 'trim', vv_email_righe( $intestazioni ) ),
		static fn( $riga ) => '' !== $riga && ! preg_match( '#^content-type:#i', $riga )
	);

	$righe[] = 'Content-Type: text/html; charset=UTF-8';
	return array_values( $righe );
}

/**
 * I link diventano "testo (indirizzo)", i blocchi vanno a capo.
 */
function vv_email_da_html( $html ) {
	$html  = preg_replace_callback(
		'#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is',
		static function ( $link ) {
			list( , $indirizzo, $testo ) = $link;
			$testo                       = trim( wp_strip_all_tags( $testo ) );
			return $testo && $testo !== $indirizzo ? "{$testo} ({$indirizzo})" : $indirizzo;
		},
		(string) $html
	);
	$html  = preg_replace( '#<br\s*/?>|</(p|div|tr|h[1-6]|li)>#i', "\n", $html );
	$testo = preg_replace( "/[ \t]+/", ' ', vv_testo_semplice( $html ) );
	return trim( (string) preg_replace( "/\n\s*\n\s*\n+/", "\n\n", $testo ) );
}

/**
 * @return array{html:string,testo:string}
 */
function vv_email_piede() {
	$citta = trim( vv_mod( 'vv_cap' ) . ' ' . vv_mod( 'vv_citta' ) );
	$sede  = implode( ', ', array_filter( array( vv_mod( 'vv_indirizzo' ), $citta ) ) );
	$email = vv_mod( 'vv_email' );
	$link  = array_filter( array(
		vv_dominio() => home_url( '/' ),
		'Facebook'   => vv_mod( 'vv_facebook' ),
		'Instagram'  => vv_mod( 'vv_instagram' ),
		'Privacy'    => get_privacy_policy_url(),
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
 * Tabelle e stili in linea, come vogliono i programmi di posta, che non leggono le variabili CSS: i
 * colori sono quelli del tema chiaro di tokens.css.
 */
function vv_email_documento( $oggetto, $corpo, $piede ) {
	$nome = get_bloginfo( 'name' );
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
' . $piede . '
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
}

/**
 * @param array $atts Argomenti di wp_mail(): to, subject, message, headers, attachments.
 */
function vv_email_avvolgi( $atts ) {
	$messaggio    = (string) ( $atts['message'] ?? '' );
	$intestazioni = $atts['headers'] ?? '';
	vv_email_testo( '' );
	if ( '' === trim( $messaggio ) || str_contains( strtolower( $messaggio ), '<html' ) ) {
		return $atts;
	}

	if ( vv_email_intestazioni_html( $intestazioni ) ) {
		$corpo = $messaggio;
		$testo = vv_email_da_html( $messaggio );
	} else {
		$testo = trim( $messaggio );
		$corpo = make_clickable( wpautop( esc_html( $testo ) ) );
		$corpo = preg_replace( '#<a href=#', '<a style="color:#007a87;" href=', $corpo );
		// Un paragrafo che è solo un indirizzo diventa un pulsante: se il servizio d'invio riscrive i link
		// per contare i clic, il testo non mostra un indirizzo diverso (SpamAssassin: HTTPS_HTTP_MISMATCH).
		$corpo = preg_replace(
			'#<p>\s*<a [^>]*href="([^"]+)"[^>]*>[^<]+</a>\s*</p>#',
			'<p style="margin:22px 0;"><a href="$1" style="display:inline-block;padding:12px 22px;background-color:#ffd923;color:#00102e;font-weight:bold;text-decoration:none;border-radius:2px;">Apri il link</a></p>',
			$corpo
		);
	}
	$corpo = str_replace( '<p>', '<p style="margin:0 0 14px;">', $corpo );

	$piede           = vv_email_piede();
	$atts['message'] = vv_email_documento( (string) ( $atts['subject'] ?? '' ), $corpo, $piede['html'] );
	$atts['headers'] = vv_email_intestazioni( $intestazioni );
	vv_email_testo( $testo . "\n\n" . $piede['testo'] );
	return $atts;
}
add_filter( 'wp_mail', 'vv_email_avvolgi', 20 );

/**
 * Versione in solo testo e host del sito (Message-ID e saluto HELO) invece del nome del container.
 * PHPMailer è lo stesso oggetto per tutte le email della richiesta: il testo si azzera ogni volta.
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
