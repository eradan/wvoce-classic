<?php
/**
 * Iscrizioni dei moduli Forminator nei fogli Google, attraverso l'app web di Apps Script del foglio.
 *
 * - soci: modulo "Iscrizione Socio" → foglio "Albo Soci", tab dell'anno sociale
 *   ("DomandeAmmissione 2026-2027"), script inc/apps-script/albo-soci.gs. "Assicurazione" resta vuota.
 * - corsi: modulo "Iscrizione ai corsi 2026-2027" → foglio "Iscrizione Corsi", script
 *   inc/apps-script/iscrizione-corsi.gs. Il sito manda una riga per ogni corso scelto, con il testo
 *   del corso; il foglio sceglie il tab secondo il suo tab "configurazioni". Le colonne della
 *   segreteria (dovuto, quota, pagamento) restano vuote.
 *
 * Ogni 15 minuti le iscrizioni nuove partono verso l'app web, che le scrive colonna per colonna
 * secondo le intestazioni. Si avanza solo sulle righe che il foglio conferma; il campo "Rif." evita
 * i doppioni. Foglio, indirizzo, codice, modulo e data di partenza si impostano in Strumenti → Esporta
 * iscrizioni → Configurazione (pagina in inc/sheets-admin.php): senza indirizzo e codice un
 * collegamento resta spento.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collegamenti modulo → foglio. Per ciascuno: prefisso delle proprietà mostrate nel pannello
 * (VV_SOCI_URL…), modulo e data di partenza predefiniti, opzioni in cui si tengono impostazioni,
 * punto raggiunto e stato, funzione che fa le righe di un'iscrizione, colonne del foglio, script
 * Apps Script da incollare. "inizio": opzione del punto di partenza fissato al primo giro (si parte
 * dalle iscrizioni nuove); vuoto per partire dalla data. "requisito": funzione che dice cosa manca
 * per attivarlo.
 */
function vv_sheets_collegamenti() {
	return apply_filters(
		'vv_sheets_collegamenti',
		array(
			'soci'  => array(
				'titolo'       => 'Foglio soci',
				'foglio'       => 'Albo Soci',
				'prefisso'     => 'VV_SOCI',
				'form'         => 274,
				'dal'          => '2026-09-01',
				'impostazioni' => 'vv_soci_impostazioni',
				'ultimo'       => 'vv_soci_ultimo',
				'stato'        => 'vv_soci_stato',
				'inizio'       => '',
				'righe'        => 'vv_soci_righe',
				'colonne'      => 'vv_soci_colonne',
				'requisito'    => '',
				'campi'        => false,
				'script'       => 'albo-soci.gs',
			),
			'corsi' => array(
				'titolo'       => 'Foglio corsi',
				'foglio'       => 'Iscrizione Corsi',
				'prefisso'     => 'VV_CORSI',
				'form'         => 1617,
				'dal'          => '',
				'impostazioni' => 'vv_corsi_impostazioni',
				'ultimo'       => 'vv_corsi_ultimo',
				'stato'        => 'vv_corsi_stato',
				'inizio'       => 'vv_corsi_inizio',
				'righe'        => 'vv_corsi_righe',
				'colonne'      => 'vv_corsi_colonne',
				'requisito'    => 'vv_corsi_requisito',
				'campi'        => true,
				'script'       => 'iscrizione-corsi.gs',
			),
		)
	);
}

/**
 * Impostazioni di un collegamento: indirizzo e codice dell'app web, modulo e data da cui partire
 * (salvati dal pannello), più le voci di vv_sheets_collegamenti().
 *
 * @param string $id Collegamento (soci, corsi).
 */
function vv_sheets_config( $id = 'soci' ) {
	$tutti = vv_sheets_collegamenti();
	$c     = array_merge(
		array(
			'titolo'       => $id,
			'foglio'       => $id,
			'prefisso'     => 'VV_' . strtoupper( $id ),
			'form'         => 0,
			'dal'          => '',
			'impostazioni' => "vv_{$id}_impostazioni",
			'ultimo'       => "vv_{$id}_ultimo",
			'stato'        => "vv_{$id}_stato",
			'inizio'       => '',
			'righe'        => '',
			'colonne'      => '',
			'requisito'    => '',
			'campi'        => false,
			'script'       => '',
		),
		(array) ( $tutti[ $id ] ?? array() )
	);
	$s     = (array) get_option( $c['impostazioni'], array() );

	$c['id']    = $id;
	$c['url']   = (string) ( $s['url'] ?? '' );
	$c['token'] = (string) ( $s['token'] ?? '' );
	// ID del foglio Google ("foglio" è il nome leggibile, per i messaggi).
	$c['foglio_id'] = (string) ( $s['foglio'] ?? '' );
	$c['form']      = empty( $s['form'] ) ? (int) $c['form'] : (int) $s['form'];
	$c['dal']       = array_key_exists( 'dal', $s ) ? (string) $s['dal'] : (string) $c['dal'];
	return $c;
}

