<?php
/**
 * Forminator: controllo vero del codice fiscale (il plugin conta solo i caratteri) e, per anno
 * sociale, dell'iscrizione a socio: richiesta per i corsi, una sola volta per il modulo dei soci.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Moduli controllati: id del modulo => regola sul socio dell'anno. I moduli sono quelli scelti in
 * Strumenti → Esporta iscrizioni → Configurazione (oggi 274 "Iscrizione Socio" e 1617 "Iscrizione ai
 * corsi 2026-2027"), così il controllo segue anche il modulo nuovo di ogni anno.
 * - "richiesto" (corsi): il codice fiscale deve comparire fra le iscrizioni del modulo dei soci dal
 *   1° settembre dell'anno sociale in corso;
 * - "unico" (soci): se compare già, il modulo si ferma.
 *
 * @return array<int,string>
 */
function vv_forminator_moduli() {
	$moduli = array();
	foreach ( array( 'corsi' => 'richiesto', 'soci' => 'unico' ) as $id => $regola ) {
		$form = (int) vv_sheets_config( $id )['form'];
		if ( $form > 0 ) {
			$moduli[ $form ] = $regola;
		}
	}
	return $moduli;
}

/**
 * Messaggio per chi si iscrive a un corso prima di essersi iscritto all'associazione.
 *
 * @param string $adesso Data di riferimento, "Y-m-d H:i:s".
 */
function vv_forminator_msg_socio( $adesso ) {
	$anno = vv_anno_sociale( $adesso );
	return sprintf(
		'Questo codice fiscale manca ancora fra i soci %1$d-%2$d: i corsi sono riservati ai soci. Prima occorre iscriversi all\'associazione con il <a href="%3$s" target="_blank" rel="noopener">modulo di iscrizione a socio</a>, poi si torna a questa pagina.',
		$anno,
		$anno + 1,
		esc_url( vv_mod( 'vv_url_socio' ) )
	);
}

/**
 * Messaggio per chi si iscrive a socio una seconda volta nello stesso anno sociale.
 *
 * @param string $adesso Data di riferimento, "Y-m-d H:i:s".
 */
function vv_forminator_msg_doppio( $adesso ) {
	$anno = vv_anno_sociale( $adesso );
	return sprintf(
		'Questo codice fiscale risulta già fra i soci %1$d-%2$d: l\'iscrizione di quest\'anno è già registrata in anagrafica. Per domande o per correggere i dati scrivi a <a href="mailto:%3$s">%3$s</a>.',
		$anno,
		$anno + 1,
		'info@wvoce.it'
	);
}

/**
 * Codice fiscale di una persona: 16 caratteri nel formato giusto (anche con le lettere al posto
 * dei numeri dei casi di omocodia) e carattere di controllo corretto.
 *
 * @param string $cf Valore inserito.
 */
function vv_codice_fiscale_valido( $cf ) {
	$cf = strtoupper( preg_replace( '/\s+/', '', (string) $cf ) );
	if ( ! preg_match( '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/', $cf ) ) {
		return false;
	}
	// Valori dei caratteri in posizione dispari (1ª, 3ª…): cifre e lettere A–Z hanno la stessa tabella.
	$dispari = array( 1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23 );
	$somma   = 0;
	for ( $i = 0; $i < 15; $i++ ) {
		$n      = ctype_digit( $cf[ $i ] ) ? (int) $cf[ $i ] : ord( $cf[ $i ] ) - 65;
		$somma += 0 === $i % 2 ? $dispari[ $n ] : $n;
	}
	return chr( 65 + $somma % 26 ) === $cf[15];
}

/**
 * Errore accanto al campo quando il codice fiscale non torna; nel modulo dei corsi quando manca fra i
 * soci dell'anno, in quello dei soci quando c'è già (il modulo non viene inviato).
 *
 * @param array $submit_errors    Errori già trovati da Forminator.
 * @param int   $form_id          Id del modulo.
 * @param array $field_data_array Campi inviati: array( 'name' => id del campo, 'value' => valore ).
 */
