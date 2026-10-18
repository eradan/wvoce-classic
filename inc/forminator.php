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
 * Pagina dei corsi aperta con un codice valido: modulo dei corsi ricompilato (vv_ricompila()).
 */
function vv_corso_pagina() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Il codice stesso fa da gettone.
	$campi = isset( $_GET['vv'] ) ? vv_corso_dati( sanitize_key( wp_unslash( $_GET['vv'] ) ) ) : null;
	if ( $campi ) {
		vv_ricompila( (int) vv_sheets_config( 'corsi' )['form'], $campi, VV_CORSO_CASELLE, 'Abbiamo ripreso i tuoi dati dall\'iscrizione a socio: scegli il corso e carica la ricevuta del pagamento.' );
	}
}
add_action( 'template_redirect', 'vv_corso_pagina' );

/**
 * Modulo da ricompilare nella pagina (assets/js/vv.js): pagina fuori dalla cache, perché dentro ci sono
 * dati personali, e dati in fondo alla pagina.
 *
 * @param int      $form    Id del modulo.
 * @param array    $campi   Nome del campo nella pagina => valore (i radio per valore dell'opzione).
 * @param string[] $caselle Caselle da spuntare.
 * @param string   $avviso  Testo sopra il modulo.
 */
function vv_ricompila( $form, $campi, $caselle, $avviso ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	add_action(
		'wp_footer',
		static function () use ( $form, $campi, $caselle, $avviso ) {
			$dati = array(
				'form'    => $form,
				'campi'   => $campi,
				'caselle' => $caselle,
				'avviso'  => $avviso,
			);
			printf( '<script id="vv-ricompila" type="application/json">%s</script>' . "\n", wp_json_encode( $dati, JSON_HEX_TAG | JSON_HEX_AMP ) );
		}
	);
}

/*
 * Recupera i dati dell'ultima iscrizione. Nel modulo dei soci, sotto il codice fiscale, un link
 * (assets/js/vv.js) chiede al sito di cercare l'ultima iscrizione con quel codice negli ultimi anni
 * sociali. A video il messaggio è sempre lo stesso, per non dire a nessuno chi è socio: la risposta
 * arriva per email all'indirizzo di quell'iscrizione. Se la persona è già iscritta quest'anno l'email
 * lo dice; altrimenti porta un link che riapre il modulo con i dati anagrafici, di residenza e di
 * contatto. Ricevuta, accettazioni e scelte si rifanno. Il link vale 24 ore; nel transient c'è solo il
 * riferimento all'iscrizione, i dati si rileggono all'apertura.
 */

/*
 * Anni sociali precedenti in cui cercare l'ultima iscrizione.
 */
const VV_SOCIO_ANNI = 3;

/*
 * Durata del link, in secondi: 24 ore.
 */
const VV_SOCIO_DURATA = 86400;

/*
 * Limiti: richieste per IP in un'ora, minuti fra due email allo stesso codice fiscale.
 */
const VV_SOCIO_RICHIESTE = 5;
const VV_SOCIO_ATTESA    = 15;

/**
 * Campi recuperati: id del campo, o array( campo, parte ) per gli indirizzi. Fuori: ricevuta, caselle,
 * accettazioni, autorizzazione alle comunicazioni, corso sì/no, data di compilazione.
 */
function vv_socio_campi_recuperati() {
	return array(
		'radio-6',
		'name-1',
		'name-2',
		VV_CAMPO_CF,
		'text-4',
		'text-5',
		'email-1',
		'phone-1',
		'phone-2',
		array( 'address-1', 'street_address' ),
		array( 'address-1', 'city' ),
		array( 'address-1', 'state' ),
		array( 'address-1', 'zip' ),
		'radio-1',
		'date-1',
		array( 'address-2', 'city' ),
		array( 'address-2', 'state' ),
	);
}

/**
 * Valori da ricompilare da un'iscrizione: nome del campo nella pagina ("address-1-city") => valore.
 *
 * @param object $entry Iscrizione di Forminator.
 */
function vv_socio_dati_recuperati( $entry ) {
	$meta = is_array( $entry->meta_data ?? null ) ? $entry->meta_data : array();
	$dati = array();
	foreach ( vv_socio_campi_recuperati() as $campo ) {
		list( $field, $part ) = is_array( $campo ) ? $campo : array( $campo, '' );
		$valore               = vv_sheets_valore( $meta[ $field ]['value'] ?? '', $field, $part );
		if ( '' !== $valore ) {
			$dati[ $part ? "{$field}-{$part}" : $field ] = $valore;
		}
	}
	return $dati;
}

/**
 * Ultima iscrizione a socio con questo codice fiscale negli ultimi VV_SOCIO_ANNI anni sociali.
 *
 * @param string $cf     Codice fiscale.
 * @param string $adesso Data di riferimento, "Y-m-d H:i:s".
 * @return object|null
 */
