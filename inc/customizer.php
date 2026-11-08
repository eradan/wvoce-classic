<?php
/**
 * Pannello "VivaVoce" del Customizer e le funzioni che ne leggono i valori.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_predefiniti() {
	static $predefiniti = null;

	$predefiniti ??= array(
		'vv_hero_enable'    => true,
		'vv_hero_title'     => 'Lettura ad alta voce, teatro e creatività',
		'vv_hero_cta1_text' => 'Prossimi appuntamenti',
		'vv_hero_cta1_url'  => '#appuntamenti',
		'vv_hero_cta2_text' => 'Diventa socio',
		'vv_hero_cta2_url'  => '', // vuoto: la pagina "Diventa socio"
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
		'vv_img_5x1000'     => 0,
		'vv_palette'        => '',
	);
	return $predefiniti;
}

/**
 * Una per file in assets/css/palette/, con il nome del file come chiave.
 */
function vv_palette_disponibili() {
	return array(
		''       => 'Predefinita',
		'sabbia' => 'Sabbia: pagina color carta e schede bianche',
		'natale' => 'Natale: verde, rosso, oro e neve che scende',
	);
}

/**
 * Solo le palette conosciute: il valore finisce nel percorso del foglio di stile.
 */
function vv_pulisci_palette( $valore ) {
	return is_string( $valore ) && array_key_exists( $valore, vv_palette_disponibili() ) ? $valore : '';
}

function vv_mod( $chiave ) {
	return get_theme_mod( $chiave, vv_predefiniti()[ $chiave ] ?? '' );
}

/**
 * Fino alla 1.4 la pagina era un indirizzo scritto a mano (vv_url_socio, vv_url_donazione): finché non
 * se ne sceglie una vale quello.
 *
 * @param string $quale socio | donazione.
 * @return int 0 se non c'è.
 */
function vv_pagina_id( $quale ) {
	$id = absint( get_theme_mod( 'vv_pagina_' . $quale, 0 ) );
	if ( ! $id ) {
		$vecchi = array(
			'socio'     => '/cosa-puoi-fare/diventa-socio/',
			'donazione' => '/cosa-puoi-fare/fai-una-donazione/',
		);
		$id     = url_to_postid( (string) get_theme_mod( 'vv_url_' . $quale, home_url( $vecchi[ $quale ] ?? '/' ) ) );
	}
	return $id;
}

/**
 * @param string $quale socio | donazione.
 */
