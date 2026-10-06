<?php
/**
 * Filtri su Sydney e WordPress: layout, testi, classi.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Estratto anche per le pagine (usato dalle card della Pagina hub).
 */
function vv_setup() {
	add_post_type_support( 'page', 'excerpt' );
	add_image_size( 'vv-card', 720, 480, true );
	add_image_size( 'vv-persona', 400, 400, true );
}
add_action( 'after_setup_theme', 'vv_setup', 20 );

/**
 * Classe sul body a cui si agganciano alcune regole del tema.
 *
 * @param string[] $classes Classi del body.
 */
function vv_body_class( $classes ) {
	$classes[] = 'vv-design';
	return $classes;
}
add_filter( 'body_class', 'vv_body_class' );

// Niente sidebar: i contatti vivono nel footer.
add_filter( 'sydney_show_sidebar', '__return_false' );

/**
 * Colonna dei contenuti a tutta larghezza (la larghezza di lettura la dà il CSS).
 *
 * @param string $classes Classi della content-area.
 */
function vv_content_area_class( $classes ) {
	return is_singular() ? 'no-sidebar' : 'col-md-12';
}
add_filter( 'sydney_content_area_class', 'vv_content_area_class', 20 );

/**
 * Immagine in evidenza in testa alle pagine (titolo, immagine, testo), come l'opzione
 * "Enable featured images on all pages" di Sydney. Non nella home, che ha l'hero.
 *
 * @param mixed $value Valore salvato nel Customizer.
 */
function vv_page_featured_image( $value ) {
	if ( is_front_page() ) {
		return $value;
	}
	// Nelle "Pagine elenco" l'immagine serve alla card dell'hub: in testa spingerebbe le card sotto la piega.
	return is_page_template( 'page-templates/page-elenco.php' ) ? 0 : 1;
}
add_filter( 'theme_mod_enable_page_feat_images', 'vv_page_featured_image' );

/**
 * Fascia sotto il footer: "© anno" e la scritta VIVA VOCE del logo, al posto del testo del Customizer
 * (Footer → Credits, che su Sydney finiva con "Proudly powered by Sydney"). Sydney sostituisce
 * {copyright} e {year}; le faccine ai lati sono nel CSS (footer.css).
 */
function vv_footer_credits() {
	return sprintf(
		'<span class="vv-credits__anno">{copyright} {year}</span> <img class="vv-credits__logo" src="%s" width="236" height="140" alt="VivaVoce">',
		esc_url( VV_URI . '/assets/img/viva-voce-scritta.png' )
	);
}
add_filter( 'theme_mod_footer_credits', 'vv_footer_credits' );

// Niente icone social nella fascia sotto il footer (i social sono già nelle colonne del footer).
add_filter( 'theme_mod_social_profiles_footer', '__return_empty_string' );

/**
 * Titolo degli archivi senza il prefisso "Categoria:" / "Tag:".
 *
 * @param string $title  Titolo completo.
 * @param string $plain  Titolo senza prefisso.
 */
function vv_archive_title( $title, $plain = '' ) {
	return $plain ? $plain : $title;
}
add_filter( 'get_the_archive_title', 'vv_archive_title', 10, 2 );

/**
 * Traduzioni mancanti in Sydney e in Forminator, che sul sito non ha la traduzione italiana:
 * pulsante e testo del campo "carica un file", messaggi d'errore predefiniti.
 *
 * @param string $translation Testo tradotto.
 * @param string $text        Testo originale.
 * @param string $domain      Dominio del testo.
 */
function vv_plugin_strings( $translation, $text, $domain ) {
	static $domains = array( 'sydney', 'forminator' );
	if ( ! in_array( $domain, $domains, true ) ) {
		return $translation;
	}
	static $map = array(
		'Read More' => 'Leggi',
		'Read more' => 'Leggi',
		'By'        => 'di',
		// Forminator
		'Choose File'                              => 'Scegli il file',
		'Choose file'                              => 'Scegli il file',
		'No file chosen'                           => 'Nessun file scelto',
		'This field is required.'                  => 'Questo campo è obbligatorio.',
		'This field is required. Please check it.' => 'Per continuare spunta questa casella.',
	);
	return $map[ $text ] ?? $translation;
}
add_filter( 'gettext', 'vv_plugin_strings', 10, 3 );

/**
 * Estratti chiusi da "…" invece di "[…]".
 */
function vv_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'vv_excerpt_more', 20 );

/**
 * Carica un SVG del tema (pennellate) da stampare inline.
 *
 * @param string $name Nome del file senza estensione.
 * @param string $class Classe CSS da aggiungere.
 */
function vv_svg( $name, $class = '' ) {
	static $files = array();
	if ( ! isset( $files[ $name ] ) ) {
		$file           = VV_DIR . '/assets/svg/' . sanitize_file_name( $name ) . '.svg';
		$files[ $name ] = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	$svg = $files[ $name ];
	if ( '' === $svg ) {
		return '';
	}
	$attr = 'aria-hidden="true" focusable="false"' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' );
	return preg_replace( '/<svg\b/', '<svg ' . $attr, $svg, 1 );
}

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
	static $letterlike = array(
		'ℂ' => 'C', 'ℍ' => 'H', 'ℕ' => 'N', 'ℙ' => 'P', 'ℚ' => 'Q', 'ℝ' => 'R', 'ℤ' => 'Z',
		'ℬ' => 'B', 'ℰ' => 'E', 'ℱ' => 'F', 'ℋ' => 'H', 'ℐ' => 'I', 'ℒ' => 'L', 'ℳ' => 'M', 'ℛ' => 'R',
		'ℭ' => 'C', 'ℌ' => 'H', 'ℑ' => 'I', 'ℜ' => 'R', 'ℨ' => 'Z', 'ℎ' => 'h', 'ℯ' => 'e', 'ℊ' => 'g', 'ℴ' => 'o',
	);
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
 * La voce di menu che porta a "Diventa socio" diventa il pulsante rosso dell'header,
 * senza dover aggiungere a mano la classe CSS "vv-cta" (che resta comunque valida).
 *
 * @param string[] $classes Classi della voce.
 * @param WP_Post  $item    Voce di menu.
 * @param stdClass $args    Argomenti del menu.
 * @param int      $depth   Livello (0 = voce principale).
 */
function vv_menu_cta_class( $classes, $item, $args = null, $depth = 0 ) {
	if ( 0 !== (int) $depth || in_array( 'vv-cta', (array) $classes, true ) ) {
		return $classes;
	}
	static $target = null;
	if ( null === $target ) {
		$target = untrailingslashit( (string) wp_parse_url( vv_mod( 'vv_url_socio' ), PHP_URL_PATH ) );
	}
	$url = untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) );
	if ( $target && $url === $target ) {
		$classes[] = 'vv-cta';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'vv_menu_cta_class', 10, 4 );

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
