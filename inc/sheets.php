<?php
/**
 * Domande di ammissione dei soci (modulo Forminator "Iscrizione Socio") nel foglio Google "Albo Soci".
 *
 * Ogni 15 minuti le iscrizioni nuove partono verso l'app web di Apps Script del foglio
 * (docs/apps-script-albo-soci.gs), che le scrive nel tab dell'anno sociale ("DomandeAmmissione
 * 2026-2027") colonna per colonna secondo le intestazioni, lasciando vuota "Assicurazione".
 * Si avanza solo sulle righe che il foglio conferma; il campo "Rif." evita i doppioni.
 * Senza VV_SHEETS_URL e VV_SHEETS_TOKEN in wp-config.php non succede nulla.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Impostazioni: indirizzo e codice dell'app web (in wp-config.php), modulo e data da cui partire.
 */
function vv_sheets_config() {
	return array(
		'url'   => defined( 'VV_SHEETS_URL' ) ? (string) VV_SHEETS_URL : '',
		'token' => defined( 'VV_SHEETS_TOKEN' ) ? (string) VV_SHEETS_TOKEN : '',
		'form'  => defined( 'VV_SHEETS_FORM' ) ? (int) VV_SHEETS_FORM : 274,
		'dal'   => defined( 'VV_SHEETS_DAL' ) ? (string) VV_SHEETS_DAL : '2026-09-01',
	);
}

/**
 * Il collegamento è configurato e Forminator è attivo?
 */
function vv_sheets_ready() {
	$c = vv_sheets_config();
	return '' !== $c['url'] && '' !== $c['token'] && class_exists( 'Forminator_API' );
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
 * Riga per il foglio da un'iscrizione di Forminator (Forminator_Form_Entry_Model).
 *
 * @param object $entry Iscrizione.
 */
function vv_sheets_riga( $entry ) {
	$meta = is_array( $entry->meta_data ?? null ) ? $entry->meta_data : array();
	$riga = array(
		'_tab'            => vv_sheets_tab( $entry->date_created_sql ),
		'Rif.'            => sprintf( 'F%d-%d', (int) $entry->form_id, (int) $entry->entry_id ),
		'Submission Time' => vv_sheets_data( $entry->date_created_sql ),
	);
	foreach ( vv_sheets_colonne() as $intestazione => $campo ) {
		list( $field, $part ) = is_array( $campo ) ? $campo : array( $campo, '' );
		$riga[ $intestazione ] = vv_sheets_valore( $meta[ $field ]['value'] ?? '', $field, $part );
	}
	return $riga;
}

/**
 * Iscrizioni da mandare: dalla data di partenza in poi, dopo l'ultima confermata, niente spam
 * né bozze salvate, dalla più vecchia.
 */
function vv_sheets_da_inviare() {
	$c       = vv_sheets_config();
	$ultimo  = (int) get_option( 'vv_sheets_ultimo', 0 );
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
				&& substr( (string) $e->date_created_sql, 0, 10 ) >= $c['dal'];
		}
	);
	usort( $entries, static fn( $a, $b ) => (int) $a->entry_id <=> (int) $b->entry_id );
	return array_values( $entries );
}

/**
 * Manda un blocco di righe all'app web. Apps Script risponde con un reindirizzamento a
 * googleusercontent.com, da leggere con una GET (seguito come POST darebbe errore).
 *
 * @param array $righe Righe per il foglio.
 * @return array|WP_Error Rif. confermati dal foglio.
 */
function vv_sheets_post( $righe ) {
	$c    = vv_sheets_config();
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
		return new WP_Error( 'vv_sheets', "Google chiede di accedere (HTTP {$code}): nel deployment dell'app web \"Chi ha accesso\" deve essere \"Chiunque\" e VV_SHEETS_URL deve finire con /exec." );
	}
	if ( 404 === $code ) {
		return new WP_Error( 'vv_sheets', 'App web non trovata (HTTP 404): controlla VV_SHEETS_URL.' );
	}
	if ( 200 !== $code || ! is_array( $json ) || empty( $json['ok'] ) ) {
		$errore = is_array( $json ) && ! empty( $json['errore'] ) ? $json['errore'] : 'risposta inattesa (HTTP ' . $code . ')';
		return new WP_Error( 'vv_sheets', 'Il foglio ha risposto: ' . $errore );
	}
	return array_map( 'strval', (array) ( $json['rif'] ?? array() ) );
}

