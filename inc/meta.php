<?php
/**
 * Campi aggiuntivi: date e luogo degli articoli-evento, categoria delle "Pagine elenco".
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/*
 * Spunta "In evidenza": il campo è quello del vecchio plugin Featured Post Creative (ora rimosso),
 * così gli articoli segnati allora restano in evidenza.
 */
const VV_FEATURED_KEY = '_wpfp_featured_post';

/**
 * Registrazione dei campi (visibili anche via REST).
 */
function vv_register_meta() {
	$date = array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'vv_sanitize_date',
		'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
	);
	register_post_meta( 'post', 'vv_data', $date );
	register_post_meta( 'post', 'vv_data_fine', $date );
	register_post_meta( 'post', 'vv_luogo', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
	) );
	register_post_meta( 'post', 'vv_senza_immagine', array(
		'type'              => 'boolean',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
		'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
	) );
	register_post_meta( 'page', 'vv_nascondi', array(
		'type'              => 'boolean',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
		'auth_callback'     => static fn() => current_user_can( 'edit_pages' ),
	) );
	register_post_meta( 'page', 'vv_categoria', array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'vv_sanitize_slugs',
		'auth_callback'     => static fn() => current_user_can( 'edit_pages' ),
	) );
}
add_action( 'init', 'vv_register_meta' );

/**
 * Data nel formato AAAA-MM-GG, altrimenti vuota.
 *
 * @param string $value Valore inserito.
 */
function vv_sanitize_date( $value ) {
	$value = trim( (string) $value );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
}

/**
 * Elenco di slug separati da virgola.
 *
 * @param string $value Valore inserito.
 */
function vv_sanitize_slugs( $value ) {
	$slugs = array_filter( array_map( 'sanitize_title', explode( ',', (string) $value ) ) );
	return implode( ',', $slugs );
}

/**
 * Riquadri nell'editor.
 */
function vv_add_meta_boxes() {
	add_meta_box( 'vv_evento', 'VivaVoce: date e luogo', 'vv_render_evento_box', 'post', 'side' );
	add_meta_box( 'vv_pagina', 'VivaVoce', 'vv_render_pagina_box', 'page', 'side' );
}
add_action( 'add_meta_boxes', 'vv_add_meta_boxes' );

/**
 * @param WP_Post $post Articolo.
 */
function vv_render_evento_box( $post ) {
	wp_nonce_field( 'vv_meta', 'vv_meta_nonce' );
	$fields = array(
		'vv_data'      => array( 'Data (o inizio)', 'date' ),
		'vv_data_fine' => array( 'Fine (facoltativa)', 'date' ),
		'vv_luogo'     => array( 'Luogo', 'text' ),
	);
	printf(
		'<p><label><input type="checkbox" name="vv_in_evidenza" value="1" %s> <strong>In evidenza</strong> (riserva: compare in home e in "Da non perdere" solo quando non ci sono eventi in calendario)</label></p>',
		checked( (string) get_post_meta( $post->ID, VV_FEATURED_KEY, true ), '1', false )
	);
	printf(
		'<p><input type="hidden" name="vv_senza_immagine" value="0"><label><input type="checkbox" name="vv_senza_immagine" value="1" %s> <strong>Senza immagine in testa</strong> (l\'immagine in evidenza resta per card, anteprime e condivisioni)</label></p>',
		checked( (bool) get_post_meta( $post->ID, 'vv_senza_immagine', true ), true, false )
	);
	foreach ( $fields as $key => list( $label, $type ) ) {
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="widefat"></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( get_post_meta( $post->ID, $key, true ) )
		);
	}
	echo '<p class="description">Le date dell\'evento decidono dove compare: in home ("…questo non potete perdervelo!", fino a 3 mesi dopo la fine), in "In programma" e nella parte "In programma" delle pagine Eventi e Corsi e laboratori finché non è finito, poi nell\'archivio dell\'anno. Senza data un evento non va né in home né in "In programma". Per un giorno solo basta la prima data.</p>';
}

/**
 * @param WP_Post $post Pagina.
 */
