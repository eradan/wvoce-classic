<?php
/**
 * Correzioni dei contenuti al momento di mostrarli; il testo salvato non cambia.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Su UTF-8 non valido o al limite di backtracking preg_replace() dà null: la pagina resterebbe vuota.
 */
function vv_sostituito( $nuovo, $partenza ) {
	return null === $nuovo ? $partenza : $nuovo;
}

/**
 * Paragrafi di soli trattini bassi ("________", anche "____ ____") diventano il divisore. Attorno ai
 * trattini sono ammessi spazi e tag di formattazione, non immagini.
 */
function vv_righe_di_trattini( $contenuto ) {
	if ( ! str_contains( $contenuto, '___' ) ) {
		return $contenuto;
	}
	$formattazione = '(?:span|strong|b|em|i|u)';
	$nuovo         = preg_replace( '#<p[^>]*>\s*(?:<' . $formattazione . '\b[^>]*>\s*)*(?=[\x{00A0}\s]*_{3})[_\x{00A0}\s]{8,}(?:</' . $formattazione . '>\s*)*</p>#u', '<hr class="vv-rule">', $contenuto );
	if ( null === $nuovo ) {
		return $contenuto;
	}
	return vv_sostituito( preg_replace( '#(?:<br\s*/?>\s*)+_{8,}#u', '<span class="vv-rule--inline" role="separator"></span>', $nuovo ), $contenuto );
}
add_filter( 'the_content', 'vv_righe_di_trattini', 20 );

/**
 * Lettere "decorative" incollate dai social (𝕍𝕠𝕔𝕖, 𝐁𝐨𝐥𝐝…) come testo normale: non esistono nei font
 * del sito (compaiono come □) e gli screen reader le leggono una per una.
 *
 * @param bool $grassetto Racchiudere le parole convertite in <strong>.
 */
function vv_lettere_semplici( $testo, $grassetto = true ) {
	if ( ! is_string( $testo ) || ! preg_match( '/[\x{1D400}-\x{1D7FF}]/u', $testo ) ) {
		return $testo;
	}
	// I "simboli letterali" che riempiono i buchi dei blocchi matematici (ℂ al posto della C doppia).
	static $simboli = null;

	$simboli ??= array_combine(
		preg_split( '//u', 'ℂℍℕℙℚℝℤℬℰℱℋℐℒℳℛℭℌℑℜℨℎℯℊℴ', -1, PREG_SPLIT_NO_EMPTY ),
		str_split( 'CHNPQRZBEFHILMRCHIRZhego' )
	);

	$lettere = '\x{1D400}-\x{1D6A3}\x{1D7CE}-\x{1D7FF}' . implode( '', array_keys( $simboli ) );
	// Da una lettera decorativa all'ultima, con spazi, punteggiatura e accenti in mezzo.
	return preg_replace_callback(
		'/[' . $lettere . '](?:[' . $lettere . '\x{0300}-\x{036F}\s\'’.,:;!?&-]*[' . $lettere . '])?[\x{0300}-\x{036F}]*/u',
		static function ( $parola ) use ( $simboli, $grassetto ) {
			$semplice = preg_replace_callback(
				'/[\x{1D400}-\x{1D6A3}\x{1D7CE}-\x{1D7FF}]/u',
				static function ( $lettera ) {
					$codice = mb_ord( $lettera[0] );
					if ( $codice >= 0x1D7CE ) {
						return (string) ( ( $codice - 0x1D7CE ) % 10 );
					}
					$posizione = ( $codice - 0x1D400 ) % 52;
					return chr( $posizione < 26 ? 65 + $posizione : 97 + $posizione - 26 );
				},
				strtr( $parola[0], $simboli )
			);
			if ( class_exists( 'Normalizer' ) ) {
				$semplice = Normalizer::normalize( $semplice, Normalizer::FORM_C ); // a + ◌̀ → à
			}
			return $grassetto ? '<strong class="vv-plain">' . $semplice . '</strong>' : $semplice;
		},
		$testo
	);
}