/**
 * Salva le impostazioni di un collegamento dal pannello. Codice vuoto: resta quello salvato. Se
 * cambia il modulo si riparte da capo (punto raggiunto e punto di partenza azzerati).
 *
 * @param string $id   Collegamento.
 * @param array  $dati url, token, form, dal.
 * @return array Avvisi per chi salva.
 */
function vv_sheets_salva_impostazioni( $id, $dati ) {
	$c      = vv_sheets_config( $id );
	$prima  = (array) get_option( $c['impostazioni'], array() );
	$avvisi = array();

	$url = esc_url_raw( trim( (string) ( $dati['url'] ?? '' ) ) );
	if ( '' !== $url && ! preg_match( '#^https://script\.google\.com/macros/s/[^/]+/exec$#', $url ) ) {
		$avvisi[] = "L'indirizzo di {$c['prefisso']}_URL ha una forma insolita: di solito è https://script.google.com/macros/s/…/exec.";
	}

	$token = trim( sanitize_text_field( (string) ( $dati['token'] ?? '' ) ) );
	if ( '' === $token ) {
		$token = (string) ( $prima['token'] ?? '' );
	}

	$form = absint( $dati['form'] ?? 0 );

	$foglio_inserito = trim( (string) ( $dati['foglio'] ?? '' ) );
	$foglio          = vv_sheets_id_foglio( $foglio_inserito );
	if ( '' !== $foglio_inserito && '' === $foglio ) {
		$foglio   = (string) ( $prima['foglio'] ?? '' );
		$avvisi[] = "{$c['prefisso']}_FOGLIO non riconosciuto (serve l'ID o l'indirizzo del foglio): resta quello di prima.";
	}

	$dal_inserito = trim( (string) ( $dati['dal'] ?? '' ) );
	$dal          = vv_sanitize_date( $dal_inserito );
	if ( '' !== $dal_inserito && '' === $dal ) {
		$dal      = (string) ( $prima['dal'] ?? $c['dal'] );
		$avvisi[] = "Data di {$c['prefisso']}_DAL non valida (serve aaaa-mm-gg): resta quella di prima.";
	}

	$nuove = array(
		'url'    => $url,
		'token'  => $token,
		'form'   => $form ? $form : (int) $c['form'],
		'dal'    => $dal,
		'foglio' => $foglio,
	);
	if ( $nuove['form'] !== (int) $c['form'] ) {
		delete_option( $c['ultimo'] );
		if ( $c['inizio'] ) {
			delete_option( $c['inizio'] );
		}
		$avvisi[] = "Modulo cambiato: l'invio riparte da capo per il modulo n. {$nuove['form']}.";
	}
	update_option( $c['impostazioni'], $nuove, false );
	return $avvisi;
}

/**
 * ID di un foglio Google da quanto incollato nel pannello: l'ID stesso o l'indirizzo intero
 * (https://docs.google.com/spreadsheets/d/ID/edit…). '' se non riconosciuto.
 *
 * @param string $testo ID o indirizzo.
 */
function vv_sheets_id_foglio( $testo ) {
	$testo = trim( (string) $testo );
	if ( preg_match( '#/spreadsheets/d/([A-Za-z0-9_-]{20,})#', $testo, $m ) ) {
		return $m[1];
	}
	return preg_match( '/^[A-Za-z0-9_-]{20,}$/', $testo ) ? $testo : '';
}

/**
 * Costanti di wp-config.php delle versioni precedenti ancora definite: il tema le ignora.
 */
function vv_sheets_costanti_rimaste() {
	$rimaste = array();
	foreach ( array( 'VV_SHEETS', 'VV_SOCI', 'VV_CORSI' ) as $prefisso ) {
		foreach ( array( 'URL', 'TOKEN', 'FORM', 'DAL' ) as $nome ) {
			if ( defined( "{$prefisso}_{$nome}" ) ) {
				$rimaste[] = "{$prefisso}_{$nome}";
			}
		}
	}
	return $rimaste;
}

/**
 * Opzioni con i nomi della 0.8 (vv_sheets_ultimo…) portate ai nomi nuovi (vv_soci_ultimo…), una
 * volta sola: il punto raggiunto resta quello, nessun reinvio.
 */