function vv_socio_ultima_iscrizione( $cf, $adesso ) {
	$ultima = null;
	foreach ( vv_soci_iscrizioni( $cf, ( vv_anno_sociale( $adesso ) - VV_SOCIO_ANNI ) . '-09-01' ) as $e ) {
		if ( ! $ultima || (string) $e->date_created_sql > (string) $ultima->date_created_sql ) {
			$ultima = $e;
		}
	}
	return $ultima;
}

/**
 * Indirizzo del visitatore. Il sito sta dietro nginx: se la richiesta arriva dal proxy (indirizzo
 * locale o privato) vale X-Real-IP, che imposta il proxy.
 */
function vv_ip_visitatore() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( $ip && ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) && isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		$reale = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		if ( filter_var( $reale, FILTER_VALIDATE_IP ) ) {
			return $reale;
		}
	}
	return $ip;
}

/**
 * Richiesta di recuperare i dati: controlli, limiti, email. L'esito serve ai test e a scegliere il
 * messaggio; a video "formato" e "troppe" hanno il loro, tutti gli altri lo stesso.
 *
 * @param string $cf     Codice fiscale scritto nel modulo.
 * @param string $ip     Indirizzo del visitatore.
 * @param string $adesso Data di riferimento, "Y-m-d H:i:s".
 * @return string formato|troppe|nessuno|attesa|iscritto|link
 */
function vv_socio_richiesta( $cf, $ip, $adesso ) {
	if ( ! vv_codice_fiscale_valido( $cf ) ) {
		return 'formato';
	}
	$chiave_ip = 'vv_socio_ip_' . md5( (string) $ip );
	$richieste = (int) get_transient( $chiave_ip );
	if ( $richieste >= VV_SOCIO_RICHIESTE ) {
		return 'troppe';
	}
	set_transient( $chiave_ip, $richieste + 1, HOUR_IN_SECONDS );

	$cf     = vv_cf_normalizza( $cf );
	$ultima = vv_socio_ultima_iscrizione( $cf, $adesso );
	$email  = $ultima ? vv_sheets_valore( $ultima->meta_data['email-1']['value'] ?? '', 'email-1' ) : '';
	if ( ! $ultima || ! is_email( $email ) ) {
		return 'nessuno';
	}
	$chiave_cf = 'vv_socio_cf_' . md5( $cf );
	if ( get_transient( $chiave_cf ) ) {
		return 'attesa';
	}
	set_transient( $chiave_cf, 1, VV_SOCIO_ATTESA * MINUTE_IN_SECONDS );

	$anno = vv_anno_sociale( $adesso );
	if ( vv_anno_sociale( $ultima->date_created_sql ) === $anno ) {
		wp_mail(
			$email,
			sprintf( 'VivaVoce: la tua iscrizione %d-%d è già registrata', $anno, $anno + 1 ),
			sprintf(
				"Ciao,\n\nqualcuno, probabilmente tu, ha chiesto di recuperare i dati della tua iscrizione a VivaVoce dal modulo sul sito.\n\nLa tua iscrizione per l'anno sociale %d-%d è già registrata: non serve compilare di nuovo il modulo. Per correggere i dati scrivi a %s.\n\nSe non sei stato tu, ignora questa email.\n\nVivaVoce",
				$anno,
				$anno + 1,
				vv_mod( 'vv_email' )
			)
		);
		return 'iscritto';
	}

	$codice = bin2hex( random_bytes( 16 ) );
	set_transient(
		'vv_socio_' . $codice,
		array(
			'form'  => (int) $ultima->form_id,
			'entry' => (int) $ultima->entry_id,
			'cf'    => $cf,
		),
		VV_SOCIO_DURATA
	);
	wp_mail(
		$email,
		'VivaVoce: recupera i dati della tua iscrizione',
		sprintf(
			"Ciao,\n\nhai chiesto di recuperare i dati della tua ultima iscrizione a VivaVoce. Apri questo link entro 24 ore: troverai il modulo di iscrizione già compilato con i dati anagrafici, di residenza e di contatto.\n\n%s\n\nControlla che i dati siano ancora giusti, carica la ricevuta del pagamento e rinnova le accettazioni.\n\nSe non sei stato tu, ignora questa email: senza il link nessuno vede i tuoi dati.\n\nVivaVoce",
			add_query_arg( 'vv-socio', $codice, vv_mod( 'vv_url_socio' ) )
		)
	);
	return 'link';
}

/**
 * Messaggio a video per un esito.
 *
 * @param string $esito Esito di vv_socio_richiesta().
 */