function vv_forminator_check_cf( $submit_errors, $form_id, $field_data_array ) {
	$regola = vv_forminator_moduli()[ (int) $form_id ] ?? '';
	if ( '' === $regola || ! is_array( $field_data_array ) ) {
		return $submit_errors;
	}
	if ( ! is_array( $submit_errors ) ) {
		$submit_errors = array();
	}
	foreach ( $field_data_array as $item ) {
		$value = is_array( $item ) && VV_CAMPO_CF === ( $item['name'] ?? '' ) ? trim( (string) ( $item['value'] ?? '' ) ) : '';
		if ( '' === $value ) {
			continue;
		}
		$adesso = current_time( 'mysql' );
		if ( ! vv_codice_fiscale_valido( $value ) ) {
			$submit_errors[][ VV_CAMPO_CF ] = 'Controlla il codice fiscale: sono 16 lettere e numeri, come sulla tessera sanitaria.';
		} elseif ( 'richiesto' === $regola && ! vv_socio_iscritto( $value, $adesso ) ) {
			$submit_errors[][ VV_CAMPO_CF ] = vv_forminator_msg_socio( $adesso );
		} elseif ( 'unico' === $regola && vv_socio_iscritto( $value, $adesso ) ) {
			// L'iscrizione in corso entra nel database dopo questo controllo: si trovano solo le precedenti.
			$submit_errors[][ VV_CAMPO_CF ] = vv_forminator_msg_doppio( $adesso );
		}
	}
	return $submit_errors;
}
add_filter( 'forminator_custom_form_submit_errors', 'vv_forminator_check_cf', 15, 3 );

/**
 * Nel campo del codice fiscale le lettere si vedono maiuscole mentre si scrive.
 */
function vv_forminator_cf_style() {
	$rules = array();
	foreach ( array_keys( vv_forminator_moduli() ) as $form_id ) {
		$rules[] = sprintf( '#forminator-module-%d input[name="%s"]', (int) $form_id, VV_CAMPO_CF );
	}
	if ( $rules ) {
		wp_add_inline_style( 'vv-plugins', implode( ",\n", $rules ) . " {\n\ttext-transform: uppercase;\n}" );
	}
}
add_action( 'wp_enqueue_scripts', 'vv_forminator_cf_style', 21 );

/*
 * Dal modulo dei soci a quello dei corsi. Chi nel modulo dei soci risponde di volersi iscrivere anche
 * a un corso viene mandato da Forminator (comportamento dopo l'invio, rinvio con condizione) alla
 * pagina dei corsi; il tema aggiunge all'indirizzo un codice usa e getta (?vv=…) e lì ricompila il
 * modulo dei corsi con i dati appena inviati, caselle comprese: restano da scegliere i corsi e da
 * caricare la ricevuta. I dati restano sul server (transient di due ore), mai nell'indirizzo.
 */

/**
 * Campi ripresi: hanno lo stesso id nel modulo dei soci e in quello dei corsi.
 */
function vv_corso_campi() {
	return array( 'name-1', 'name-2', VV_CAMPO_CF, 'email-1', 'phone-2' );
}

/*
 * Caselle del modulo dei corsi già accettate con l'iscrizione a socio: Regolamento ("Statuto e
 * Regolamento"), Socio, Privacy ("GDPR").
 */
const VV_CORSO_CASELLE = array( 'consent-1', 'consent-2', 'consent-3' );

/*
 * Durata del codice usa e getta, in secondi: due ore.
 */
const VV_CORSO_DURATA = 7200;

/**
 * Campi inviati al modulo dei soci, tenuti da parte per il rinvio alla pagina dei corsi.
 *
 * @param array|null $campi Campi da tenere (null: solo lettura).
 * @return array Campi tenuti: id => valore.
 */
function vv_corso_inviati( $campi = null ) {
	static $tenuti = array();
	if ( null !== $campi ) {
		$tenuti = $campi;
	}
	return $tenuti;
}