function vv_sheets_migra_opzioni() {
	if ( (int) get_option( 'vv_sheets_versione', 0 ) >= 2 ) {
		return;
	}
	$nomi = array(
		'vv_sheets_ultimo'       => 'vv_soci_ultimo',
		'vv_sheets_stato'        => 'vv_soci_stato',
		'vv_sheets_corsi_ultimo' => 'vv_corsi_ultimo',
		'vv_sheets_corsi_stato'  => 'vv_corsi_stato',
		'vv_sheets_corsi_inizio' => 'vv_corsi_inizio',
	);
	foreach ( $nomi as $vecchio => $nuovo ) {
		$valore = get_option( $vecchio, false );
		if ( false === $valore ) {
			continue;
		}
		if ( false === get_option( $nuovo, false ) ) {
			update_option( $nuovo, $valore, false );
		}
		delete_option( $vecchio );
	}
	update_option( 'vv_sheets_versione', 2 );
}
add_action( 'admin_init', 'vv_sheets_migra_opzioni' );

/**
 * Indirizzo e codice ci sono e Forminator è attivo?
 *
 * @param string $id Collegamento.
 */
function vv_sheets_configurato( $id = 'soci' ) {
	$c = vv_sheets_config( $id );
	return '' !== $c['url'] && '' !== $c['token'] && $c['form'] > 0 && $c['righe'] && class_exists( 'Forminator_API' );
}

/**
 * Cosa manca, oltre a indirizzo e codice, perché il collegamento parta ('' se nulla).
 *
 * @param string $id Collegamento.
 */
function vv_sheets_manca( $id = 'soci' ) {
	$c = vv_sheets_config( $id );
	return $c['requisito'] && is_callable( $c['requisito'] ) ? (string) call_user_func( $c['requisito'] ) : '';
}

/**
 * Il collegamento è configurato e pronto a partire?
 *
 * @param string $id Collegamento.
 */
function vv_sheets_ready( $id = 'soci' ) {
	return vv_sheets_configurato( $id ) && '' === vv_sheets_manca( $id );
}

/**
 * Valore di un campo come testo per il foglio. Forminator salva indirizzi, date a tendina,
 * caselle e file come array.
 *
 * @param mixed  $value Valore salvato.
 * @param string $field Id del campo (il tipo è nel nome: consent-1, upload-1…).
 * @param string $part  Parte di un indirizzo (city, state…).
 */
function vv_sheets_valore( $value, $field, $part = '' ) {
	if ( $part ) {
		return is_array( $value ) ? trim( (string) ( $value[ $part ] ?? '' ) ) : '';
	}
	if ( 0 === strpos( $field, 'consent' ) ) {
		return ( '' !== $value && null !== $value && 'false' !== $value && false !== $value ) ? 'TRUE' : '';
	}
	if ( is_array( $value ) ) {
		if ( isset( $value['file'] ) ) {
			$url = $value['file']['file_url'] ?? '';
			return is_array( $url ) ? implode( ' ', array_filter( array_map( 'strval', $url ) ) ) : (string) $url;
		}
		if ( isset( $value['day'], $value['month'], $value['year'] ) ) {
			return sprintf( '%02d/%02d/%04d', (int) $value['day'], (int) $value['month'], (int) $value['year'] );
		}
		return implode( ', ', array_filter( array_map( 'strval', array_filter( $value, 'is_scalar' ) ), 'strlen' ) );
	}
	$value = trim( wp_strip_all_tags( (string) $value ) );
	return 'text-1' === $field ? strtoupper( $value ) : $value;
}

/**
 * Valori delle colonne da un'iscrizione.
 *
 * @param array $meta    Campi salvati da Forminator (id => array( 'value' => … )).
 * @param array $colonne Intestazione => campo, o array( campo, parte ); campo vuoto = colonna vuota.
 */
function vv_sheets_valori( $meta, $colonne ) {
	$riga = array();
	foreach ( $colonne as $intestazione => $campo ) {
		list( $field, $part )  = is_array( $campo ) ? $campo : array( $campo, '' );
		$riga[ $intestazione ] = '' === (string) $field ? '' : vv_sheets_valore( $meta[ $field ]['value'] ?? '', $field, $part );
	}
	return $riga;
}

/**
 * Riferimento di un'iscrizione nel foglio ("F274-12"), contro i doppioni.
 *
 * @param object $entry Iscrizione.
 */
function vv_sheets_rif( $entry ) {
	return sprintf( 'F%d-%d', (int) $entry->form_id, (int) $entry->entry_id );
}

/*
 * Soci.
 */

/**
 * Intestazione della colonna del foglio => campo del modulo (o array( campo, parte ) per gli indirizzi).
 */