/**
 * Niente tag, entità decodificate, lettere decorative come testo normale; va ancora escapato.
 */
function vv_testo_semplice( $html ) {
	return vv_lettere_semplici( html_entity_decode( wp_strip_all_tags( (string) $html ) ), false );
}

function vv_lettere_semplici_contenuto( $contenuto ) {
	if ( ! preg_match( '/[\x{1D400}-\x{1D7FF}]/u', $contenuto ) ) {
		return $contenuto;
	}
	// Solo i nodi di testo, mai tag e attributi.
	$parti = preg_split( '/(<[^>]*>)/', $contenuto, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parti as $i => $parte ) {
		if ( '' !== $parte && '<' !== $parte[0] ) {
			$parti[ $i ] = vv_lettere_semplici( $parte );
		}
	}
	return implode( '', $parti );
}
add_filter( 'the_content', 'vv_lettere_semplici_contenuto', 21 );

function vv_lettere_semplici_testo( $testo ) {
	return is_admin() ? $testo : vv_lettere_semplici( $testo, false );
}
add_filter( 'the_title', 'vv_lettere_semplici_testo' );
add_filter( 'get_the_excerpt', 'vv_lettere_semplici_testo' );
add_filter( 'single_post_title', 'vv_lettere_semplici_testo' ); // <title>, og:title, dati strutturati

/**
 * <li> rimasti fuori dopo un </ul> per HTML rotto nell'editor: tornano in un elenco.
 */
function vv_elenchi_orfani( $contenuto ) {
	if ( ! str_contains( $contenuto, '</ul>' ) ) {
		return $contenuto;
	}
	$nuovo = preg_replace_callback(
		'#</ul>((?:\s*<li\b[^>]*>(?:(?!<li\b|</?ul\b|</?ol\b).)*?</li>)+)#is',
		static fn( $voci ) => '</ul><ul>' . $voci[1] . '</ul>',
		$contenuto
	);
	return vv_sostituito( $nuovo, $contenuto );
}
add_filter( 'the_content', 'vv_elenchi_orfani', 19 );

/**
 * Emoji incollate da Facebook o Gmail come immagini 16×16 dai loro server, con l'emoji vera nell'alt:
 * diventano testo, senza richieste esterne né immagini rotte quando quegli indirizzi scadono.
 */
function vv_emoji_esterne( $contenuto ) {
	if ( ! str_contains( $contenuto, 'fbcdn.net' ) && ! str_contains( $contenuto, 'googleusercontent.com' ) ) {
		return $contenuto;
	}
	$nuovo = preg_replace_callback(
		'#<img\b[^>]*>#i',
		static function ( $img ) {
			$esterna   = preg_match( '#\s(?:data-)?src=["\']https?://[^"\']*(?:fbcdn\.net|googleusercontent\.com)/#i', $img[0] );
			$con_emoji = preg_match( '#\salt=(["\'])([^"\']{1,8})\1#u', $img[0], $alt );
			$grande    = preg_match( '#\swidth=["\']?(\d+)#i', $img[0], $larghezza ) && (int) $larghezza[1] > 32;
			return $esterna && $con_emoji && ! $grande ? $alt[2] : $img[0];
		},
		$contenuto
	);
	return vv_sostituito( $nuovo, $contenuto );
}
add_filter( 'the_content', 'vv_emoji_esterne', 18 );

/**
 * Le miniature delle gallerie aprono l'immagine, non la pagina dell'allegato (404 per i contenuti non
 * pubblicati).
 */
function vv_gallerie_al_file( $attributi ) {
	if ( isset( $attributi['link'] ) && 'none' !== $attributi['link'] ) {
		$attributi['link'] = 'file';
	}
	return $attributi;
}
add_filter( 'shortcode_atts_gallery', 'vv_gallerie_al_file' );