function vv_pagina_url( $quale ) {
	$id = vv_pagina_id( $quale );
	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * Senza "www.": wvoce.it.
 */
function vv_dominio() {
	return preg_replace( '/^www\./', '', (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
}

function vv_registra_customizer( WP_Customize_Manager $wp_customize ) {
	$predefiniti = vv_predefiniti();

	$wp_customize->add_panel( 'vv_panel', array(
		'title'    => 'VivaVoce',
		'priority' => 1,
	) );
	$wp_customize->add_section( 'vv_hero', array(
		'title'       => 'Hero della home',
		'panel'       => 'vv_panel',
		'description' => 'Sostituisce lo slider di Sydney nella home.',
	) );
	$wp_customize->add_section( 'vv_palette', array(
		'title'       => 'Palette del sito',
		'panel'       => 'vv_panel',
		'description' => 'Colori per un periodo o una palette alternativa, per tutti i visitatori. Natale: sito scuro con oro, verde, rosso e neve; chi vuole lo spegne dal pulsante del tema in testata e torna al chiaro o allo scuro di sempre. Sabbia vale solo nel tema chiaro. Con "Pianifica" (accanto a "Pubblica") si programma quando accenderla e quando tornare alla predefinita.',
	) );
	$wp_customize->add_section( 'vv_dati', array(
		'title'       => 'Dati dell\'associazione',
		'panel'       => 'vv_panel',
		'description' => 'Finiscono nel piè di pagina delle email, nei dati strutturati per i motori di ricerca, negli shortcode [vv_dati_fiscali] e [vv_sostienici] e nel pulsante 2 dell\'hero quando il suo link è vuoto. I contatti nel piè di pagina del sito sono invece nei widget (Aspetto → Widget). Il plugin wvoce-iscrizioni ha la sua email di contatto e la sua pagina "Diventa socio" (Strumenti → Iscrizioni → Collegamenti): se cambiano, vanno cambiate anche lì.',
	) );

	// Tipo: text, link (indirizzo o #ancora della pagina), url, email.
	$text_fields   = array(
		'vv_hero_title'     => array( 'vv_hero', 'Titolo', 'text', 'Non si vede: serve a screen reader e motori di ricerca, ed è la descrizione della home nelle anteprime social se manca il motto del sito (Impostazioni → Generali).' ),
		'vv_hero_cta1_text' => array( 'vv_hero', 'Pulsante 1: testo', 'text', '' ),
		'vv_hero_cta1_url'  => array( 'vv_hero', 'Pulsante 1: link', 'link', 'Un indirizzo o un\'ancora: #appuntamenti porta al punto della home con id="appuntamenti", che deve esserci nel suo contenuto.' ),
		'vv_hero_cta2_text' => array( 'vv_hero', 'Pulsante 2: testo', 'text', '' ),
		'vv_hero_cta2_url'  => array( 'vv_hero', 'Pulsante 2: link', 'link', 'Vuoto: la pagina "Diventa socio" scelta in Dati dell\'associazione.' ),
		'vv_cf'             => array( 'vv_dati', 'Codice fiscale (5×1000)', 'text', '' ),
		'vv_piva'           => array( 'vv_dati', 'Partita IVA', 'text', '' ),
		'vv_iban'           => array( 'vv_dati', 'IBAN', 'text', '' ),
		'vv_nome_legale'    => array( 'vv_dati', 'Ragione sociale', 'text', '' ),
		'vv_indirizzo'      => array( 'vv_dati', 'Sede: via e numero', 'text', '' ),
		'vv_cap'            => array( 'vv_dati', 'Sede: CAP', 'text', '' ),
		'vv_citta'          => array( 'vv_dati', 'Sede: città', 'text', '' ),
		'vv_telefono'       => array( 'vv_dati', 'Telefono (con +39)', 'text', 'Solo nei dati strutturati per i motori di ricerca.' ),
		'vv_email'          => array( 'vv_dati', 'Email', 'email', '' ),
		'vv_facebook'       => array( 'vv_dati', 'Pagina Facebook', 'url', '' ),
		'vv_instagram'      => array( 'vv_dati', 'Profilo Instagram', 'url', '' ),
	);
	$sanificazioni = array(
		'text'  => 'sanitize_text_field',
		'link'  => 'vv_pulisci_link',
		'url'   => 'esc_url_raw',
		'email' => 'sanitize_email',
	);

	$wp_customize->add_setting( 'vv_palette', array(
		'default'           => $predefiniti['vv_palette'],
		'sanitize_callback' => 'vv_pulisci_palette',
	) );
	$wp_customize->add_control( 'vv_palette', array(
		'section' => 'vv_palette',
		'label'   => 'Palette',
		'type'    => 'select',
		'choices' => vv_palette_disponibili(),
	) );

	$wp_customize->add_setting( 'vv_hero_enable', array(
		'default'           => $predefiniti['vv_hero_enable'],
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'vv_hero_enable', array(
		'section' => 'vv_hero',
		'label'   => 'Usa l\'hero VivaVoce',
		'type'    => 'checkbox',
	) );

	foreach ( $text_fields as $id => list( $section, $label, $type, $description ) ) {
		$wp_customize->add_setting( $id, array(
			'default'           => $predefiniti[ $id ],
			'sanitize_callback' => $sanificazioni[ $type ],
		) );
		$wp_customize->add_control( $id, array(
			'section'     => $section,
			'label'       => $label,
			'description' => $description,
			'type'        => 'link' === $type ? 'text' : $type,
		) );
	}

	$pagine = array(
		'socio'     => 'Pagina "Diventa socio"',
		'donazione' => 'Pagina "Fai una donazione"',
	);
	foreach ( $pagine as $quale => $label ) {
		$wp_customize->add_setting( 'vv_pagina_' . $quale, array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		) );
		$wp_customize->add_control( 'vv_pagina_' . $quale, array(
			'section'     => 'vv_dati',
			'label'       => $label,
			'description' => 'Titolo, riassunto e immagine in evidenza della pagina fanno il suo riquadro in [vv_sostienici].',
			'type'        => 'dropdown-pages',
		) );
	}

	$wp_customize->add_setting( 'vv_hero_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vv_hero_image', array(
		'section'     => 'vv_hero',
		'label'       => 'Illustrazione',
		'description' => 'Se vuota si usa la prima immagine dello slider di Sydney. Testa, mano e trattini si animano solo con l\'illustrazione originale (il file vivavoce-logo-pearl); con un\'altra immagine resta ferma.',
	) ) );

	$wp_customize->add_setting( 'vv_img_5x1000', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'vv_img_5x1000', array(
		'section'     => 'vv_dati',
		'label'       => 'Immagine del 5×1000',
		'description' => 'Nel riquadro del 5×1000 di [vv_sostienici], che non ha una pagina sua.',
		'mime_type'   => 'image',
	) ) );
}
add_action( 'customize_register', 'vv_registra_customizer' );

/**
 * Un indirizzo completo o un'ancora della pagina (#appuntamenti).
 */
function vv_pulisci_link( $valore ) {
	$valore = trim( (string) $valore );
	if ( str_starts_with( $valore, '#' ) ) {
		return '#' . sanitize_html_class( substr( $valore, 1 ) );
	}
	return esc_url_raw( $valore );
}