function vv_soci_colonne() {
	return apply_filters(
		'vv_soci_colonne',
		array(
			'Tipologia'                          => 'radio-6',
			'Nome'                               => 'name-1',
			'Cognome'                            => 'name-2',
			'CODICE FISCALE'                     => 'text-1',
			'Email'                              => 'email-1',
			'Telefono'                           => 'phone-1',
			'Cellulare (+39)'                    => 'phone-2',
			'Indirizzo'                          => array( 'address-1', 'street_address' ),
			'Citta'                              => array( 'address-1', 'city' ),
			'Provincia'                          => array( 'address-1', 'state' ),
			'CAP'                                => array( 'address-1', 'zip' ),
			'Genere'                             => 'radio-1',
			'Data di Nascita'                    => 'date-1',
			'Address - Comune Di Nascita'        => array( 'address-2', 'city' ),
			'ADDRESS - PROVINCIA DI NASCITA'     => array( 'address-2', 'state' ),
			'Pagamento quota'                    => 'upload-1',
			'Attività di Volontariato'           => 'checkbox-2',
			'GDPR'                               => 'consent-1',
			'Statuto e Regolamento'              => 'consent-2',
			'Autorizzazione Invio Comunicazioni' => 'radio-4',
			'Data compilazione'                  => 'hidden-1',
		)
	);
}

/**
 * Tab dell'anno sociale (da settembre ad agosto) di una data "Y-m-d H:i:s".
 *
 * @param string $data Data dell'iscrizione.
 */
function vv_soci_tab( $data ) {
	$t    = strtotime( $data );
	$anno = (int) gmdate( 'Y', $t );
	if ( (int) gmdate( 'n', $t ) < 9 ) {
		--$anno;
	}
	return sprintf( 'DomandeAmmissione %d-%d', $anno, $anno + 1 );
}

/**
 * Data nel formato dell'export di Forminator usato finora nel foglio: "Ott 5, 2026 @ 10:08 AM".
 *
 * @param string $data Data dell'iscrizione, "Y-m-d H:i:s" (ora del sito).
 */
