<?php
/**
 * Iscrizioni a socio e ai corsi: le gestisce il plugin wvoce-iscrizioni (Strumenti → Iscrizioni). Fino
 * alla 0.13 lo faceva il tema; questo file passa una volta sola al plugin la configurazione di VivaVoce
 * (moduli, campi, colonne e tab dei fogli, testi, pagine) e lo stato dell'invio ai fogli (punto
 * raggiunto, iscrizioni sospese, partenza dalle nuove), così niente riparte da capo e i fogli non
 * cambiano. Senza il plugin il tema funziona lo stesso: i moduli restano quelli di Forminator.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Configurazione di VivaVoce per il plugin, con le impostazioni e lo stato salvati dal tema fino
 * alla 0.13 (opzioni vv_soci_* e vv_corsi_*).
 */
function vv_iscrizioni_configurazione() {
	$vecchie = static fn( $id ) => (array) get_option( "vv_{$id}_impostazioni", array() );
	$soci    = $vecchie( 'soci' );
	$corsi   = $vecchie( 'corsi' );
	$stato   = array();
	foreach ( array( 'soci', 'corsi' ) as $id ) {
		foreach ( array( 'ultimo', 'sospese', 'inizio', 'stato' ) as $voce ) {
			$valore = get_option( "vv_{$id}_{$voce}", null );
			if ( null !== $valore ) {
				$stato[ $id ][ $voce ] = $valore;
			}
		}
	}
	$collegamento = static fn( $s, $form, $dal ) => array(
		'url'    => (string) ( $s['url'] ?? '' ),
		'token'  => (string) ( $s['token'] ?? '' ),
		'foglio' => (string) ( $s['foglio'] ?? '' ),
		'form'   => empty( $s['form'] ) ? $form : (int) $s['form'],
		'dal'    => array_key_exists( 'dal', $s ) ? (string) $s['dal'] : $dal,
	);

	return array(
		'generali' => array(
			'mese_inizio'  => 9,
			'email'        => (string) vv_mod( 'vv_email' ),
			'pagina_soci'  => (string) vv_mod( 'vv_url_socio' ),
			'pagina_corsi' => (string) vv_mod( 'vv_url_corso' ),
			'proxy'        => true,
			'col_rif'      => 'Rif.',
			'autocomplete' => array(
				'name-1'          => 'given-name',
				'name-2'          => 'family-name',
				'email-1'         => 'email',
				'phone-1'         => 'tel',
				'phone-2'         => 'tel',
				'address-2-city'  => 'off',
				'address-2-state' => 'off',
			),
		),
		'soci'     => $collegamento( $soci, 274, '2026-09-01' ) + array(
			'dalle_nuove'         => false,
			'campo_cf'            => 'text-1',
			'controlla'           => true,
			'campo_email'         => 'email-1',
			'col_data'            => 'Submission Time',
			'tab_prefisso'        => 'DomandeAmmissione ',
			'unico'               => true,
			'msg_doppio'          => 'Questo codice fiscale risulta già fra i soci {anno}: l\'iscrizione di quest\'anno è già registrata in anagrafica. Per domande o per correggere i dati scrivi a <a href="mailto:{email}">{email}</a>.',
			'colonne'             => array(
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
			),
			'recupera'            => true,
			'recupera_campi'      => array( 'radio-6', 'name-1', 'name-2', 'text-1', 'text-4', 'text-5', 'email-1', 'phone-1', 'phone-2', 'address-1.street_address', 'address-1.city', 'address-1.state', 'address-1.zip', 'radio-1', 'date-1', 'address-2.city', 'address-2.state' ),
			'recupera_anni'       => 3,
			'recupera_ore'        => 24,
			'recupera_richieste'  => 5,
			'recupera_attesa'     => 5,
			'recupera_invito'     => 'Già socio? <a>Recupera i tuoi dati</a>',
			'recupera_avviso'     => 'Abbiamo recuperato i dati della tua ultima iscrizione: controlla che siano ancora giusti, carica la ricevuta del pagamento e rinnova le accettazioni.',
			'recupera_link_testo' => "Ciao,\n\nhai chiesto di recuperare i dati della tua ultima iscrizione a {associazione}. Apri il link qui sotto entro {ore} ore: troverai il modulo di iscrizione già compilato con i dati anagrafici, di residenza e di contatto.\n\n{link}\n\nControlla che i dati siano ancora giusti, carica la ricevuta del pagamento e rinnova le accettazioni.\n\nSe non sei stato tu, ignora questa email: senza il link nessuno vede i tuoi dati.\n\n{associazione}",
		),
		'corsi'    => $collegamento( $corsi, 1617, '' ) + array(
			'dalle_nuove'        => true,
			'campo_cf'           => 'text-1',
			'controlla'          => true,
			'campo_corsi'        => 'checkbox-1',
			'col_corso'          => 'Corso',
			'tab_configurazioni' => 'configurazioni',
			'tab_riserva'        => 'Da smistare',
			'richiede_socio'     => true,
			'colonne'            => array(
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
			),
			'passaggio'          => true,
			'passaggio_campi'    => array( 'name-1', 'name-2', 'text-1', 'email-1', 'phone-2' ),
			'passaggio_caselle'  => array( 'consent-1', 'consent-2', 'consent-3' ),
			'passaggio_avviso'   => 'Abbiamo ripreso i tuoi dati dall\'iscrizione a socio: scegli il corso e carica la ricevuta del pagamento.',
		),
		'stato'    => $stato,
	);
}

/**
 * Passaggio al plugin, una volta: con il plugin attivo e senza una sua configurazione, gli passa quella
 * di VivaVoce e spegne il vecchio giro del tema verso i fogli (il plugin ha il suo).
 */
function vv_iscrizioni_passaggio() {
	if ( ! function_exists( 'wvi_importa' ) || get_option( 'vv_iscrizioni_passate' ) ) {
		return;
	}
	if ( ! get_option( 'wvi_soci_impostazioni' ) && ! get_option( 'wvi_corsi_impostazioni' ) ) {
		wvi_importa( vv_iscrizioni_configurazione() );
	}
	wp_clear_scheduled_hook( 'vv_sheets_sync' );
	update_option( 'vv_iscrizioni_passate', 1 );
}
add_action( 'init', 'vv_iscrizioni_passaggio' );