/**
 * Tiene da parte i campi ripresi da un invio del modulo dei soci.
 *
 * @param array $field_data_array Campi inviati: array( 'name' => id del campo, 'value' => valore ).
 * @param int   $form_id          Id del modulo.
 */
function vv_corso_tieni_campi( $field_data_array, $form_id ) {
	if ( (int) vv_sheets_config( 'soci' )['form'] === (int) $form_id && is_array( $field_data_array ) ) {
		$campi = array();
		foreach ( $field_data_array as $item ) {
			$id = is_array( $item ) ? (string) ( $item['name'] ?? '' ) : '';
			if ( in_array( $id, vv_corso_campi(), true ) ) {
				$campi[ $id ] = vv_sheets_valore( $item['value'] ?? '', $id );
			}
		}
		vv_corso_inviati( $campi );
	}
	return $field_data_array;
}
add_filter( 'forminator_custom_form_submit_field_data', 'vv_corso_tieni_campi', 10, 2 );

/**
 * L'indirizzo porta alla pagina dei corsi (vv_url_corso nel Customizer)?
 *
 * @param string $url Indirizzo.
 */
function vv_corso_e_pagina( $url ) {
	$path = static fn( $u ) => trailingslashit( (string) wp_parse_url( (string) $u, PHP_URL_PATH ) );
	return '' !== (string) $url && $path( $url ) === $path( vv_mod( 'vv_url_corso' ) );
}

/**
 * Invio riuscito del modulo dei soci con rinvio alla pagina dei corsi: i campi ripresi vanno in un
 * transient e il codice nell'indirizzo del rinvio.
 *
 * @param array $response Risposta di Forminator (success, url…).
 * @param int   $form_id  Id del modulo.
 */
function vv_corso_rinvio( $response, $form_id ) {
	$campi = vv_corso_inviati();
	if ( ! is_array( $response ) || empty( $response['success'] ) || empty( $response['url'] ) || ! $campi
		|| (int) vv_sheets_config( 'soci' )['form'] !== (int) $form_id || ! vv_corso_e_pagina( $response['url'] ) ) {
		return $response;
	}
	$codice = bin2hex( random_bytes( 16 ) );
	set_transient( 'vv_corso_' . $codice, $campi, VV_CORSO_DURATA );
	$response['url'] = add_query_arg( 'vv', $codice, $response['url'] );
	vv_corso_inviati( array() );
	return $response;
}
add_filter( 'forminator_form_ajax_submit_response', 'vv_corso_rinvio', 10, 2 );
add_filter( 'forminator_form_submit_response', 'vv_corso_rinvio', 10, 2 );

/**
 * Dati da ricompilare per un codice, o null se il codice non è valido o è scaduto.
 *
 * @param mixed $codice Codice dall'indirizzo.
 * @return array|null
 */
function vv_corso_dati( $codice ) {
	if ( ! is_string( $codice ) || ! preg_match( '/^[0-9a-f]{32}$/', $codice ) ) {
		return null;
	}
	$campi = get_transient( 'vv_corso_' . $codice );
	return is_array( $campi ) && $campi ? $campi : null;
}

/**
 * Pagina dei corsi aperta con un codice valido: niente cache (dentro ci sono dati personali) e, in
 * fondo alla pagina, i dati per la ricompilazione del modulo dei corsi (assets/js/vv.js).
 */
function vv_corso_pagina() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Il codice stesso fa da gettone.
	$campi = isset( $_GET['vv'] ) ? vv_corso_dati( sanitize_key( wp_unslash( $_GET['vv'] ) ) ) : null;
	if ( ! $campi ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	add_action(
		'wp_footer',
		static function () use ( $campi ) {
			$dati = array(
				'form'    => (int) vv_sheets_config( 'corsi' )['form'],
				'campi'   => $campi,
				'caselle' => VV_CORSO_CASELLE,
			);
			printf( '<script id="vv-corso-dati" type="application/json">%s</script>' . "\n", wp_json_encode( $dati, JSON_HEX_TAG | JSON_HEX_AMP ) );
		}
	);
}
add_action( 'template_redirect', 'vv_corso_pagina' );