function vv_soci_data( $data ) {
	static $mesi = array( 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic' );
	$t           = strtotime( $data );
	return $mesi[ (int) gmdate( 'n', $t ) - 1 ] . gmdate( ' j, Y @ g:i A', $t );
}

/**
 * Riga per il foglio dei soci da un'iscrizione di Forminator (Forminator_Form_Entry_Model).
 *
 * @param object $entry Iscrizione.
 */
function vv_soci_riga( $entry ) {
	$meta = is_array( $entry->meta_data ?? null ) ? $entry->meta_data : array();
	return array(
		'_tab'            => vv_soci_tab( $entry->date_created_sql ),
		'Rif.'            => vv_sheets_rif( $entry ),
		'Submission Time' => vv_soci_data( $entry->date_created_sql ),
	) + vv_sheets_valori( $meta, vv_soci_colonne() );
}

/**
 * Righe dei soci: una per iscrizione.
 *
 * @param object $entry Iscrizione.
 */
function vv_soci_righe( $entry ) {
	return array( vv_soci_riga( $entry ) );
}

/*
 * Corsi.
 */

/**
 * Tab per le iscrizioni a un corso che il tab "configurazioni" del foglio non conosce.
 */
function vv_corsi_da_smistare() {
	return 'Da smistare';
}

/**
 * Intestazione della colonna del tab => campo del modulo 1617 (o array( campo, parte )).
 * Id letti dal modulo pubblicato su /iscrizione-corso/ (ottobre 2026).
 */
function vv_corsi_colonne() {
	return apply_filters(
		'vv_corsi_colonne',
		array(
			'Nome'           => 'name-1',
			'Cognome'        => 'name-2',
			'Codice Fiscale' => 'text-1',
			'Email'          => 'email-1',
			'Cellulare'      => 'phone-2',
			'Ricevuta'       => 'upload-1',
			'Regolamento'    => 'consent-1',
			'Socio'          => 'consent-2',
			'Privacy'        => 'consent-3',
			'Note'           => 'textarea-1',
		)
	);
}

/**
 * Id del campo del modulo con il corso scelto (caselle multiple). Se il filtro lo svuota il
 * collegamento dei corsi resta fermo.
 */
function vv_corsi_campo() {
	return (string) apply_filters( 'vv_corsi_campo', 'checkbox-1' );
}

/**
 * Cosa manca per attivare i corsi ('' se nulla).
 */
function vv_corsi_requisito() {
	return '' === vv_corsi_campo() ? 'manca il campo del corso (filtro vv_corsi_campo)' : '';
}

/**
 * Valori delle opzioni del campo del corso, letti dal modulo.
 */
function vv_corsi_opzioni() {
	$campo = vv_corsi_campo();
	foreach ( vv_sheets_campi( vv_sheets_config( 'corsi' )['form'] ) as $f ) {
		if ( '' !== $campo && $f['id'] === $campo ) {
			return array_values( array_filter( array_column( $f['opzioni'], 'valore' ), 'strlen' ) );
		}
	}
	return array();
}

/**
 * Corsi scelti in un'iscrizione. Le caselle arrivano come elenco; se arrivano come testo unito
 * ("a, b") si cercano dentro le opzioni del modulo, perché le opzioni stesse contengono virgole.
 *
 * @param mixed      $valore  Valore salvato del campo del corso.
 * @param array|null $opzioni Opzioni del modulo (null: lette dal modulo).
 */
function vv_corsi_scelti( $valore, $opzioni = null ) {
	if ( ! is_array( $valore ) ) {
		$testo = trim( wp_strip_all_tags( (string) $valore ) );
		$noti  = array();
		foreach ( '' === $testo ? array() : ( null === $opzioni ? vv_corsi_opzioni() : $opzioni ) as $opzione ) {
			$pos = '' === (string) $opzione ? false : strpos( $testo, (string) $opzione );
			if ( false !== $pos ) {
				$noti[ $pos ] = (string) $opzione;
			}
		}
		ksort( $noti );
		$valore = $noti ? array_values( $noti ) : array( $testo );
	}
	$corsi = array();
	foreach ( $valore as $v ) {
		$v = is_scalar( $v ) ? trim( wp_strip_all_tags( (string) $v ) ) : '';
		if ( '' !== $v ) {
			$corsi[] = $v;
		}
	}
	return array_values( array_unique( $corsi ) );
}

/**
 * Righe per il foglio dei corsi da un'iscrizione: una per ogni corso scelto, con lo stesso "Rif."
 * e il testo del corso in "Corso". Il tab lo sceglie il foglio, dal suo tab "configurazioni".
 *
 * @param object $entry Iscrizione.
 */
function vv_corsi_righe( $entry ) {
	$meta  = is_array( $entry->meta_data ?? null ) ? $entry->meta_data : array();
	$campo = vv_corsi_campo();
	$corsi = vv_corsi_scelti( '' === $campo ? '' : ( $meta[ $campo ]['value'] ?? '' ) );
	$base  = array( 'Rif.' => vv_sheets_rif( $entry ) ) + vv_sheets_valori( $meta, vv_corsi_colonne() );
	$righe = array();
	foreach ( $corsi ? $corsi : array( '' ) as $corso ) {
		$righe[] = $base + array( 'Corso' => $corso );
	}
	return $righe;
}

/**
 * Testo di un corso ridotto per il confronto con il tab "configurazioni": minuscole, senza accenti,
 * spazi compressi. Lo script del foglio fa lo stesso (normalizza_).
 *
 * @param string $testo Nome del corso.
 */
function vv_corsi_normalizza( $testo ) {
	$testo = (string) $testo;
	$testo = function_exists( 'remove_accents' )
		? remove_accents( $testo )
		: strtr( $testo, array( 'à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'í' => 'i', 'ò' => 'o', 'ó' => 'o', 'ù' => 'u', 'ú' => 'u', 'À' => 'A', 'È' => 'E', 'É' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U' ) );
	return trim( preg_replace( '/\s+/', ' ', strtolower( $testo ) ) );
}

/**
 * Corrispondenza corso → tab letta dal tab "configurazioni" del foglio (azione "mappa" dello
 * script), tenuta 5 minuti.
 *
 * @param bool $fresca Rilegge dal foglio anche se c'è una copia recente.
 * @return array|WP_Error array( 'mappa' => corso => tab, 'tab' => tab esistenti ).
 */
function vv_corsi_mappa( $fresca = false ) {
	$copia = $fresca ? false : get_transient( 'vv_corsi_mappa' );
	if ( is_array( $copia ) ) {
		return $copia;
	}
	$json = vv_sheets_chiama( 'corsi', array( 'azione' => 'mappa' ) );
	if ( is_wp_error( $json ) ) {
		return $json;
	}
	if ( ! isset( $json['mappa'] ) ) {
		return new WP_Error( 'vv_sheets', 'Lo script del foglio dei corsi non legge il tab "configurazioni": va incollata e pubblicata la versione nuova (Strumenti → Esporta iscrizioni → Script).' );
	}
	$mappa = array(
		'mappa' => array_map( 'strval', (array) $json['mappa'] ),
		'tab'   => array_map( 'strval', (array) ( $json['tab'] ?? array() ) ),
	);
	set_transient( 'vv_corsi_mappa', $mappa, 5 * MINUTE_IN_SECONDS );
	return $mappa;
}

/**
 * Tab in cui il foglio scriverà un corso, secondo la mappa: come lo script, "Da smistare" se il corso
 * manca dal tab "configurazioni" o il tab indicato non esiste.
 *
 * @param string $corso Testo del corso.
 * @param array  $mappa Risultato di vv_corsi_mappa().
 */
function vv_corsi_tab( $corso, $mappa ) {
	$chiave = vv_corsi_normalizza( $corso );
	foreach ( (array) ( $mappa['mappa'] ?? array() ) as $nome => $tab ) {
		if ( '' !== $chiave && vv_corsi_normalizza( $nome ) === $chiave ) {
			return in_array( $tab, (array) ( $mappa['tab'] ?? array() ), true ) ? $tab : vv_corsi_da_smistare();
		}
	}
	return vv_corsi_da_smistare();
}

/**
 * Campi di un modulo (id, etichetta, tipo, opzioni con etichetta e valore), per controllare la
 * corrispondenza delle colonne. Solo la struttura del modulo, nessun dato delle iscrizioni.
 *
 * @param int $form Id del modulo.
 */
function vv_sheets_campi( $form ) {
	if ( ! class_exists( 'Forminator_API' ) || ! method_exists( 'Forminator_API', 'get_form_fields' ) ) {
		return array();
	}
	$campi = Forminator_API::get_form_fields( $form );
	if ( ! is_array( $campi ) ) {
		return array();
	}
	$out = array();
	foreach ( $campi as $f ) {
		if ( is_object( $f ) && method_exists( $f, 'to_formatted_array' ) ) {
			$d = (array) $f->to_formatted_array();
		} elseif ( is_object( $f ) ) {
			$d = array_merge( is_array( $f->raw ?? null ) ? $f->raw : array(), array( 'element_id' => $f->slug ?? '' ) );
		} else {
			$d = (array) $f;
		}
		$opzioni = array();
		foreach ( (array) ( $d['options'] ?? array() ) as $o ) {
			if ( is_array( $o ) ) {
				$label     = (string) ( $o['label'] ?? '' );
				$value     = (string) ( $o['value'] ?? '' );
				$opzioni[] = array(
					'etichetta' => $label,
					'valore'    => '' === $value ? $label : $value,
				);
			}
		}
		$out[] = array(
			'id'        => (string) ( $d['element_id'] ?? $d['slug'] ?? $d['id'] ?? '' ),
			'etichetta' => wp_strip_all_tags( (string) ( $d['field_label'] ?? $d['label'] ?? '' ) ),
			'tipo'      => (string) ( $d['type'] ?? '' ),
			'opzioni'   => $opzioni,
		);
	}
	return $out;
}

/*
 * Invio.
 */

/**
 * Numero più alto fra le iscrizioni di un modulo (spam e bozze compresi).
 *
 * @param int $form Id del modulo.
 */
function vv_sheets_ultimo_id( $form ) {
	$entries = Forminator_API::get_entries( $form );
	$max     = 0;
	foreach ( is_array( $entries ) ? $entries : array() as $e ) {
		$max = is_object( $e ) ? max( $max, (int) $e->entry_id ) : $max;
	}
	return $max;
}

/**
 * Ultima iscrizione confermata dal foglio. Per un collegamento che parte dalle iscrizioni nuove, la
 * prima volta si fissa come punto di partenza l'iscrizione più recente già presente.
 *
 * @param string $id Collegamento.
 */
function vv_sheets_ultimo( $id = 'soci' ) {
	$c      = vv_sheets_config( $id );
	$ultimo = get_option( $c['ultimo'], false );
	if ( false !== $ultimo ) {
		return (int) $ultimo;
	}
	if ( ! $c['inizio'] ) {
		return 0;
	}
	$inizio = get_option( $c['inizio'], false );
	if ( false === $inizio ) {
		$inizio = vv_sheets_ultimo_id( $c['form'] );
		update_option( $c['inizio'], $inizio, false );
	}
	return (int) $inizio;
}

/**
 * Iscrizioni da mandare: dopo l'ultima confermata, dalla data di partenza (se c'è) in poi, niente
 * spam né bozze salvate, dalla più vecchia.
 *
 * @param string $id Collegamento.
 */
function vv_sheets_da_inviare( $id = 'soci' ) {
	$c       = vv_sheets_config( $id );
	$ultimo  = vv_sheets_ultimo( $id );
	$entries = Forminator_API::get_entries( $c['form'] );
	if ( ! is_array( $entries ) ) {
		return array();
	}
	$entries = array_filter(
		$entries,
		static function ( $e ) use ( $ultimo, $c ) {
			return is_object( $e )
				&& (int) $e->entry_id > $ultimo
				&& empty( $e->is_spam )
				&& empty( $e->draft_id )
				&& ( '' === $c['dal'] || substr( (string) $e->date_created_sql, 0, 10 ) >= $c['dal'] );
		}
	);
	usort( $entries, static fn( $a, $b ) => (int) $a->entry_id <=> (int) $b->entry_id );
	return array_values( $entries );
}

/**
 * Righe per il foglio di un blocco di iscrizioni.
 *
 * @param array  $entries Iscrizioni.
 * @param string $id      Collegamento.
 */
function vv_sheets_righe( $entries, $id = 'soci' ) {
	$c     = vv_sheets_config( $id );
	$righe = array();
	foreach ( $entries as $entry ) {
		foreach ( (array) call_user_func( $c['righe'], $entry ) as $riga ) {
			$righe[] = $riga;
		}
	}
	return $righe;
}

/**
 * Chiamata all'app web di un collegamento. Apps Script risponde con un reindirizzamento a
 * googleusercontent.com, da leggere con una GET (seguito come POST darebbe errore).
 *
 * @param string $id   Collegamento.
 * @param array  $dati Dati da mandare insieme al codice (righe, azione…).
 * @return array|WP_Error Risposta del foglio.
 */
function vv_sheets_chiama( $id, $dati ) {
	$c    = vv_sheets_config( $id );
	$dove = "{$c['prefisso']}_URL in Strumenti → Esporta iscrizioni → Configurazione";
	if ( '' === $c['url'] || '' === $c['token'] ) {
		return new WP_Error( 'vv_sheets', "Mancano {$c['prefisso']}_URL o {$c['prefisso']}_TOKEN in Strumenti → Esporta iscrizioni → Configurazione." );
	}
	$args = array(
		'timeout'     => 30,
		'redirection' => 0,
		'headers'     => array( 'Content-Type' => 'application/json' ),
		'body'        => wp_json_encode( array( 'token' => $c['token'] ) + ( '' !== $c['foglio_id'] ? array( 'foglio' => $c['foglio_id'] ) : array() ) + $dati ),
	);
	$res  = wp_remote_post( $c['url'], $args );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
		$res  = wp_remote_get( wp_remote_retrieve_header( $res, 'location' ), array( 'timeout' => 30 ) );
		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	}
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$json = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( in_array( $code, array( 401, 403 ), true ) ) {
		return new WP_Error( 'vv_sheets', "Google chiede di accedere (HTTP {$code}): nel deployment dell'app web \"Chi ha accesso\" deve essere \"Chiunque\" e {$dove} deve finire con /exec." );
	}
	if ( 404 === $code ) {
		return new WP_Error( 'vv_sheets', "App web non trovata (HTTP 404): controlla {$dove}." );
	}
	if ( 200 !== $code || ! is_array( $json ) || empty( $json['ok'] ) ) {
		$errore = is_array( $json ) && ! empty( $json['errore'] ) ? $json['errore'] : 'risposta inattesa (HTTP ' . $code . ')';
		return new WP_Error( 'vv_sheets', 'Il foglio ha risposto: ' . $errore );
	}
	return $json;
}