/**
 * Invia le iscrizioni nuove, a blocchi di 20. Avanza l'ultima confermata blocco per blocco; al
 * primo errore si ferma e riprova al giro dopo. Dopo 24 ore di errori avvisa l'amministratore.
 *
 * @return array Esito: inviate, errore.
 */
function vv_sheets_sync() {
	if ( ! vv_sheets_ready() ) {
		return array( 'inviate' => 0, 'errore' => 'Collegamento non configurato.' );
	}
	$stato   = (array) get_option( 'vv_sheets_stato', array() );
	$inviate = 0;
	$errore  = '';
	foreach ( array_chunk( vv_sheets_da_inviare(), 20 ) as $blocco ) {
		$righe = array_map( 'vv_sheets_riga', $blocco );
		$esito = vv_sheets_post( $righe );
		if ( is_wp_error( $esito ) ) {
			$errore = $esito->get_error_message();
			break;
		}
		$attesi = wp_list_pluck( $righe, 'Rif.' );
		if ( array_diff( $attesi, $esito ) ) {
			$errore = 'Il foglio non ha confermato tutte le righe del blocco.';
			break;
		}
		update_option( 'vv_sheets_ultimo', max( array_map( static fn( $e ) => (int) $e->entry_id, $blocco ) ), false );
		$inviate += count( $blocco );
	}

	$adesso = time();
	if ( $errore ) {
		$stato['errore']     = $errore;
		$stato['errore_dal'] = $stato['errore_dal'] ?? $adesso;
		if ( empty( $stato['avvisato'] ) && $adesso - $stato['errore_dal'] > DAY_IN_SECONDS ) {
			wp_mail(
				get_option( 'admin_email' ),
				'VivaVoce: le domande dei soci non arrivano al foglio Google',
				"Da più di un giorno l'invio al foglio \"Albo Soci\" non riesce.\n\nUltimo errore: {$errore}\n\nStato e prova: " . admin_url( 'tools.php?page=vv-sheets' )
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
	update_option( 'vv_sheets_stato', $stato, false );
	return array( 'inviate' => $inviate, 'errore' => $errore );
}
add_action( 'vv_sheets_sync', 'vv_sheets_sync' );

/**
 * Ogni 15 minuti, finché il collegamento è configurato.
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
	if ( vv_sheets_ready() && ! $next ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'vv_15_minuti', 'vv_sheets_sync' );
	} elseif ( ! vv_sheets_ready() && $next ) {
		wp_clear_scheduled_hook( 'vv_sheets_sync' );
	}
}
add_action( 'init', 'vv_sheets_schedule' );
add_action( 'switch_theme', static fn() => wp_clear_scheduled_hook( 'vv_sheets_sync' ) );

/**
 * Prova a secco: quante iscrizioni partirebbero, verso quali tab, e quante hanno ogni colonna piena.
 * Nessun valore.
 */
function vv_sheets_prova() {
	$tab     = array();
	$piene   = array_fill_keys( array_keys( vv_sheets_colonne() ), 0 );
	$entries = vv_sheets_da_inviare();
	foreach ( $entries as $entry ) {
		$riga = vv_sheets_riga( $entry );
		$tab[ $riga['_tab'] ] = ( $tab[ $riga['_tab'] ] ?? 0 ) + 1;
		foreach ( $piene as $col => $n ) {
			if ( '' !== $riga[ $col ] ) {
				++$piene[ $col ];
			}
		}
	}
	return array( 'totale' => count( $entries ), 'tab' => $tab, 'piene' => $piene );
}

/**
 * Strumenti → Foglio soci: stato, prova a secco, invio subito, ripartenza dalla data iniziale.
 */
function vv_sheets_menu() {
	add_management_page( 'Foglio soci', 'Foglio soci', 'manage_options', 'vv-sheets', 'vv_sheets_page' );
}
add_action( 'admin_menu', 'vv_sheets_menu' );

function vv_sheets_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$c      = vv_sheets_config();
	$azione = isset( $_POST['vv_sheets'] ) ? sanitize_key( wp_unslash( $_POST['vv_sheets'] ) ) : '';
	$esito  = null;
	if ( $azione && check_admin_referer( 'vv_sheets' ) && vv_sheets_ready() ) {
		if ( 'invia' === $azione ) {
			$esito = vv_sheets_sync();
		} elseif ( 'riparti' === $azione ) {
			delete_option( 'vv_sheets_ultimo' );
		}
	}
	$prova = vv_sheets_ready() ? vv_sheets_prova() : null;
	$stato = (array) get_option( 'vv_sheets_stato', array() );
	$quando = static fn( $t ) => $t ? wp_date( 'j F Y, H:i', (int) $t ) : '—';
	?>
	<div class="wrap">
		<h1>Foglio soci</h1>
		<p>Le domande del modulo Forminator n. <?php echo (int) $c['form']; ?> dal <?php echo esc_html( wp_date( 'j F Y', strtotime( $c['dal'] ) ) ); ?> in poi vanno nel foglio Google "Albo Soci", nel tab dell'anno sociale. La colonna "Assicurazione" resta da compilare a mano.</p>
		<?php if ( ! vv_sheets_ready() ) : ?>
			<div class="notice notice-warning inline"><p>Non configurato: servono <code>VV_SHEETS_URL</code> e <code>VV_SHEETS_TOKEN</code> in <code>wp-config.php</code> (istruzioni nel README del tema) e Forminator attivo.</p></div>
		<?php else : ?>
			<?php if ( $esito ) : ?>
				<div class="notice <?php echo $esito['errore'] ? 'notice-error' : 'notice-success'; ?> inline"><p>
					<?php echo esc_html( sprintf( 'Inviate %d iscrizioni.', $esito['inviate'] ) . ( $esito['errore'] ? ' Errore: ' . $esito['errore'] : '' ) ); ?>
				</p></div>
			<?php endif; ?>
			<table class="widefat striped" style="max-width:720px">
				<tbody>
					<tr><th>Ultima iscrizione confermata dal foglio</th><td>n. <?php echo (int) get_option( 'vv_sheets_ultimo', 0 ); ?></td></tr>
					<tr><th>Ultimo invio riuscito</th><td><?php echo esc_html( $quando( $stato['ultimo_invio'] ?? 0 ) ); ?></td></tr>
					<tr><th>Ultimo controllo</th><td><?php echo esc_html( $quando( $stato['controllo'] ?? 0 ) ); ?></td></tr>
					<tr><th>Prossimo controllo</th><td><?php echo esc_html( $quando( wp_next_scheduled( 'vv_sheets_sync' ) ) ); ?></td></tr>
					<tr><th>Errore</th><td><?php echo esc_html( ( $stato['errore'] ?? '' ) ? $stato['errore'] . ' (dal ' . $quando( $stato['errore_dal'] ) . ')' : 'nessuno' ); ?></td></tr>
				</tbody>
			</table>

			<h2>Prova a secco</h2>
			<p><?php echo esc_html( sprintf( '%d iscrizioni da inviare.', $prova['totale'] ) ); ?>
				<?php foreach ( $prova['tab'] as $nome => $n ) : ?>
					<br><?php echo esc_html( "{$nome}: {$n}" ); ?>
				<?php endforeach; ?>
			</p>
			<?php if ( $prova['totale'] ) : ?>
				<table class="widefat striped" style="max-width:720px">
					<thead><tr><th>Colonna del foglio</th><th>Iscrizioni con la colonna piena</th></tr></thead>
					<tbody>
						<?php foreach ( $prova['piene'] as $col => $n ) : ?>
							<tr><td><?php echo esc_html( $col ); ?></td><td><?php echo (int) $n; ?> su <?php echo (int) $prova['totale']; ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<form method="post" style="margin-top:1.5em">
				<?php wp_nonce_field( 'vv_sheets' ); ?>
				<button class="button button-primary" name="vv_sheets" value="invia">Invia adesso</button>
				<button class="button" name="vv_sheets" value="riparti" onclick="return confirm('Ricominciare dal <?php echo esc_js( $c['dal'] ); ?>? Le righe già nel foglio non vengono duplicate.');">Ricomincia dalla data iniziale</button>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
