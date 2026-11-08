<?php
/**
 * Campi aggiuntivi e riquadri dell'editor: date e luogo degli eventi, "Senza immagine in testa",
 * categorie collegate delle pagine.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

function vv_registra_campi() {
	$campi = array(
		// Tipo di contenuto, campo, tipo del valore, sanificazione, permesso.
		array( 'post', 'vv_data', 'string', 'vv_pulisci_data', 'edit_posts' ),
		array( 'post', 'vv_data_fine', 'string', 'vv_pulisci_data', 'edit_posts' ),
		array( 'post', 'vv_luogo', 'string', 'sanitize_text_field', 'edit_posts' ),
		array( 'post', 'vv_senza_immagine', 'boolean', 'rest_sanitize_boolean', 'edit_posts' ),
		array( 'page', 'vv_senza_immagine', 'boolean', 'rest_sanitize_boolean', 'edit_pages' ),
		array( 'page', 'vv_categoria', 'string', 'vv_pulisci_slug', 'edit_pages' ),
	);
	foreach ( $campi as list( $tipo_contenuto, $chiave, $tipo, $pulisci, $permesso ) ) {
		register_post_meta( $tipo_contenuto, $chiave, array(
			'type'              => $tipo,
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => $pulisci,
			'auth_callback'     => static fn() => current_user_can( $permesso ),
		) );
	}
}
add_action( 'init', 'vv_registra_campi' );

/**
 * AAAA-MM-GG di un giorno che esiste, altrimenti vuota.
 */
function vv_pulisci_data( $valore ) {
	$valore = trim( (string) $valore );
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $valore, $parti ) ) {
		return '';
	}
	return checkdate( (int) $parti[2], (int) $parti[3], (int) $parti[1] ) ? $valore : '';
}

function vv_pulisci_slug( $valore ) {
	return implode( ',', array_filter( array_map( 'sanitize_title', explode( ',', (string) $valore ) ) ) );
}

function vv_aggiungi_riquadri() {
	add_meta_box( 'vv_evento', 'VivaVoce: date e luogo', 'vv_riquadro_evento', 'post', 'side' );
	add_meta_box( 'vv_pagina', 'VivaVoce', 'vv_riquadro_pagina', 'page', 'side' );
}
add_action( 'add_meta_boxes', 'vv_aggiungi_riquadri' );

function vv_riquadro_evento( WP_Post $articolo ) {
	wp_nonce_field( 'vv_meta', 'vv_meta_nonce' );
	vv_casella_senza_immagine( $articolo );
	$campi = array(
		'vv_data'      => array( 'Data (o inizio)', 'date' ),
		'vv_data_fine' => array( 'Fine (facoltativa)', 'date' ),
		'vv_luogo'     => array( 'Luogo', 'text' ),
	);
	foreach ( $campi as $chiave => list( $etichetta, $tipo ) ) {
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="widefat"></p>',
			esc_attr( $chiave ),
			esc_html( $etichetta ),
			esc_attr( $tipo ),
			esc_attr( get_post_meta( $articolo->ID, $chiave, true ) )
		);
	}
	echo '<p class="description">Le date decidono dove compare l\'evento: in home fino a 3 mesi dopo la fine, in "In programma" e nella parte "In programma" delle Pagine elenco finché non è finito, poi nell\'archivio dell\'anno. Senza data non va né in home né in "In programma". Per un giorno solo basta la prima data.</p>';
}

function vv_casella_senza_immagine( WP_Post $contenuto ) {
	printf(
		'<p><input type="hidden" name="vv_senza_immagine" value="0"><label><input type="checkbox" name="vv_senza_immagine" value="1" %s> <strong>Senza immagine in testa</strong> (l\'immagine in evidenza resta per card, anteprime e condivisioni)</label></p>',
		checked( (bool) get_post_meta( $contenuto->ID, 'vv_senza_immagine', true ), true, false )
	);
}

function vv_riquadro_pagina( WP_Post $pagina ) {
	wp_nonce_field( 'vv_meta', 'vv_meta_nonce' );
	echo '<p class="description">L\'<strong>immagine in evidenza</strong> e il <strong>riassunto</strong> di questa pagina sono quelli della sua card nella pagina madre (per esempio in Cosa facciamo). Nelle "Pagine elenco" l\'immagine non compare in testa alla pagina, ma solo nella card.</p>';
	vv_casella_senza_immagine( $pagina );
	echo '<p><label for="vv_categoria"><strong>Categorie collegate</strong></label><br>';
	printf( '<input type="text" id="vv_categoria" name="vv_categoria" value="%s" class="widefat" placeholder="es. laboratori"></p>', esc_attr( get_post_meta( $pagina->ID, 'vv_categoria', true ) ) );
	echo '<p class="description">Slug separati da virgola. Con il modello "Pagina elenco" la pagina elenca questi articoli; in ogni caso gli articoli di queste categorie mostrano la pagina nel percorso in alto. Disponibili: ';
	echo esc_html( implode( ', ', wp_list_pluck( get_categories( array( 'hide_empty' => false ) ), 'slug' ) ) );
	echo '</p>';
}

/**
 * La casella si salva solo spuntata; i testi passano dalla sanificazione di register_post_meta().
 */
function vv_salva_campi( $contenuto_id ) {
	$autorizzato = wp_verify_nonce( sanitize_key( $_POST['vv_meta_nonce'] ?? '' ), 'vv_meta' ) && current_user_can( 'edit_post', $contenuto_id );
	if ( ! $autorizzato || wp_is_post_revision( $contenuto_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	if ( '1' === ( $_POST['vv_senza_immagine'] ?? '' ) ) {
		update_post_meta( $contenuto_id, 'vv_senza_immagine', 1 );
	} else {
		delete_post_meta( $contenuto_id, 'vv_senza_immagine' );
	}
	$testi = 'page' === get_post_type( $contenuto_id ) ? array( 'vv_categoria' ) : array( 'vv_data', 'vv_data_fine', 'vv_luogo' );
	foreach ( $testi as $chiave ) {
		if ( ! isset( $_POST[ $chiave ] ) ) {
			continue;
		}
		$valore = trim( (string) wp_unslash( $_POST[ $chiave ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $valore ) {
			delete_post_meta( $contenuto_id, $chiave );
		} else {
			update_post_meta( $contenuto_id, $chiave, $valore );
		}
	}
}
add_action( 'save_post', 'vv_salva_campi' );

/**
 * Immagine in evidenza e riassunto servono alle card: con l'editor classico restano visibili anche se
 * nascosti da "Impostazioni schermata" (il riassunto lo è di default).
 */
function vv_riquadri_card_visibili( $nascosti, $schermata ) {
	if ( $schermata && in_array( $schermata->post_type, array( 'post', 'page' ), true ) ) {
		$nascosti = array_values( array_diff( (array) $nascosti, array( 'postimagediv', 'postexcerpt' ) ) );
	}
	return $nascosti;
}
add_filter( 'hidden_meta_boxes', 'vv_riquadri_card_visibili', 10, 2 );