/**
 * Manda un blocco di righe all'app web.
 *
 * @param array  $righe Righe per il foglio.
 * @param string $id    Collegamento.
 * @return array|WP_Error Rif. confermati dal foglio.
 */
function vv_sheets_post( $righe, $id = 'soci' ) {
	$json = vv_sheets_chiama( $id, array( 'righe' => $righe ) );
	return is_wp_error( $json ) ? $json : array_map( 'strval', (array) ( $json['rif'] ?? array() ) );
}

/**
 * Verifica di indirizzo, codice e foglio: un invio senza righe, che il foglio conferma senza
 * scrivere nulla.
 *
 * @param string $id Collegamento.
 * @return string|WP_Error Nome del foglio raggiunto ('' se lo script è di una versione precedente).
 */
function vv_sheets_verifica( $id ) {
	$json = vv_sheets_chiama( $id, array( 'righe' => array() ) );
	return is_wp_error( $json ) ? $json : (string) ( $json['foglio'] ?? '' );
}

/**
 * Invia le iscrizioni nuove di un collegamento, a blocchi di 20. Avanza l'ultima confermata blocco
 * per blocco (un'iscrizione con più righe conta quando il foglio restituisce il suo Rif.); al primo
 * errore si ferma e riprova al giro dopo. Dopo 24 ore di errori avvisa l'amministratore.
 *
 * @param string $id Collegamento.
 * @return array Esito: inviate, errore.
 */