function vv_render_pagina_box( $post ) {
	wp_nonce_field( 'vv_meta', 'vv_meta_nonce' );
	echo '<p class="description">L\'<strong>immagine in evidenza</strong> e il <strong>riassunto</strong> di questa pagina sono quelli della sua card nella pagina madre (per esempio in Cosa facciamo). Nelle "Pagine elenco" l\'immagine non compare in testa alla pagina, ma solo nella card.</p>';
	printf(
		'<p><input type="hidden" name="vv_nascondi" value="0"><label><input type="checkbox" name="vv_nascondi" value="1" %s> Non mostrare fra le card della pagina madre</label></p>',
		checked( (bool) get_post_meta( $post->ID, 'vv_nascondi', true ), true, false )
	);
	$value = get_post_meta( $post->ID, 'vv_categoria', true );
	$cats  = get_categories( array( 'hide_empty' => false ) );
	echo '<p><label for="vv_categoria"><strong>Categorie collegate</strong></label><br>';
	printf( '<input type="text" id="vv_categoria" name="vv_categoria" value="%s" class="widefat" placeholder="es. laboratori"></p>', esc_attr( $value ) );
	echo '<p class="description">Slug separati da virgola. Con il modello "Pagina elenco" la pagina elenca questi articoli; in ogni caso gli articoli di queste categorie mostrano la pagina nel breadcrumb. Disponibili: ';
	echo esc_html( implode( ', ', wp_list_pluck( $cats, 'slug' ) ) );
	echo '</p>';
}

/**
 * @param int $post_id ID del contenuto.
 */
function vv_save_meta( $post_id ) {
	if ( ! isset( $_POST['vv_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['vv_meta_nonce'] ), 'vv_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'post' === get_post_type( $post_id ) ) {
		update_post_meta( $post_id, VV_FEATURED_KEY, empty( $_POST['vv_in_evidenza'] ) ? 0 : 1 );
	}
	$keys = 'page' === get_post_type( $post_id ) ? array( 'vv_categoria', 'vv_nascondi' ) : array( 'vv_data', 'vv_data_fine', 'vv_luogo', 'vv_senza_immagine' );
	foreach ( $keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		// La sanificazione è quella registrata in register_post_meta().
		$value = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// Caselle: salvate solo se spuntate, altrimenti il campo si toglie.
		if ( '' === trim( (string) $value ) || ( in_array( $key, array( 'vv_nascondi', 'vv_senza_immagine' ), true ) && '1' !== $value ) ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post', 'vv_save_meta' );

/**
 * Colonna "In evidenza" (stellina) nell'elenco articoli.
 *
 * @param string[] $columns Colonne.
 */
function vv_featured_column( $columns ) {
	$columns['vv_in_evidenza'] = '<span class="dashicons dashicons-star-filled" title="In evidenza"></span><span class="screen-reader-text">In evidenza</span>';
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'vv_featured_column' );

/**
 * @param string $column  Colonna.
 * @param int    $post_id Articolo.
 */
function vv_featured_column_value( $column, $post_id ) {
	if ( 'vv_in_evidenza' === $column ) {
		$on = '1' === (string) get_post_meta( $post_id, VV_FEATURED_KEY, true );
		printf( '<span class="dashicons %s" aria-label="%s"></span>', $on ? 'dashicons-star-filled' : 'dashicons-star-empty', $on ? 'In evidenza' : 'Non in evidenza' );
	}
}
add_action( 'manage_post_posts_custom_column', 'vv_featured_column_value', 10, 2 );

/**
 * Con l'editor classico "Immagine in evidenza" e "Riassunto" sono riquadri che si possono nascondere
 * da "Impostazioni schermata" (il riassunto è nascosto di default): servono alle card, restano sempre visibili.
 *
 * @param string[]  $hidden Riquadri nascosti.
 * @param WP_Screen $screen Schermata.
 * @return string[]
 */
function vv_always_show_card_boxes( $hidden, $screen ) {
	if ( $screen && in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
		$hidden = array_values( array_diff( (array) $hidden, array( 'postimagediv', 'postexcerpt' ) ) );
	}
	return $hidden;
}
add_filter( 'hidden_meta_boxes', 'vv_always_show_card_boxes', 10, 2 );