function vv_socio_messaggio( $esito ) {
	if ( 'formato' === $esito ) {
		return 'Controlla il codice fiscale: sono 16 lettere e numeri, come sulla tessera sanitaria.';
	}
	if ( 'troppe' === $esito ) {
		return 'Troppe richieste da questa connessione: riprova fra un\'ora, oppure compila il modulo per intero.';
	}
	return 'Se il codice fiscale è nel nostro archivio, ti abbiamo scritto all\'indirizzo email dell\'ultima iscrizione (guarda anche nello spam). Il link vale 24 ore.';
}

/**
 * Endpoint per il pulsante: POST /wp-json/vv/v1/recupera-socio, con "cf".
 */
function vv_socio_rest() {
	register_rest_route(
		'vv/v1',
		'/recupera-socio',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'args'                => array(
				'cf' => array(
					'type'     => 'string',
					'required' => true,
				),
			),
			'callback'            => static function ( $request ) {
				$esito = vv_socio_richiesta( (string) $request['cf'], vv_ip_visitatore(), current_time( 'mysql' ) );
				return array(
					'ok'        => ! in_array( $esito, array( 'formato', 'troppe' ), true ),
					'messaggio' => vv_socio_messaggio( $esito ),
				);
			},
		)
	);
}
add_action( 'rest_api_init', 'vv_socio_rest' );

/**
 * Dati da ricompilare per un codice del link, o null se il codice non è valido, è scaduto o
 * l'iscrizione non c'è più (cancellata, spostata nello spam, codice fiscale cambiato).
 *
 * @param mixed $codice Codice dall'indirizzo.
 * @return array|null
 */
function vv_socio_dati( $codice ) {
	if ( ! is_string( $codice ) || ! preg_match( '/^[0-9a-f]{32}$/', $codice ) || ! class_exists( 'Forminator_API' ) ) {
		return null;
	}
	$rif   = get_transient( 'vv_socio_' . $codice );
	$entry = is_array( $rif ) ? Forminator_API::get_entry( $rif['form'], $rif['entry'] ) : null;
	if ( ! is_object( $entry ) || ! empty( $entry->is_spam ) || vv_cf_normalizza( $entry->meta_data[ VV_CAMPO_CF ]['value'] ?? '' ) !== $rif['cf'] ) {
		return null;
	}
	return vv_socio_dati_recuperati( $entry );
}

/**
 * Pagina dei soci aperta dal link dell'email: modulo ricompilato. Per gli amministratori, anche con
 * ?vv-socio-prova=<id dell'iscrizione> (il numero dopo il trattino nella colonna "Rif." del foglio): stessa
 * ricompilazione senza email, per provarla o per aiutare un socio.
 */
function vv_socio_pagina() {
	$form   = (int) vv_sheets_config( 'soci' )['form'];
	$avviso = 'Abbiamo recuperato i dati della tua ultima iscrizione: controlla che siano ancora giusti, carica la ricevuta del pagamento e rinnova le accettazioni.';
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Il codice fa da gettone; la prova è solo per gli amministratori e non cambia niente.
	$campi = isset( $_GET['vv-socio'] ) ? vv_socio_dati( sanitize_key( wp_unslash( $_GET['vv-socio'] ) ) ) : null;
	$prova = isset( $_GET['vv-socio-prova'] ) && current_user_can( 'manage_options' ) ? absint( $_GET['vv-socio-prova'] ) : 0;
	// phpcs:enable
	if ( ! $campi && $prova && class_exists( 'Forminator_API' ) ) {
		$entry = Forminator_API::get_entry( $form, $prova );
		if ( is_object( $entry ) && (int) ( $entry->form_id ?? 0 ) === $form ) {
			$campi  = vv_socio_dati_recuperati( $entry );
			$avviso = sprintf( 'Anteprima per l\'amministrazione: modulo compilato dall\'iscrizione n. %d, senza email. Non inviarlo se non serve davvero.', $prova );
		}
	}
	if ( $campi ) {
		vv_ricompila( $form, $campi, array(), $avviso );
	}
}
add_action( 'template_redirect', 'vv_socio_pagina' );

/**
 * Nelle pagine con un modulo Forminator: id dei moduli dei soci e dei corsi e indirizzo del pulsante,
 * per assets/js/vv.js.
 */
function vv_moduli_script() {
	$post = get_post();
	if ( ! is_singular() || ! $post || false === strpos( (string) $post->post_content, '[forminator_form' ) ) {
		return;
	}
	$dati = array(
		'soci'     => (int) vv_sheets_config( 'soci' )['form'],
		'corsi'    => (int) vv_sheets_config( 'corsi' )['form'],
		'cf'       => VV_CAMPO_CF,
		'recupera' => esc_url_raw( rest_url( 'vv/v1/recupera-socio' ) ),
	);
	wp_add_inline_script( 'vv-main', 'window.vvModuli = ' . wp_json_encode( $dati ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'vv_moduli_script', 21 );
