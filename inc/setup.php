<?php
/**
 * Impostazioni di Sydney e WordPress: immagini, header e footer classici, immagine in testa,
 * traduzioni mancanti, Contact Form 7 solo dove serve.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_impostazioni() {
	add_post_type_support( 'page', 'excerpt' ); // il riassunto è il testo della card della pagina
	add_image_size( 'vv-card', 720, 480, true );
	add_image_size( 'vv-persona', 400, 400, true );
}
add_action( 'after_setup_theme', 'vv_impostazioni', 20 );

/**
 * Miniature più leggere; l'originale caricato resta com'è nella libreria dei media.
 */
function vv_miniature_webp( $formati ) {
	$formati['image/jpeg'] = 'image/webp';
	$formati['image/png']  = 'image/webp';
	return $formati;
}
add_filter( 'image_editor_output_format', 'vv_miniature_webp' );

/*
 * Sydney accende da solo il costruttore header/footer quando arriva da un tema che non ha "sydney" nel
 * nome: markup che il CSS del child non conosce e footer senza widget. Resta spento senza toccare
 * l'opzione salvata; per usarlo, define( 'VV_SYDNEY_BUILDER', true ); in wp-config.php.
 */
if ( ! defined( 'VV_SYDNEY_BUILDER' ) || ! VV_SYDNEY_BUILDER ) {
	add_filter(
		'option_sydney-modules',
		static function ( $moduli ) {
			$moduli               = (array) $moduli;
			$moduli['hf-builder'] = false;
			return $moduli;
		}
	);
}

add_filter( 'sydney_show_sidebar', '__return_false' );

/**
 * A tutta larghezza: la larghezza di lettura la dà il CSS.
 */
function vv_classe_contenuti() {
	return is_singular() ? 'no-sidebar' : 'col-md-12';
}
add_filter( 'sydney_content_area_class', 'vv_classe_contenuti', 20 );

/**
 * Immagine in evidenza fra titolo e testo delle pagine. Non nelle Pagine elenco, dove spingerebbe le
 * card sotto la piega (l'immagine serve alla loro card nella pagina madre), né in home.
 */
function vv_immagine_in_testa( $valore ) {
	if ( is_front_page() ) {
		return $valore;
	}
	return is_page_template( 'page-templates/page-elenco.php' ) ? 0 : 1;
}
add_filter( 'theme_mod_enable_page_feat_images', 'vv_immagine_in_testa' );

/**
 * Casella "Senza immagine in testa": nella pagina del contenuto Sydney non trova l'immagine in evidenza;
 * card, "Da non perdere" e anteprime social la usano ancora.
 */
function vv_senza_immagine_in_testa( $ha_immagine, $contenuto = null ) {
	if ( ! $ha_immagine || is_admin() || ! is_singular( array( 'post', 'page' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $ha_immagine;
	}
	$contenuto = get_post( $contenuto );
	return ! ( $contenuto && get_queried_object_id() === $contenuto->ID && get_post_meta( $contenuto->ID, 'vv_senza_immagine', true ) );
}
add_filter( 'has_post_thumbnail', 'vv_senza_immagine_in_testa', 10, 2 );

/**
 * Per chi stampa l'immagine senza chiedere prima has_post_thumbnail().
 */
function vv_senza_immagine_in_testa_html( $html, $contenuto_id ) {
	return vv_senza_immagine_in_testa( true, $contenuto_id ) ? $html : '';
}
add_filter( 'post_thumbnail_html', 'vv_senza_immagine_in_testa_html', 10, 2 );

/**
 * Al posto dei credits del Customizer, che finivano con "Proudly powered by Sydney". Sydney sostituisce
 * {copyright} e {year}.
 */
function vv_crediti_footer() {
	return sprintf(
		'<span class="vv-credits__anno">{copyright} {year}</span> <span class="vv-credits__scritta"><img class="vv-credits__logo" src="%s" width="236" height="140" alt="VivaVoce"></span>',
		esc_url( VV_URI . '/assets/img/viva-voce-scritta.png' )
	);
}
add_filter( 'theme_mod_footer_credits', 'vv_crediti_footer' );

add_filter( 'theme_mod_social_profiles_footer', '__return_empty_string' ); // i social sono già nei widget del footer

/**
 * Senza il prefisso "Categoria:" / "Tag:".
 */
function vv_titolo_archivio( $titolo, $senza_prefisso = '' ) {
	return $senza_prefisso ? $senza_prefisso : $titolo;
}
add_filter( 'get_the_archive_title', 'vv_titolo_archivio', 10, 2 );

/**
 * Sydney e Forminator, che sul sito non ha la traduzione italiana.
 */
function vv_traduzioni_mancanti( $traduzione, $testo, $dominio ) {
	static $traduzioni = array(
		'sydney'     => array(
			'Read More' => 'Leggi',
			'Read more' => 'Leggi',
			'By'        => 'di',
		),
		'forminator' => array(
			'Choose File'                              => 'Scegli il file',
			'Choose file'                              => 'Scegli il file',
			'No file chosen'                           => 'Nessun file scelto',
			'This field is required.'                  => 'Questo campo è obbligatorio.',
			'This field is required. Please check it.' => 'Per continuare spunta questa casella.',
		),
	);
	return $traduzioni[ $dominio ][ $testo ] ?? $traduzione;
}
add_filter( 'gettext', 'vv_traduzioni_mancanti', 10, 3 );

function vv_fine_riassunto() {
	return '…';
}
add_filter( 'excerpt_more', 'vv_fine_riassunto', 20 );

/**
 * Nelle pagine senza un modulo sono peso inutile e una richiesta a Google prima del consenso.
 */
function vv_cf7_solo_dove_serve() {
	$con_modulo = is_singular() && str_contains( get_queried_object()->post_content, 'contact-form-7' );
	if ( ! defined( 'WPCF7_VERSION' ) || $con_modulo ) {
		return;
	}
	foreach ( array( 'contact-form-7', 'swv', 'wpcf7-recaptcha', 'google-recaptcha' ) as $script ) {
		wp_dequeue_script( $script );
	}
	wp_dequeue_style( 'contact-form-7' );
}
add_action( 'wp_enqueue_scripts', 'vv_cf7_solo_dove_serve', 100 );
