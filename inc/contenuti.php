<?php
/**
 * Correzioni dei contenuti al momento di mostrarli (il testo salvato non cambia): separatori fatti
 * di trattini bassi, lettere "decorative" incollate dai social, voci di elenco rimaste fuori dall'elenco,
 * emoji incollate da Facebook come immagini, link delle gallerie.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paragrafi fatti solo di trattini bassi ("________") usati come separatori
 * diventano il divisore a pennellata.
 *
 * @param string $content Contenuto.
 */
function vv_underscore_rules( $content ) {
	if ( false === strpos( $content, '___' ) ) {
		return $content;
	}
	$content = preg_replace( '#<p[^>]*>\s*(?:<[^>]+>\s*)*[_\x{00A0}\s]{8,}(?:\s*</[^>]+>)*\s*</p>#u', '<hr class="vv-rule">', $content );
	return preg_replace( '#(?:<br\s*/?>\s*)+_{8,}#u', '<span class="vv-rule--inline" role="separator"></span>', $content );
}
add_filter( 'the_content', 'vv_underscore_rules', 20 );

/**
 * Lettere "decorative" incollate dai social (𝕍𝕠𝕔𝕖, 𝐁𝐨𝐥𝐝, 𝘐𝘵𝘢𝘭𝘪𝘤…): diventano testo normale
 * in grassetto. Quei caratteri non esistono nei font del sito (compaiono come □) e gli
 * screen reader li leggono lettera per lettera.
 *
 * @param string $text Testo o HTML.
 * @param bool   $wrap Racchiudere le sequenze convertite in <strong>.
 */
function vv_plain_letters( $text, $wrap = true ) {
	if ( ! is_string( $text ) || ! preg_match( '/[\x{1D400}-\x{1D7FF}]/u', $text ) ) {
		return $text;
	}
	// Lettere dei "simboli letterali" usate nei buchi dei blocchi matematici.
	static $letterlike = null;
	if ( null === $letterlike ) {
		$letterlike = array_combine(
			preg_split( '//u', 'ℂℍℕℙℚℝℤℬℰℱℋℐℒℳℛℭℌℑℜℨℎℯℊℴ', -1, PREG_SPLIT_NO_EMPTY ),
			str_split( 'CHNPQRZBEFHILMRCHIRZhego' )
		);
	}
	$chars = '\x{1D400}-\x{1D6A3}\x{1D7CE}-\x{1D7FF}' . implode( '', array_keys( $letterlike ) );
	// Una sequenza: da una lettera decorativa all'ultima, con spazi, punteggiatura e accenti in mezzo.
	return preg_replace_callback(
		'/[' . $chars . '](?:[' . $chars . '\x{0300}-\x{036F}\s\'’.,:;!?&-]*[' . $chars . '])?[\x{0300}-\x{036F}]*/u',
		static function ( $m ) use ( $letterlike, $wrap ) {
			$out = preg_replace_callback(
				'/[\x{1D400}-\x{1D6A3}\x{1D7CE}-\x{1D7FF}]/u',
				static function ( $c ) {
					$cp = mb_ord( $c[0], 'UTF-8' );
					if ( $cp >= 0x1D7CE ) {
						return (string) ( ( $cp - 0x1D7CE ) % 10 );
					}
					$i = ( $cp - 0x1D400 ) % 52;
					return chr( $i < 26 ? 65 + $i : 97 + $i - 26 );
				},
				strtr( $m[0], $letterlike )
			);
			if ( class_exists( 'Normalizer' ) ) {
				$out = Normalizer::normalize( $out, Normalizer::FORM_C ); // a + ◌̀ → à
			}
			return $wrap ? '<strong class="vv-plain">' . $out . '</strong>' : $out;
		},
		$text
	);
}

/**
 * @param string $content Contenuto.
 */
function vv_plain_letters_content( $content ) {
	if ( ! preg_match( '/[\x{1D400}-\x{1D7FF}]/u', $content ) ) {
		return $content;
	}
	// Solo i nodi di testo, mai tag e attributi.
	$parts = preg_split( '/(<[^>]*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( '' !== $part && '<' !== $part[0] ) {
			$parts[ $i ] = vv_plain_letters( $part );
		}
	}
	return implode( '', $parts );
}
add_filter( 'the_content', 'vv_plain_letters_content', 21 );

/**
 * @param string $title Titolo.
 */
function vv_plain_letters_title( $title ) {
	return is_admin() ? $title : vv_plain_letters( $title, false );
}
add_filter( 'the_title', 'vv_plain_letters_title' );
add_filter( 'get_the_excerpt', 'vv_plain_letters_title' );

/**
 * Voci di elenco "orfane": <li> rimasti fuori dopo un </ul> (HTML rotto nell'editor,
 * es. Formazione e laboratori) vengono riavvolti in un elenco, così hanno lo stesso aspetto
 * delle altre. Il contenuto salvato non cambia.
 *
 * @param string $content Contenuto.
 */
function vv_wrap_orphan_list_items( $content ) {
	if ( false === strpos( $content, '</ul>' ) ) {
		return $content;
	}
	return preg_replace_callback(
		'#</ul>((?:\s*<li\b[^>]*>(?:(?!<li\b|</?ul\b|</?ol\b).)*?</li>)+)#is',
		static fn( $m ) => '</ul><ul>' . $m[1] . '</ul>',
		$content
	);
}
add_filter( 'the_content', 'vv_wrap_orphan_list_items', 19 );

/**
 * Emoji incollate da Facebook o da Gmail: arrivano come immagini 16×16 dai server di Meta
 * (static.xx.fbcdn.net) o dal proxy di Gmail (ci*.googleusercontent.com), con l'emoji vera nell'alt.
 * Diventano testo: niente richieste a server esterni a ogni visita e niente immagini rotte quando
 * quegli indirizzi scadono. Le immagini esterne più grandi restano come sono.
 *
 * @param string $content Contenuto.
 */
function vv_emoji_esterne( $content ) {
	if ( false === strpos( $content, 'fbcdn.net' ) && false === strpos( $content, 'googleusercontent.com' ) ) {
		return $content;
	}
	return (string) preg_replace_callback(
		'#<img\b[^>]*>#i',
		static function ( $m ) {
			$img = $m[0];
			if ( ! preg_match( '#\s(?:data-)?src=["\']https?://[^"\']*(?:fbcdn\.net|googleusercontent\.com)/#i', $img )
				|| ! preg_match( '#\salt=(["\'])([^"\']{1,8})\1#u', $img, $alt )
				|| ( preg_match( '#\swidth=["\']?(\d+)#i', $img, $w ) && (int) $w[1] > 32 ) ) {
				return $img;
			}
			return $alt[2];
		},
		$content
	);
}
add_filter( 'the_content', 'vv_emoji_esterne', 18 );

/**
 * Gallerie [gallery] dell'editor classico: le miniature aprono l'immagine, non la pagina
 * dell'allegato, che il sito non usa e che per gli allegati di un contenuto non pubblicato dà 404.
 *
 * @param array $out Attributi della galleria.
 */
function vv_gallery_link_file( $out ) {
	if ( isset( $out['link'] ) && 'none' !== $out['link'] ) {
		$out['link'] = 'file';
	}
	return $out;
}
add_filter( 'shortcode_atts_gallery', 'vv_gallery_link_file' );