function vv_sheets_sync( $id = 'soci' ) {
	if ( ! vv_sheets_ready( $id ) ) {
		return array( 'inviate' => 0, 'errore' => 'Collegamento non configurato.' );
	}
	$c       = vv_sheets_config( $id );
	$stato   = (array) get_option( $c['stato'], array() );
	$inviate = 0;
	$errore  = '';
	foreach ( array_chunk( vv_sheets_da_inviare( $id ), 20 ) as $blocco ) {
		$righe = vv_sheets_righe( $blocco, $id );
		$esito = $righe ? vv_sheets_post( $righe, $id ) : array();
		if ( is_wp_error( $esito ) ) {
			$errore = $esito->get_error_message();
			break;
		}
		if ( array_diff( wp_list_pluck( $righe, 'Rif.' ), $esito ) ) {
			$errore = 'Il foglio non ha confermato tutte le righe del blocco.';
			break;
		}
		update_option( $c['ultimo'], max( array_map( static fn( $e ) => (int) $e->entry_id, $blocco ) ), false );
		$inviate += count( $blocco );
	}

	$adesso = time();
	if ( $errore ) {
		$stato['errore']     = $errore;
		$stato['errore_dal'] = $stato['errore_dal'] ?? $adesso;
		if ( empty( $stato['avvisato'] ) && $adesso - $stato['errore_dal'] > DAY_IN_SECONDS ) {
			wp_mail(
				get_option( 'admin_email' ),
				"VivaVoce: le iscrizioni non arrivano al foglio Google \"{$c['foglio']}\"",
				"Da più di un giorno l'invio al foglio \"{$c['foglio']}\" non riesce.\n\nUltimo errore: {$errore}\n\nStato e prova: " . admin_url( 'tools.php?page=vv-iscrizioni&tab=' . $id )
			);
			$stato['avvisato'] = true;
		}
	} else {
		unset( $stato['errore'], $stato['errore_dal'], $stato['avvisato'] );
	}
	$stato['controllo'] = $adesso;
	if ( $inviate ) {
		$stato['ultimo_invio'] = $adesso;
	}
	update_option( $c['stato'], $stato, false );
	return array( 'inviate' => $inviate, 'errore' => $errore );
}

