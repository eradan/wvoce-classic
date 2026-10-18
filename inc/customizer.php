<?php
/**
 * Pannello "VivaVoce" nel Customizer: hero della home e dati dell'associazione.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valori predefiniti delle impostazioni del child.
 */
function vv_defaults() {
	return array(
		'vv_hero_enable'    => true,
		'vv_hero_title'     => 'Lettura ad alta voce, teatro e creatività',
		'vv_hero_cta1_text' => 'Prossimi appuntamenti',
		'vv_hero_cta1_url'  => '#appuntamenti',
		'vv_hero_cta2_text' => 'Diventa socio',
		'vv_hero_cta2_url'  => home_url( '/cosa-puoi-fare/diventa-socio/' ),
		'vv_hero_image'     => '',
		'vv_cf'             => '93096450502',
		'vv_piva'           => '02441190507',
		'vv_iban'           => 'IT47D0501802800000017134545',
		'vv_nome_legale'    => 'Associazione VivaVoce APS',
		'vv_indirizzo'      => 'Via G. Mazzini 112',
		'vv_cap'            => '56125',
		'vv_citta'          => 'Pisa',
		'vv_telefono'       => '+39 338 1169298',
		'vv_email'          => 'info@wvoce.it',
		'vv_facebook'       => 'https://www.facebook.com/associazionevivavoce',
		'vv_instagram'      => 'https://www.instagram.com/associazione_vivavoce/',
		'vv_url_socio'      => home_url( '/cosa-puoi-fare/diventa-socio/' ),
		'vv_url_donazione'  => home_url( '/cosa-puoi-fare/fai-una-donazione/' ),
	);
}

/**
 * Legge un'impostazione del child con il suo valore predefinito.
 *
 * @param string $key Nome dell'impostazione.
 */
function vv_mod( $key ) {
	$defaults = vv_defaults();
	return get_theme_mod( $key, $defaults[ $key ] ?? '' );
}

/**
 * Immagine dell'hero: quella scelta nel pannello VivaVoce oppure la prima slide di Sydney.
 */
function vv_hero_image_url() {
	$url = vv_mod( 'vv_hero_image' );
	if ( ! $url ) {
		$url = get_theme_mod( 'slider_image_1', '' );
	}
	return $url;
}

/**
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function vv_customize_register( $wp_customize ) {
	$defaults = vv_defaults();

	$wp_customize->add_panel( 'vv_panel', array(
		'title'    => 'VivaVoce',
		'priority' => 1,
	) );
	$wp_customize->add_section( 'vv_hero', array(
		'title'       => 'Hero della home',
		'panel'       => 'vv_panel',
		'description' => 'Sostituisce lo slider di Sydney nella home.',
	) );
	$wp_customize->add_section( 'vv_dati', array(
		'title'       => 'Dati dell\'associazione',
		'panel'       => 'vv_panel',
		'description' => 'Valgono per il sito: piè di pagina e modello delle email, dati strutturati, pulsante "Diventa socio" della home. Per le iscrizioni il plugin wvoce-iscrizioni ha la sua email di contatto e le sue pagine dei moduli (Strumenti → Iscrizioni → Collegamenti): se cambiano, vanno cambiate anche lì.',
	) );

	$text_fields = array(
		'vv_hero_title'     => array( 'vv_hero', 'Titolo (per screen reader e motori di ricerca)', 'text' ),
		'vv_hero_cta1_text' => array( 'vv_hero', 'Pulsante 1: testo', 'text' ),
		'vv_hero_cta1_url'  => array( 'vv_hero', 'Pulsante 1: link', 'url' ),
		'vv_hero_cta2_text' => array( 'vv_hero', 'Pulsante 2: testo', 'text' ),
		'vv_hero_cta2_url'  => array( 'vv_hero', 'Pulsante 2: link', 'url' ),
		'vv_cf'             => array( 'vv_dati', 'Codice fiscale (5×1000)', 'text' ),
		'vv_piva'           => array( 'vv_dati', 'Partita IVA', 'text' ),
		'vv_iban'           => array( 'vv_dati', 'IBAN', 'text' ),
		'vv_nome_legale'    => array( 'vv_dati', 'Ragione sociale', 'text' ),
		'vv_indirizzo'      => array( 'vv_dati', 'Sede: via e numero', 'text' ),
		'vv_cap'            => array( 'vv_dati', 'Sede: CAP', 'text' ),
		'vv_citta'          => array( 'vv_dati', 'Sede: città', 'text' ),
		'vv_telefono'       => array( 'vv_dati', 'Telefono (con +39)', 'text' ),
		'vv_email'          => array( 'vv_dati', 'Email', 'text' ),
		'vv_facebook'       => array( 'vv_dati', 'Pagina Facebook', 'url' ),
		'vv_instagram'      => array( 'vv_dati', 'Profilo Instagram', 'url' ),
		'vv_url_socio'      => array( 'vv_dati', 'Pagina "Diventa socio"', 'url' ),
		'vv_url_donazione'  => array( 'vv_dati', 'Pagina "Fai una donazione"', 'url' ),
	);

	$wp_customize->add_setting( 'vv_hero_enable', array(
		'default'           => $defaults['vv_hero_enable'],
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'vv_hero_enable', array(
		'section' => 'vv_hero',
		'label'   => 'Usa l\'hero VivaVoce',
		'type'    => 'checkbox',
	) );

	foreach ( $text_fields as $id => list( $section, $label, $type ) ) {
		$sanitize = 'url' === $type ? 'vv_sanitize_link' : 'sanitize_text_field';
		$wp_customize->add_setting( $id, array(
			'default'           => $defaults[ $id ],
			'sanitize_callback' => $sanitize,
		) );
		$wp_customize->add_control( $id, array(
			'section' => $section,
			'label'   => $label,
			'type'    => 'text',
		) );
	}

	$wp_customize->add_setting( 'vv_hero_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vv_hero_image', array(
		'section'     => 'vv_hero',
		'label'       => 'Illustrazione',
		'description' => 'Se vuota si usa la prima immagine dello slider di Sydney.',
	) ) );
}
add_action( 'customize_register', 'vv_customize_register' );

/**
 * Link: URL completi oppure ancore nella pagina (#appuntamenti).
 *
 * @param string $value Valore inserito.
 */
function vv_sanitize_link( $value ) {
	$value = trim( (string) $value );
	if ( '' !== $value && '#' === $value[0] ) {
		return '#' . sanitize_html_class( substr( $value, 1 ) );
	}
	return esc_url_raw( $value );
}
