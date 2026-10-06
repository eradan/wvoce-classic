<?php
/**
 * Iscrizioni dei moduli Forminator nei fogli Google, attraverso l'app web di Apps Script del foglio.
 *
 * - soci: modulo "Iscrizione Socio" → foglio "Albo Soci", tab dell'anno sociale
 *   ("DomandeAmmissione 2026-2027"), script docs/apps-script-albo-soci.gs. "Assicurazione" resta vuota.
 * - corsi: modulo "Iscrizione ai corsi 2026-2027" → foglio "Iscrizione Corsi", un tab per corso
 *   (una riga per ogni corso scelto), script docs/apps-script-iscrizione-corsi.gs. Le colonne della
 *   segreteria (dovuto, quota, pagamento) restano vuote.
 *
 * Ogni 15 minuti le iscrizioni nuove partono verso l'app web, che le scrive colonna per colonna
 * secondo le intestazioni. Si avanza solo sulle righe che il foglio conferma; il campo "Rif." evita
 * i doppioni. Senza indirizzo e codice dell'app web in wp-config.php un collegamento resta spento.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collegamenti modulo → foglio. Per ciascuno: prefisso delle costanti di wp-config.php (_URL,
 * _TOKEN, facoltative _FORM e _DAL), modulo e data di partenza predefiniti, opzioni in cui si tiene
 * il punto raggiunto e lo stato, funzione che fa le righe di un'iscrizione, colonne del foglio.
 * "inizio": opzione del punto di partenza fissato al primo giro (si parte dalle iscrizioni nuove);
 * vuoto per partire dalla data. "requisito": funzione che dice cosa manca per attivarlo.
 */
function vv_sheets_collegamenti() {
	return apply_filters(
		'vv_sheets_collegamenti',
		array(
			'soci'  => array(
				'titolo'    => 'Foglio soci',
				'foglio'    => 'Albo Soci',
				'costanti'  => 'VV_SHEETS',
				'form'      => 274,
				'dal'       => '2026-09-01',
				'ultimo'    => 'vv_sheets_ultimo',
				'stato'     => 'vv_sheets_stato',
				'inizio'    => '',
				'righe'     => 'vv_sheets_righe_soci',
				'colonne'   => 'vv_sheets_colonne',
				'requisito' => '',
				'campi'     => false,
			),
			'corsi' => array(
				'titolo'    => 'Foglio corsi',
				'foglio'    => 'Iscrizione Corsi',
				'costanti'  => 'VV_CORSI',
				'form'      => 1617,
				'dal'       => '',
				'ultimo'    => 'vv_sheets_corsi_ultimo',
				'stato'     => 'vv_sheets_corsi_stato',
				'inizio'    => 'vv_sheets_corsi_inizio',
				'righe'     => 'vv_corsi_righe',
				'colonne'   => 'vv_corsi_colonne',
				'requisito' => 'vv_corsi_requisito',
				'campi'     => true,
			),
		)
	);
}

/**
 * Impostazioni di un collegamento: indirizzo e codice dell'app web (in wp-config.php), modulo e data
 * da cui partire, più le voci di vv_sheets_collegamenti().
 *
 * @param string $id Collegamento (soci, corsi).
 */
function vv_sheets_config( $id = 'soci' ) {
	$tutti = vv_sheets_collegamenti();
	$c     = array_merge(
		array(
			'titolo'    => $id,
			'foglio'    => $id,
			'costanti'  => '',
			'form'      => 0,
			'dal'       => '',
			'ultimo'    => "vv_sheets_{$id}_ultimo",
			'stato'     => "vv_sheets_{$id}_stato",
			'inizio'    => '',
			'righe'     => '',
			'colonne'   => '',
			'requisito' => '',
			'campi'     => false,
		),
		(array) ( $tutti[ $id ] ?? array() )
	);
	$k = $c['costanti'];
	$v = static fn( $nome, $altrimenti ) => ( $k && defined( "{$k}_{$nome}" ) ) ? constant( "{$k}_{$nome}" ) : $altrimenti;

	$c['id']    = $id;
	$c['url']   = (string) $v( 'URL', '' );
	$c['token'] = (string) $v( 'TOKEN', '' );
	$c['form']  = (int) $v( 'FORM', $c['form'] );
	$c['dal']   = (string) $v( 'DAL', $c['dal'] );
	return $c;
}