/**
 * Un giro per ogni collegamento pronto: l'errore di uno non ferma gli altri.
 */
function vv_sheets_sync_tutti() {
	vv_sheets_migra_opzioni();
	foreach ( array_keys( vv_sheets_collegamenti() ) as $id ) {
		if ( vv_sheets_configurato( $id ) ) {
			vv_sheets_ultimo( $id ); // Fissa il punto di partenza anche prima che sia pronto.
		}
		if ( vv_sheets_ready( $id ) ) {
			vv_sheets_sync( $id );
		}
	}
}
add_action( 'vv_sheets_sync', 'vv_sheets_sync_tutti' );

/**
 * C'è almeno un collegamento configurato?
 */
function vv_sheets_qualcuno() {
	foreach ( array_keys( vv_sheets_collegamenti() ) as $id ) {
		if ( vv_sheets_configurato( $id ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Ogni 15 minuti, finché un collegamento è configurato.
 *
 * @param array $schedules Frequenze di wp-cron.
 */
function vv_sheets_schedules( $schedules ) {
	$schedules['vv_15_minuti'] = array(
		'interval' => 15 * MINUTE_IN_SECONDS,
		'display'  => 'Ogni 15 minuti',
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'vv_sheets_schedules' );

function vv_sheets_schedule() {
	$next = wp_next_scheduled( 'vv_sheets_sync' );
	$on   = vv_sheets_qualcuno();
	if ( $on && ! $next ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'vv_15_minuti', 'vv_sheets_sync' );
	} elseif ( ! $on && $next ) {
		wp_clear_scheduled_hook( 'vv_sheets_sync' );
	}
}
add_action( 'init', 'vv_sheets_schedule' );
add_action( 'switch_theme', static fn() => wp_clear_scheduled_hook( 'vv_sheets_sync' ) );

/**
 * Prova a secco: quante iscrizioni partirebbero, quante righe in ciascun tab, quante hanno ogni
 * colonna piena. Nessun valore.
 *
 * @param string        $id     Collegamento.
 * @param callable|null $gruppo Nome del gruppo di una riga (predefinito: il suo "_tab").
 */
function vv_sheets_prova( $id = 'soci', $gruppo = null ) {
	$c       = vv_sheets_config( $id );
	$tab     = array();
	$piene   = array_fill_keys( array_keys( (array) call_user_func( $c['colonne'] ) ), 0 );
	$entries = vv_sheets_da_inviare( $id );
	$righe   = vv_sheets_righe( $entries, $id );
	foreach ( $righe as $riga ) {
		$nome         = $gruppo ? (string) call_user_func( $gruppo, $riga ) : (string) ( $riga['_tab'] ?? '' );
		$tab[ $nome ] = ( $tab[ $nome ] ?? 0 ) + 1;
		foreach ( $piene as $col => $n ) {
			if ( '' !== ( $riga[ $col ] ?? '' ) ) {
				++$piene[ $col ];
			}
		}
	}
	return array( 'totale' => count( $entries ), 'righe' => count( $righe ), 'tab' => $tab, 'piene' => $piene );
}

/**
 * Testo di uno script Apps Script da incollare nel foglio ('' se manca).
 *
 * @param string $id Collegamento.
 */
function vv_sheets_script( $id ) {
	$c    = vv_sheets_config( $id );
	$file = __DIR__ . '/apps-script/' . basename( (string) $c['script'] );
	return $c['script'] && is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}
