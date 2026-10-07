<?php
/**
 * Forminator: controllo vero del codice fiscale (il plugin conta solo i caratteri) e, per anno
 * sociale, dell'iscrizione a socio: richiesta per i corsi, una sola volta per il modulo dei soci.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Campi "codice fiscale" da controllare: id del modulo => id del campo.
 * 1617 è "Iscrizione ai corsi 2026-2027" (pagina "Iscriviti a un corso"), 274 "Iscrizione Socio".
 */
function vv_forminator_cf_fields() {
	return apply_filters(
		'vv_forminator_cf_fields',
		array(
			1617 => 'text-1',
			274  => 'text-1',
		)
	);
}

/**
 * Moduli riservati ai soci dell'anno: id del modulo => id del campo con il codice fiscale. Il codice
 * deve comparire fra le iscrizioni del modulo dei soci dal 1° settembre dell'anno sociale in corso.
 * Un filtro che restituisce un array vuoto spegne il controllo.
 */
function vv_forminator_soci_richiesti() {
	return apply_filters( 'vv_forminator_soci_richiesti', array( 1617 => 'text-1' ) );
}

/**
 * Moduli in cui un socio si iscrive una volta per anno sociale: id del modulo => id del campo con il
 * codice fiscale. Se il codice compare già fra le iscrizioni del modulo dei soci dal 1° settembre,
 * il modulo si ferma. Un filtro che restituisce un array vuoto spegne il controllo.
 */
function vv_forminator_soci_unici() {
	return apply_filters( 'vv_forminator_soci_unici', array( 274 => 'text-1' ) );
}

/**
 * Indirizzo email della segreteria, per domande e rettifiche.
 */
function vv_email_segreteria() {
	return (string) apply_filters( 'vv_email_segreteria', 'info@wvoce.it' );
}

/**
 * Indirizzo del modulo di iscrizione a socio (Customizer → VivaVoce, "Diventa socio").
 */
function vv_forminator_url_socio() {
	return function_exists( 'vv_mod' ) ? (string) vv_mod( 'vv_url_socio' ) : home_url( '/cosa-puoi-fare/diventa-socio/' );
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
		esc_url( vv_forminator_url_socio() )
	);
}

/**
 * Messaggio per chi si iscrive a socio una seconda volta nello stesso anno sociale.
 *
 * @param string $adesso Data di riferimento, "Y-m-d H:i:s".
 */
function vv_forminator_msg_doppio( $adesso ) {
	$anno  = vv_anno_sociale( $adesso );
	$email = vv_email_segreteria();
	return sprintf(
		'Questo codice fiscale risulta già fra i soci %1$d-%2$d: l\'iscrizione di quest\'anno è già registrata in anagrafica. Per domande o per correggere i dati scrivi a <a href="mailto:%3$s">%3$s</a>.',
		$anno,
		$anno + 1,
		esc_attr( $email )
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
 * Errore accanto al campo quando il codice fiscale non torna; nei moduli riservati ai soci quando
 * manca fra i soci dell'anno, nel modulo dei soci quando c'è già (il modulo non viene inviato).
 *
 * @param array $submit_errors    Errori già trovati da Forminator.
 * @param int   $form_id          Id del modulo.
 * @param array $field_data_array Campi inviati: array( 'name' => id del campo, 'value' => valore ).
 */
function vv_forminator_check_cf( $submit_errors, $form_id, $field_data_array ) {
	$fields = vv_forminator_cf_fields();
	$soci   = vv_forminator_soci_richiesti();
	$unici  = vv_forminator_soci_unici();
	$field  = $fields[ (int) $form_id ] ?? '';
	$socio  = $soci[ (int) $form_id ] ?? '';
	$unico  = $unici[ (int) $form_id ] ?? '';
	if ( ( ! $field && ! $socio && ! $unico ) || ! is_array( $field_data_array ) ) {
		return $submit_errors;
	}
	if ( ! is_array( $submit_errors ) ) {
		$submit_errors = array();
	}
	foreach ( $field_data_array as $item ) {
		$name = is_array( $item ) ? (string) ( $item['name'] ?? '' ) : '';
		if ( '' === $name || ! in_array( $name, array( $field, $socio, $unico ), true ) ) {
			continue;
		}
		$value = trim( (string) ( $item['value'] ?? '' ) );
		if ( '' === $value ) {
			continue;
		}
		if ( $name === $field && ! vv_codice_fiscale_valido( $value ) ) {
			$submit_errors[][ $name ] = 'Controlla il codice fiscale: sono 16 lettere e numeri, come sulla tessera sanitaria.';
			continue;
		}
		$adesso = current_time( 'mysql' );
		if ( $name === $socio && ! vv_socio_iscritto( $value, $adesso ) ) {
			$submit_errors[][ $name ] = vv_forminator_msg_socio( $adesso );
		} elseif ( $name === $unico && vv_socio_iscritto( $value, $adesso ) ) {
			// L'iscrizione in corso entra nel database dopo questo controllo: si trovano solo le precedenti.
			$submit_errors[][ $name ] = vv_forminator_msg_doppio( $adesso );
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
	foreach ( vv_forminator_cf_fields() as $form_id => $field ) {
		$rules[] = sprintf( '#forminator-module-%d input[name="%s"]', (int) $form_id, sanitize_key( $field ) );
	}
	if ( $rules ) {
		wp_add_inline_style( 'vv-plugins', implode( ",\n", $rules ) . " {\n\ttext-transform: uppercase;\n}" );
	}
}
add_action( 'wp_enqueue_scripts', 'vv_forminator_cf_style', 21 );
