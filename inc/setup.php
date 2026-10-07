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

// Niente sidebar: i contatti vivono nel footer.
add_filter( 'sydney_show_sidebar', '__return_false' );

/**
 * Colonna dei contenuti a tutta larghezza (la larghezza di lettura la dà il CSS).
 *
 * @param string $classes Classi della content-area.
 */
function vv_content_area_class( $classes ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- firma del filtro.
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
		'Read More'                                => 'Leggi',
		'Read more'                                => 'Leggi',
		'By'                                       => 'di',
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