/**
 * Indirizzo e codice ci sono e Forminator è attivo?
 *
 * @param string $id Collegamento.
 */
function vv_sheets_configurato( $id = 'soci' ) {
	$c = vv_sheets_config( $id );
	return '' !== $c['url'] && '' !== $c['token'] && $c['righe'] && class_exists( 'Forminator_API' );
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
 * Intestazione della colonna del foglio => campo del modulo (o array( campo, parte ) per gli indirizzi).
 */
function vv_sheets_colonne() {
	return apply_filters(
		'vv_sheets_colonne',
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
function vv_sheets_tab( $data ) {
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
function vv_sheets_data( $data ) {
	static $mesi = array( 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic' );
	$t = strtotime( $data );
	return $mesi[ (int) gmdate( 'n', $t ) - 1 ] . gmdate( ' j, Y @ g:i A', $t );
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
		list( $field, $part ) = is_array( $campo ) ? $campo : array( $campo, '' );
		$riga[ $intestazione ] = '' === (string) $field ? '' : vv_sheets_valore( $meta[ $field ]['value'] ?? '', $field, $part );
	}
	return $riga;
}

/**
 * Riga per il foglio dei soci da un'iscrizione di Forminator (Forminator_Form_Entry_Model).
 *
 * @param object $entry Iscrizione.
 */
function vv_sheets_riga( $entry ) {
	$meta = is_array( $entry->meta_data ?? null ) ? $entry->meta_data : array();
	return array(
		'_tab'            => vv_sheets_tab( $entry->date_created_sql ),
		'Rif.'            => sprintf( 'F%d-%d', (int) $entry->form_id, (int) $entry->entry_id ),
		'Submission Time' => vv_sheets_data( $entry->date_created_sql ),
	) + vv_sheets_valori( $meta, vv_sheets_colonne() );
}

/**
 * Righe dei soci: una per iscrizione.
 *
 * @param object $entry Iscrizione.
 */
function vv_sheets_righe_soci( $entry ) {
	return array( vv_sheets_riga( $entry ) );
}

/*
 * Corsi.
 */

/**
 * Tab per le iscrizioni a un corso che non ha il suo tab: lo script lo crea con una colonna "Corso".
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
 * Tab dei corsi nel foglio. Un valore del modulo trova il suo tab anche se scritto in altro modo
 * ("Lettura ad alta voce" → lettura-ad-alta-voce-2026): vedi vv_corsi_tab().
 */
function vv_corsi_tab_nomi() {
	return apply_filters(
		'vv_corsi_tab_nomi',
		array( 'lettura-ad-alta-voce-2026', 'tecnica-attoriale-2026', 'messa-in-scena-2027', 'gioco-del-teatro-2026' )
	);
}

/**
 * Valore del modulo => nome del tab, per i casi che il confronto dei nomi non risolve. Le opzioni
 * del modulo 1617 cominciano con "Adulti: Laboratorio di…" e portano date e orari: vanno indicate
 * per intero. Un'opzione riscritta nel modulo, senza la voce qui, finisce in "Da smistare".
 */
function vv_corsi_tabs() {
	return apply_filters(
		'vv_corsi_tabs',
		array(
			'Adulti: Laboratorio di lettura ad alta voce corale, ottobre-dicembre 2026 (martedì 18.30-20.30)' => 'lettura-ad-alta-voce-2026',
			'Adulti: Laboratorio di tecniche attoriali, ottobre-dicembre 2026 (giovedì 18.30-20.30)' => 'tecnica-attoriale-2026',
			'Adulti: Laboratorio di messa in scena, gennaio-giugno 2027 (giovedì 18.30-20.30)' => 'messa-in-scena-2027',
			'Bambini (elementari): Il gioco del teatro, ottobre-dicembre 2026' => 'gioco-del-teatro-2026',
		)
	);
}

/**
 * Corsi scelti in un'iscrizione. Le caselle arrivano come elenco; se arrivano come testo unito
 * ("a, b") si cercano dentro le opzioni note, perché le opzioni stesse contengono virgole.
 *
 * @param mixed $valore Valore salvato del campo del corso.
 */
function vv_corsi_scelti( $valore ) {
	if ( ! is_array( $valore ) ) {
		$testo = trim( wp_strip_all_tags( (string) $valore ) );
		$noti  = array();
		foreach ( array_keys( vv_corsi_tabs() ) as $opzione ) {
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
 * Cosa manca per attivare i corsi ('' se nulla).
 */
function vv_corsi_requisito() {
	return '' === vv_corsi_campo() ? 'manca il campo del corso (filtro vv_corsi_campo)' : '';
}

/**
 * Testo ridotto a minuscole, lettere senza accenti e trattini: "Gioco del Teatro (3-6 anni)" →
 * "gioco-del-teatro-3-6-anni".
 *
 * @param string $testo Nome di un corso o di un tab.
 */
function vv_corsi_chiave( $testo ) {
	$testo = (string) $testo;
	$testo = function_exists( 'remove_accents' )
		? remove_accents( $testo )
		: strtr( $testo, array( 'à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'í' => 'i', 'ò' => 'o', 'ó' => 'o', 'ù' => 'u', 'ú' => 'u', 'À' => 'A', 'È' => 'E', 'É' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U' ) );
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $testo ) ), '-' );
}

/**
 * Tab di un corso. Prima la tabella vv_corsi_tabs(), poi il confronto con i nomi dei tab: uguali,
 * uguali senza l'anno finale, o il corso che comincia con il nome del tab ("lettura-ad-alta-voce-
 * ottobre" → lettura-ad-alta-voce-2026). Un anno nel corso diverso da quello del tab non combacia.
 * Senza corrispondenza: "Da smistare".
 *
 * @param string $corso Valore scelto nel modulo.
 */
function vv_corsi_tab( $corso ) {
	$mappa = vv_corsi_tabs();
	if ( isset( $mappa[ $corso ] ) ) {
		return (string) $mappa[ $corso ];
	}
	$chiave = vv_corsi_chiave( $corso );
	if ( '' === $chiave ) {
		return vv_corsi_da_smistare();
	}
	foreach ( $mappa as $valore => $tab ) {
		if ( vv_corsi_chiave( $valore ) === $chiave ) {
			return (string) $tab;
		}
	}
	$anno     = preg_match( '/(?:^|-)(\d{4})(?:-|$)/', $chiave, $m ) ? $m[1] : '';
	$migliore = vv_corsi_da_smistare();
	$lungo    = 0;
	foreach ( array_unique( array_merge( vv_corsi_tab_nomi(), array_values( $mappa ) ) ) as $tab ) {
		$k        = vv_corsi_chiave( $tab );
		$anno_tab = preg_match( '/-(\d{4})$/', $k, $m ) ? $m[1] : '';
		$base     = $anno_tab ? substr( $k, 0, -5 ) : $k;
		if ( $chiave === $k || $chiave === $base ) {
			return (string) $tab;
		}
		if ( '' === $base || 0 !== strpos( $chiave, $base . '-' ) || strlen( $base ) <= $lungo ) {
			continue;
		}
		if ( $anno && $anno_tab && $anno !== $anno_tab ) {
			continue;
		}
		$migliore = (string) $tab;
		$lungo    = strlen( $base );
	}
	return $migliore;
}

/**
 * Righe per il foglio dei corsi da un'iscrizione: una per ogni corso scelto, con lo stesso "Rif.".
 * Ogni riga porta anche "Corso", che lo script scrive solo dove c'è la colonna ("Da smistare").
 *
 * @param object $entry Iscrizione.
 */
function vv_corsi_righe( $entry ) {
	$meta  = is_array( $entry->meta_data ?? null ) ? $entry->meta_data : array();
	$campo = vv_corsi_campo();
	$corsi = vv_corsi_scelti( '' === $campo ? '' : ( $meta[ $campo ]['value'] ?? '' ) );
	$base  = array( 'Rif.' => sprintf( 'F%d-%d', (int) $entry->form_id, (int) $entry->entry_id ) ) + vv_sheets_valori( $meta, vv_corsi_colonne() );
	$righe = array();
	foreach ( $corsi ? $corsi : array( '' ) as $corso ) {
		$tab = vv_corsi_tab( $corso );
		// Nello stesso tab una sola riga per iscrizione; in "Da smistare" una per corso.
		$chiave = vv_corsi_da_smistare() === $tab ? $tab . "\n" . $corso : $tab;
		if ( ! isset( $righe[ $chiave ] ) ) {
			$righe[ $chiave ] = array( '_tab' => $tab ) + $base + array( 'Corso' => $corso );
		}
	}
	return array_values( $righe );
}

/**
 * Campi di un modulo (id, etichetta, tipo, opzioni), per completare la corrispondenza delle colonne.
 * Solo la struttura del modulo, nessun dato delle iscrizioni.
 *
 * @param int $form Id del modulo.
 */
function vv_sheets_campi( $form ) {
	if ( ! class_exists( 'Forminator_API' ) ) {
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
				$value     = (string) ( $o['value'] ?? $label );
				$opzioni[] = $label === $value || '' === $value ? $label : "{$label} = {$value}";
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
 * Manda un blocco di righe all'app web. Apps Script risponde con un reindirizzamento a
 * googleusercontent.com, da leggere con una GET (seguito come POST darebbe errore).
 *
 * @param array  $righe Righe per il foglio.
 * @param string $id    Collegamento.
 * @return array|WP_Error Rif. confermati dal foglio.
 */
function vv_sheets_post( $righe, $id = 'soci' ) {
	$c    = vv_sheets_config( $id );
	$k    = $c['costanti'] . '_URL';
	$args = array(
		'timeout'     => 30,
		'redirection' => 0,
		'headers'     => array( 'Content-Type' => 'application/json' ),
		'body'        => wp_json_encode( array( 'token' => $c['token'], 'righe' => $righe ) ),
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
		return new WP_Error( 'vv_sheets', "Google chiede di accedere (HTTP {$code}): nel deployment dell'app web \"Chi ha accesso\" deve essere \"Chiunque\" e {$k} deve finire con /exec." );
	}
	if ( 404 === $code ) {
		return new WP_Error( 'vv_sheets', "App web non trovata (HTTP 404): controlla {$k}." );
	}
	if ( 200 !== $code || ! is_array( $json ) || empty( $json['ok'] ) ) {
		$errore = is_array( $json ) && ! empty( $json['errore'] ) ? $json['errore'] : 'risposta inattesa (HTTP ' . $code . ')';
		return new WP_Error( 'vv_sheets', 'Il foglio ha risposto: ' . $errore );
	}
	return array_map( 'strval', (array) ( $json['rif'] ?? array() ) );
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
				"Da più di un giorno l'invio al foglio \"{$c['foglio']}\" non riesce.\n\nUltimo errore: {$errore}\n\nStato e prova: " . admin_url( 'tools.php?page=vv-sheets' )
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
 * @param string $id Collegamento.
 */
function vv_sheets_prova( $id = 'soci' ) {
	$c       = vv_sheets_config( $id );
	$tab     = array();
	$piene   = array_fill_keys( array_keys( (array) call_user_func( $c['colonne'] ) ), 0 );
	$entries = vv_sheets_da_inviare( $id );
	$righe   = vv_sheets_righe( $entries, $id );
	foreach ( $righe as $riga ) {
		$tab[ $riga['_tab'] ] = ( $tab[ $riga['_tab'] ] ?? 0 ) + 1;
		foreach ( $piene as $col => $n ) {
			if ( '' !== ( $riga[ $col ] ?? '' ) ) {
				++$piene[ $col ];
			}
		}
	}
	return array( 'totale' => count( $entries ), 'righe' => count( $righe ), 'tab' => $tab, 'piene' => $piene );
}

/*
 * Strumenti → Fogli Google.
 */

function vv_sheets_menu() {
	add_management_page( 'Fogli Google', 'Fogli Google', 'manage_options', 'vv-sheets', 'vv_sheets_page' );
}
add_action( 'admin_menu', 'vv_sheets_menu' );

/**
 * Una sezione per collegamento: stato, prova a secco, invio subito, ripartenza.
 */
function vv_sheets_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$azione = isset( $_POST['vv_sheets'] ) ? sanitize_key( wp_unslash( $_POST['vv_sheets'] ) ) : '';
	$quale  = isset( $_POST['vv_sheets_id'] ) ? sanitize_key( wp_unslash( $_POST['vv_sheets_id'] ) ) : '';
	$esito  = array();
	if ( $azione && check_admin_referer( 'vv_sheets' ) && vv_sheets_ready( $quale ) ) {
		if ( 'invia' === $azione ) {
			$esito[ $quale ] = vv_sheets_sync( $quale );
		} elseif ( 'riparti' === $azione ) {
			delete_option( vv_sheets_config( $quale )['ultimo'] );
		}
	}
	?>
	<div class="wrap">
		<h1>Fogli Google</h1>
		<p>Le iscrizioni dei moduli Forminator arrivano da sole nei fogli Google, ogni 15 minuti. Istruzioni nel README del tema.</p>
		<?php
		foreach ( array_keys( vv_sheets_collegamenti() ) as $id ) {
			vv_sheets_sezione( $id, $esito[ $id ] ?? null );
		}
		?>
	</div>
	<?php
}

/**
 * Sezione di un collegamento nella pagina Strumenti → Fogli Google.
 *
 * @param string     $id    Collegamento.
 * @param array|null $esito Esito di "Invia adesso", se appena premuto.
 */
function vv_sheets_sezione( $id, $esito ) {
	$c       = vv_sheets_config( $id );
	$k       = $c['costanti'];
	$quando  = static fn( $t ) => $t ? wp_date( 'j F Y, H:i', (int) $t ) : '—';
	$attivo  = vv_sheets_configurato( $id );
	$manca   = vv_sheets_manca( $id );
	$pronto  = $attivo && '' === $manca;
	$ultimo  = $attivo ? vv_sheets_ultimo( $id ) : 0;
	$inizio  = $c['inizio'] ? (int) get_option( $c['inizio'], 0 ) : 0;
	$prova   = $pronto ? vv_sheets_prova( $id ) : null;
	$stato   = (array) get_option( $c['stato'], array() );
	$colonne = $c['colonne'] ? (array) call_user_func( $c['colonne'] ) : array();
	$partenza = $c['inizio']
		? sprintf( 'dopo l\'iscrizione n. %d (la più recente quando il collegamento è stato attivato)', $inizio )
		: ( $c['dal'] ? 'dal ' . wp_date( 'j F Y', strtotime( $c['dal'] ) ) : 'dalla prima' );
	?>
	<h2 style="margin-top:2em"><?php echo esc_html( $c['titolo'] ); ?></h2>
	<p>Modulo Forminator n. <?php echo (int) $c['form']; ?> → foglio "<?php echo esc_html( $c['foglio'] ); ?>"<?php echo $attivo ? esc_html( ', iscrizioni ' . $partenza ) : ''; ?>.</p>
	<?php if ( ! $attivo ) : ?>
		<div class="notice notice-warning inline"><p>Spento: servono <code><?php echo esc_html( $k ); ?>_URL</code> e <code><?php echo esc_html( $k ); ?>_TOKEN</code> in <code>wp-config.php</code> e Forminator attivo.</p></div>
	<?php elseif ( $manca ) : ?>
		<div class="notice notice-warning inline"><p>Fermo: <?php echo esc_html( $manca ); ?>. Le iscrizioni arrivate nel frattempo partiranno appena il collegamento è pronto.</p></div>
	<?php endif; ?>

	<?php if ( $esito ) : ?>
		<div class="notice <?php echo $esito['errore'] ? 'notice-error' : 'notice-success'; ?> inline"><p>
			<?php echo esc_html( sprintf( 'Inviate %d iscrizioni.', $esito['inviate'] ) . ( $esito['errore'] ? ' Errore: ' . $esito['errore'] : '' ) ); ?>
		</p></div>
	<?php endif; ?>

	<?php if ( $attivo ) : ?>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
				<tr><th>Ultima iscrizione confermata dal foglio</th><td>n. <?php echo (int) $ultimo; ?></td></tr>
				<tr><th>Ultimo invio riuscito</th><td><?php echo esc_html( $quando( $stato['ultimo_invio'] ?? 0 ) ); ?></td></tr>
				<tr><th>Ultimo controllo</th><td><?php echo esc_html( $quando( $stato['controllo'] ?? 0 ) ); ?></td></tr>
				<tr><th>Prossimo controllo</th><td><?php echo esc_html( $quando( wp_next_scheduled( 'vv_sheets_sync' ) ) ); ?></td></tr>
				<tr><th>Errore</th><td><?php echo esc_html( ( $stato['errore'] ?? '' ) ? $stato['errore'] . ' (dal ' . $quando( $stato['errore_dal'] ) . ')' : 'nessuno' ); ?></td></tr>
			</tbody>
		</table>
	<?php endif; ?>

	<?php if ( $prova ) : ?>
		<h3>Prova a secco</h3>
		<p><?php echo esc_html( sprintf( '%d iscrizioni da inviare, %d righe.', $prova['totale'], $prova['righe'] ) ); ?>
			<?php foreach ( $prova['tab'] as $nome => $n ) : ?>
				<br><?php echo esc_html( "{$nome}: {$n}" ); ?>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>

	<?php if ( $c['campi'] || ( $prova && $prova['righe'] ) ) : ?>
		<table class="widefat striped" style="max-width:720px">
			<thead><tr><th>Colonna del foglio</th><th>Campo del modulo</th><?php echo $prova ? '<th>Righe con la colonna piena</th>' : ''; ?></tr></thead>
			<tbody>
				<?php foreach ( $colonne as $col => $campo ) : ?>
					<tr>
						<td><?php echo esc_html( $col ); ?></td>
						<td><?php echo $campo ? '<code>' . esc_html( implode( ' → ', (array) $campo ) ) . '</code>' : '<em>da indicare</em>'; ?></td>
						<?php if ( $prova ) : ?>
							<td><?php echo (int) ( $prova['piene'][ $col ] ?? 0 ); ?> su <?php echo (int) $prova['righe']; ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php if ( $c['campi'] && class_exists( 'Forminator_API' ) ) : ?>
		<details style="margin-top:1em;max-width:720px">
			<summary>Campi del modulo n. <?php echo (int) $c['form']; ?></summary>
			<p>Per completare la corrispondenza delle colonne (filtri <code>vv_corsi_colonne</code>, <code>vv_corsi_campo</code> e <code>vv_corsi_tabs</code>). Solo la struttura del modulo, nessun dato delle iscrizioni.</p>
			<table class="widefat striped">
				<thead><tr><th>Id</th><th>Etichetta</th><th>Tipo</th><th>Opzioni → tab</th></tr></thead>
				<tbody>
					<?php foreach ( vv_sheets_campi( $c['form'] ) as $f ) : ?>
						<tr>
							<td><code><?php echo esc_html( $f['id'] ); ?></code></td>
							<td><?php echo esc_html( $f['etichetta'] ); ?></td>
							<td><?php echo esc_html( $f['tipo'] ); ?></td>
							<td>
								<?php foreach ( $f['opzioni'] as $o ) : ?>
									<?php $valore = false !== strpos( $o, ' = ' ) ? substr( $o, strpos( $o, ' = ' ) + 3 ) : $o; ?>
									<?php echo esc_html( $o . ( 'corsi' === $id ? ' → ' . vv_corsi_tab( $valore ) : '' ) ); ?><br>
								<?php endforeach; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
	<?php endif; ?>

	<?php if ( $pronto ) : ?>
		<form method="post" style="margin-top:1.5em">
			<?php wp_nonce_field( 'vv_sheets' ); ?>
			<input type="hidden" name="vv_sheets_id" value="<?php echo esc_attr( $id ); ?>">
			<button class="button button-primary" name="vv_sheets" value="invia">Invia adesso</button>
			<button class="button" name="vv_sheets" value="riparti" onclick="return confirm('Ricominciare <?php echo esc_js( $partenza ); ?>? Le righe già nel foglio non vengono duplicate.');">Ricomincia dal punto di partenza</button>
		</form>
	<?php endif; ?>
	<?php
}
