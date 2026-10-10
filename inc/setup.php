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

/*
 * Header e footer "classici" di Sydney. Passando da un tema che non ha "sydney" nel nome Sydney accende
 * da solo il costruttore header/footer, con un markup che il CSS del child non conosce e un footer senza
 * aree widget: qui resta spento, senza toccare l'opzione salvata. Per usarlo davvero:
 * define( 'VV_SYDNEY_BUILDER', true ); in wp-config.php.
 */
if ( ! defined( 'VV_SYDNEY_BUILDER' ) || ! VV_SYDNEY_BUILDER ) {
	add_filter(
		'option_sydney-modules',
		static function ( $modules ) {
			$modules               = is_array( $modules ) ? $modules : (array) $modules;
			$modules['hf-builder'] = false;
			return $modules;
		}
	);
}

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
 * "Senza immagine in testa" (riquadro VivaVoce di articoli e pagine): nella pagina del contenuto
 * Sydney non trova l'immagine in evidenza e non la stampa sopra il testo. Altrove (card, "Da non
 * perdere", anteprime dei social) l'immagine resta.
 *
 * @param bool             $has  Il contenuto ha un'immagine in evidenza.
 * @param int|WP_Post|null $post Articolo o pagina.
 * @return bool
 */
function vv_senza_immagine_in_testa( $has, $post = null ) {
	if ( ! $has || is_admin() || ! is_singular( array( 'post', 'page' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $has;
	}
	$post = get_post( $post );
	return ! ( $post && get_queried_object_id() === $post->ID && get_post_meta( $post->ID, 'vv_senza_immagine', true ) );
}
add_filter( 'has_post_thumbnail', 'vv_senza_immagine_in_testa', 10, 2 );

/**
 * Stessa regola per chi stampa l'immagine senza chiedere prima has_post_thumbnail().
 *
 * @param string $html    Immagine.
 * @param int    $post_id Articolo o pagina.
 */
function vv_senza_immagine_in_testa_html( $html, $post_id ) {
	return vv_senza_immagine_in_testa( true, $post_id ) ? $html : '';
}
add_filter( 'post_thumbnail_html', 'vv_senza_immagine_in_testa_html', 10, 2 );

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

/**
 * Contact Form 7 e reCAPTCHA solo nelle pagine con un modulo (shortcode o blocco, oggi Contatti):
 * nelle altre sono peso inutile e una richiesta ai server di Google prima del consenso.
 */
function vv_cf7_solo_dove_serve() {
	if ( ! defined( 'WPCF7_VERSION' ) || ( is_singular() && false !== strpos( get_queried_object()->post_content, 'contact-form-7' ) ) ) {
		return;
	}
	foreach ( array( 'contact-form-7', 'swv', 'wpcf7-recaptcha', 'google-recaptcha' ) as $handle ) {
		wp_dequeue_script( $handle );
	}
	wp_dequeue_style( 'contact-form-7' );
}
add_action( 'wp_enqueue_scripts', 'vv_cf7_solo_dove_serve', 100 );
