<?php
/**
 * Strumenti → Esporta iscrizioni: configurazione dei collegamenti, invio delle iscrizioni dei soci e
 * dei corsi, script Apps Script da incollare nei fogli. Il motore è in inc/sheets.php.
 *
 * @package VivaVoce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tab della pagina.
 */
function vv_sheets_tabs() {
	return array(
		'configurazione' => 'Configurazione',
		'soci'           => 'Iscrizioni soci',
		'corsi'          => 'Iscrizioni corsi',
		'script'         => 'Script Apps Script',
	);
}

/**
 * Tab aperto (dal parametro "tab" dell'indirizzo).
 */
function vv_sheets_tab_attivo() {
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( vv_sheets_tabs()[ $tab ] ) ? $tab : 'configurazione';
}

/**
 * Indirizzo della pagina, su un tab.
 *
 * @param string $tab Tab.
 */
function vv_sheets_url( $tab = 'configurazione' ) {
	return admin_url( 'tools.php?page=vv-iscrizioni&tab=' . rawurlencode( $tab ) );
}

function vv_sheets_menu() {
	$hook = add_management_page( 'Esporta iscrizioni', 'Esporta iscrizioni', 'manage_options', 'vv-iscrizioni', 'vv_sheets_page' );
	if ( $hook ) {
		add_action( "load-{$hook}", 'vv_sheets_azioni' );
	}
}
add_action( 'admin_menu', 'vv_sheets_menu' );

/**
 * Messaggio da mostrare dopo il reindirizzamento che segue un'azione.
 *
 * @param string $tipo  success, warning, error.
 * @param string $testo Messaggio.
 */
function vv_sheets_avviso( $tipo, $testo ) {
	$chiave   = 'vv_sheets_avvisi_' . get_current_user_id();
	$avvisi   = (array) get_transient( $chiave );
	$avvisi[] = array( $tipo, $testo );
	set_transient( $chiave, array_filter( $avvisi ), MINUTE_IN_SECONDS );
}

/**
 * Azioni dei pulsanti, prima che la pagina parta: poi si torna sul tab con una GET, così un
 * aggiornamento della pagina non ripete l'azione.
 */
function vv_sheets_azioni() {
	if ( empty( $_POST['vv_azione'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'vv_sheets' );
	$azione = sanitize_key( wp_unslash( $_POST['vv_azione'] ) );
	$id     = isset( $_POST['vv_id'] ) ? sanitize_key( wp_unslash( $_POST['vv_id'] ) ) : '';
	$tab    = isset( $_POST['vv_tab'] ) ? sanitize_key( wp_unslash( $_POST['vv_tab'] ) ) : 'configurazione';
	if ( ! isset( vv_sheets_collegamenti()[ $id ] ) ) {
		wp_safe_redirect( vv_sheets_url( $tab ) );
		exit;
	}
	$c = vv_sheets_config( $id );

	if ( 'salva' === $azione || 'verifica' === $azione ) {
		$dati = isset( $_POST['vv'] ) && is_array( $_POST['vv'] ) ? map_deep( wp_unslash( $_POST['vv'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		foreach ( vv_sheets_salva_impostazioni( $id, $dati ) as $avviso ) {
			vv_sheets_avviso( 'warning', $avviso );
		}
		vv_sheets_avviso( 'success', "{$c['titolo']}: impostazioni salvate." );
		vv_sheets_schedule();
		if ( 'corsi' === $id ) {
			delete_transient( 'vv_corsi_mappa' );
		}
		if ( 'verifica' === $azione ) {
			$esito = vv_sheets_verifica( $id );
			vv_sheets_avviso(
				is_wp_error( $esito ) ? 'error' : 'success',
				is_wp_error( $esito )
					? "{$c['titolo']}, verifica non riuscita: " . $esito->get_error_message()
					: ( '' !== $esito
						? "{$c['titolo']}: risponde il foglio «{$esito}», indirizzo, codice e foglio sono giusti."
						: "{$c['titolo']}: il foglio risponde, indirizzo e codice sono giusti. Lo script pubblicato è di una versione precedente: per usare {$c['prefisso']}_FOGLIO va incollata e pubblicata la versione nuova." )
			);
		}
	} elseif ( 'invia' === $azione && vv_sheets_configurato( $id ) ) {
		$esito = vv_sheets_sync( $id );
		vv_sheets_avviso(
			$esito['errore'] ? 'error' : 'success',
			sprintf( 'Inviate %d iscrizioni.', $esito['inviate'] ) . ( $esito['errore'] ? ' Errore: ' . $esito['errore'] : '' )
		);
	} elseif ( 'riparti' === $azione && vv_sheets_configurato( $id ) ) {
		delete_option( $c['ultimo'] );
		vv_sheets_avviso( 'success', 'Si riparte dal punto di partenza: le righe già nel foglio restano senza doppioni.' );
	} elseif ( 'mappa' === $azione && 'corsi' === $id ) {
		$mappa = vv_corsi_mappa( true );
		vv_sheets_avviso(
			is_wp_error( $mappa ) ? 'error' : 'success',
			is_wp_error( $mappa ) ? $mappa->get_error_message() : sprintf( 'Tab "configurazioni" riletto: %d corsi.', count( $mappa['mappa'] ) )
		);
	}
	wp_safe_redirect( vv_sheets_url( $tab ) );
	exit;
}

/**
 * Apertura di un modulo dei pulsanti: nonce, collegamento, tab a cui tornare.
 *
 * @param string $id  Collegamento.
 * @param string $tab Tab.
 */
function vv_sheets_form_apri( $id, $tab ) {
	echo '<form method="post" action="' . esc_url( vv_sheets_url( $tab ) ) . '">';
	wp_nonce_field( 'vv_sheets' );
	echo '<input type="hidden" name="vv_id" value="' . esc_attr( $id ) . '">';
	echo '<input type="hidden" name="vv_tab" value="' . esc_attr( $tab ) . '">';
}

function vv_sheets_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$attivo = vv_sheets_tab_attivo();
	$chiave = 'vv_sheets_avvisi_' . get_current_user_id();
	$avvisi = (array) get_transient( $chiave );
	delete_transient( $chiave );
	?>
	<div class="wrap">
		<h1>Esporta iscrizioni</h1>
		<nav class="nav-tab-wrapper" style="margin-bottom:1em">
			<?php foreach ( vv_sheets_tabs() as $tab => $titolo ) : ?>
				<a href="<?php echo esc_url( vv_sheets_url( $tab ) ); ?>" class="nav-tab<?php echo $tab === $attivo ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $titolo ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php foreach ( array_filter( $avvisi ) as $avviso ) : ?>
			<div class="notice notice-<?php echo esc_attr( $avviso[0] ); ?> is-dismissible"><p><?php echo esc_html( $avviso[1] ); ?></p></div>
		<?php endforeach; ?>
		<?php
		if ( 'configurazione' === $attivo ) {
			vv_sheets_tab_configurazione();
		} elseif ( 'script' === $attivo ) {
			vv_sheets_tab_script();
		} else {
			vv_sheets_sezione( $attivo );
		}
		?>
	</div>
	<?php
}

/**
 * Moduli di Forminator per la tendina: id => nome.
 */
function vv_sheets_moduli() {
	if ( ! class_exists( 'Forminator_API' ) || ! method_exists( 'Forminator_API', 'get_forms' ) ) {
		return array();
	}
	$moduli = Forminator_API::get_forms( null, 1, 200 );
	$out    = array();
	foreach ( is_array( $moduli ) ? $moduli : array() as $m ) {
		if ( ! is_object( $m ) || empty( $m->id ) ) {
			continue;
		}
		$nome                = (string) ( $m->settings['formName'] ?? $m->name ?? '' );
		$out[ (int) $m->id ] = sprintf( 'n. %d — %s', (int) $m->id, '' !== $nome ? $nome : 'senza nome' );
	}
	ksort( $out );
	return $out;
}

/**
 * Tab Configurazione: indirizzo, codice, modulo e data di ogni collegamento, con la guida.
 */
function vv_sheets_tab_configurazione() {
	$moduli = vv_sheets_moduli();
	?>
	<p>Le iscrizioni dei moduli Forminator arrivano da sole nei fogli Google, ogni 15 minuti, attraverso un'app web di Apps Script pubblicata dal foglio. Per ogni foglio servono l'ID del foglio, l'indirizzo dell'app web e il suo codice segreto; modulo e data di partenza hanno già un valore predefinito. La guida è in fondo alla pagina.</p>
	<?php
	foreach ( array_keys( vv_sheets_collegamenti() ) as $id ) :
		$c      = vv_sheets_config( $id );
		$p      = $c['prefisso'];
		$attivo = vv_sheets_configurato( $id );
		vv_sheets_form_apri( $id, 'configurazione' );
		?>
		<h2 style="margin-top:1.5em"><?php echo esc_html( $c['titolo'] ); ?> <span style="font-weight:normal;font-size:13px">— foglio "<?php echo esc_html( $c['foglio'] ); ?>", <?php echo $attivo ? '<strong>attivo</strong>' : 'spento'; ?></span></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vv-<?php echo esc_attr( $id ); ?>-url"><code><?php echo esc_html( $p ); ?>_URL</code></label></th>
				<td>
					<input type="url" class="large-text code" id="vv-<?php echo esc_attr( $id ); ?>-url" name="vv[url]" value="<?php echo esc_attr( $c['url'] ); ?>" placeholder="https://script.google.com/macros/s/…/exec">
					<p class="description">URL dell'app web, dal deployment dello script. Vuoto: collegamento spento.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vv-<?php echo esc_attr( $id ); ?>-foglio"><code><?php echo esc_html( $p ); ?>_FOGLIO</code></label></th>
				<td>
					<input type="text" class="large-text code" id="vv-<?php echo esc_attr( $id ); ?>-foglio" name="vv[foglio]" value="<?php echo esc_attr( $c['foglio_id'] ); ?>" placeholder="https://docs.google.com/spreadsheets/d/…/edit">
					<p class="description">
						ID del foglio Google (la parte dell'indirizzo fra <code>/d/</code> e <code>/edit</code>) o l'indirizzo intero. Per passare da una copia di prova al foglio vero basta cambiare questo valore.
						<?php if ( '' !== $c['foglio_id'] ) : ?>
							<a href="<?php echo esc_url( 'https://docs.google.com/spreadsheets/d/' . rawurlencode( $c['foglio_id'] ) . '/edit' ); ?>" target="_blank" rel="noopener">Apri il foglio</a>
						<?php endif; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vv-<?php echo esc_attr( $id ); ?>-token"><code><?php echo esc_html( $p ); ?>_TOKEN</code></label></th>
				<td>
					<input type="password" class="regular-text code" id="vv-<?php echo esc_attr( $id ); ?>-token" name="vv[token]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $c['token'] ? 'salvato, termina con …' . substr( $c['token'], -4 ) : '' ); ?>">
					<p class="description">Codice segreto prodotto da <code>creaToken</code> nello script. Lasciare vuoto per conservare quello salvato.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vv-<?php echo esc_attr( $id ); ?>-form"><code><?php echo esc_html( $p ); ?>_FORM</code></label></th>
				<td>
					<?php if ( $moduli ) : ?>
						<select id="vv-<?php echo esc_attr( $id ); ?>-form" name="vv[form]">
							<?php if ( ! isset( $moduli[ $c['form'] ] ) ) : ?>
								<option value="<?php echo (int) $c['form']; ?>" selected>n. <?php echo (int) $c['form']; ?> — modulo assente</option>
							<?php endif; ?>
							<?php foreach ( $moduli as $form => $nome ) : ?>
								<option value="<?php echo (int) $form; ?>"<?php selected( $form, $c['form'] ); ?>><?php echo esc_html( $nome ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input type="number" min="1" class="small-text" id="vv-<?php echo esc_attr( $id ); ?>-form" name="vv[form]" value="<?php echo (int) $c['form']; ?>">
					<?php endif; ?>
					<p class="description">Modulo Forminator da esportare. Cambiandolo, l'invio riparte da capo.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vv-<?php echo esc_attr( $id ); ?>-dal"><code><?php echo esc_html( $p ); ?>_DAL</code></label></th>
				<td>
					<input type="date" id="vv-<?php echo esc_attr( $id ); ?>-dal" name="vv[dal]" value="<?php echo esc_attr( $c['dal'] ); ?>">
					<p class="description">
						Facoltativa: le iscrizioni precedenti a questa data restano fuori.
						<?php echo $c['inizio'] ? 'Questo collegamento parte comunque dalle iscrizioni arrivate dopo la sua attivazione.' : ''; ?>
					</p>
				</td>
			</tr>
		</table>
		<p>
			<button class="button button-primary" name="vv_azione" value="salva">Salva</button>
			<button class="button" name="vv_azione" value="verifica">Salva e verifica il collegamento</button>
		</p>
		</form>
	<?php endforeach; ?>

	<hr style="margin:2em 0">
	<h2>Guida alla compilazione</h2>
	<ol style="max-width:820px">
		<li><strong>Script nel Drive.</strong> In Google Drive, nella cartella dell'associazione: Nuovo → Altro → Google Apps Script (progetto autonomo, consigliato per i fogli in un Drive condiviso). In alternativa, dal foglio: Estensioni → Apps Script. Incollare al posto del codice esistente il testo del tab <a href="<?php echo esc_url( vv_sheets_url( 'script' ) ); ?>">Script Apps Script</a>.</li>
		<li><strong>Foglio (<code>…_FOGLIO</code>).</strong> L'indirizzo del foglio, copiato dalla barra del browser, o il solo ID (la parte fra <code>/d/</code> e <code>/edit</code>). Il sito lo manda allo script a ogni chiamata: lo script si incolla così com'è, con <code>ID_FOGLIO</code> vuoto. Lo stesso progetto serve per la copia di prova e per il foglio vero; basta che l'account che pubblica lo script possa modificarli.</li>
		<li><strong>Codice segreto (<code>…_TOKEN</code>).</strong> Nell'editor, scegliere la funzione <code>creaToken</code> e premere Esegui. La prima volta Google chiede le autorizzazioni (Avanzate → Vai a … → Consenti). Nel registro di esecuzione compare il codice di 64 caratteri da incollare qui.</li>
		<li><strong>Indirizzo (<code>…_URL</code>).</strong> Esegui il deployment → Nuovo deployment → tipo App web; Esegui come: Me; Chi ha accesso: Chiunque. L'URL dell'app web termina con <code>/exec</code>.</li>
		<li><strong>Modulo (<code>…_FORM</code>) e data (<code>…_DAL</code>).</strong> Valori predefiniti: soci, modulo n. 274 dal 1° settembre 2026; corsi, modulo n. 1617 dalle iscrizioni nuove.</li>
		<li><strong>Verifica.</strong> "Salva e verifica il collegamento" manda al foglio una richiesta vuota: la risposta riporta il nome del foglio raggiunto e conferma indirizzo e codice senza scrivere nulla. Poi, nei tab dei soci e dei corsi, la prova a secco mostra cosa partirebbe.</li>
		<li><strong>Script modificato.</strong> Dopo aver incollato una versione nuova: Esegui il deployment → Gestisci deployment → matita → Versione: Nuova versione. URL, codice e foglio restano gli stessi.</li>
	</ol>
	<h3>Foglio dei corsi: il tab "configurazioni"</h3>
	<p style="max-width:820px">Il foglio dei corsi deve contenere un tab chiamato <code>configurazioni</code>. Riga 1: intestazioni (<code>Tab</code>, <code>Corso</code>). Dalla riga 2, una riga per corso: in colonna A il nome del tab del foglio, in colonna B il nome esteso del corso, cioè il testo dell'opzione del modulo (elenco nel tab <a href="<?php echo esc_url( vv_sheets_url( 'corsi' ) ); ?>">Iscrizioni corsi</a>). Maiuscole, accenti e spazi doppi contano poco; il resto deve coincidere. Un corso assente, o con un tab inesistente, finisce in "Da smistare". Un tab ancora vuoto riceve alla prima iscrizione le intestazioni del sito, con <code>Rif.</code> in colonna A; lo script scrive solo nelle colonne con quelle intestazioni e lascia intatte le altre, che la segreteria organizza come crede. Per un corso nuovo basta un tab e una riga in <code>configurazioni</code>, senza toccare il sito.</p>
	<h3>Se il deployment risponde "Non disponi dell'autorizzazione necessaria"</h3>
	<ul style="max-width:820px;list-style:disc;padding-left:1.5em">
		<li>Script collegato a un foglio in un Drive condiviso: in Dettagli progetto compare "Contenitore". Rimedio: progetto autonomo con <code>ID_FOGLIO</code>.</li>
		<li>Più account Google aperti nel browser (l'indirizzo dell'editor contiene <code>/u/1/</code>): una finestra anonima con un solo account.</li>
		<li>Permesso appena cambiato: chiudere e riaprire l'editor.</li>
		<li>Nel dominio Workspace manca la voce "Chiunque" in "Chi ha accesso": serve l'amministratore del dominio.</li>
	</ul>
	<?php
}

/**
 * Tab Script Apps Script: il testo da incollare nell'editor, con il pulsante per copiarlo.
 */
function vv_sheets_tab_script() {
	?>
	<p style="max-width:820px">Testo completo degli script da incollare nell'editor di Apps Script, al posto del codice esistente. Istruzioni passo per passo nel tab <a href="<?php echo esc_url( vv_sheets_url() ); ?>">Configurazione</a>. Dopo ogni nuova versione del tema conviene confrontare lo script pubblicato con quello qui sotto e, se diverso, pubblicare una Nuova versione del deployment.</p>
	<?php foreach ( array_keys( vv_sheets_collegamenti() ) as $id ) : ?>
		<?php
		$c     = vv_sheets_config( $id );
		$testo = vv_sheets_script( $id );
		if ( '' === $testo ) {
			continue;
		}
		?>
		<h2 style="margin-top:1.5em"><?php echo esc_html( $c['titolo'] ); ?> <span style="font-weight:normal;font-size:13px">— foglio "<?php echo esc_html( $c['foglio'] ); ?>", <code><?php echo esc_html( $c['script'] ); ?></code></span></h2>
		<p>
			<button type="button" class="button button-primary" data-vv-copia="vv-script-<?php echo esc_attr( $id ); ?>">Copia lo script</button>
			<span class="vv-copiato" aria-live="polite" style="margin-left:.5em"></span>
		</p>
		<textarea id="vv-script-<?php echo esc_attr( $id ); ?>" class="large-text code" rows="18" readonly spellcheck="false" style="font-size:12px"><?php echo esc_textarea( $testo ); ?></textarea>
	<?php endforeach; ?>
	<script>
	document.querySelectorAll( '[data-vv-copia]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var area = document.getElementById( b.getAttribute( 'data-vv-copia' ) );
			var nota = b.parentNode.querySelector( '.vv-copiato' );
			var fatto = function () { nota.textContent = 'Copiato.'; };
			var aMano = function () {
				area.focus();
				area.select();
				nota.textContent = document.execCommand( 'copy' ) ? 'Copiato.' : 'Testo selezionato: Ctrl+C per copiarlo.';
			};
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( area.value ).then( fatto, aMano );
			} else {
				aMano();
			}
		} );
	} );
	</script>
	<?php
}

/**
 * Tab di un collegamento: stato, prova a secco, colonne, invio subito, ripartenza. Per i corsi
 * anche la corrispondenza corso → tab letta dal foglio.
 *
 * @param string $id Collegamento.
 */
function vv_sheets_sezione( $id ) {
	$c        = vv_sheets_config( $id );
	$quando   = static fn( $t ) => $t ? wp_date( 'j F Y, H:i', (int) $t ) : '—';
	$attivo   = vv_sheets_configurato( $id );
	$ultimo   = $attivo ? vv_sheets_ultimo( $id ) : 0;
	$inizio   = $c['inizio'] ? (int) get_option( $c['inizio'], 0 ) : 0;
	$stato    = (array) get_option( $c['stato'], array() );
	$colonne  = (array) call_user_func( $c['colonne'] );
	$mappa    = null;
	$gruppo   = null;
	$partenza = $c['inizio']
		? sprintf( 'dopo l\'iscrizione n. %d (la più recente quando il collegamento è stato attivato)', $inizio )
		: ( $c['dal'] ? 'dal ' . wp_date( 'j F Y', strtotime( $c['dal'] ) ) : 'dalla prima' );

	if ( 'corsi' === $id && $attivo ) {
		$mappa  = vv_corsi_mappa();
		$gruppo = is_wp_error( $mappa )
			? static fn( $riga ) => 'corso: ' . ( '' !== $riga['Corso'] ? $riga['Corso'] : '(nessuno)' )
			: static fn( $riga ) => vv_corsi_tab( $riga['Corso'], $mappa );
	}
	$prova = $attivo ? vv_sheets_prova( $id, $gruppo ) : null;
	?>
	<h2><?php echo esc_html( $c['titolo'] ); ?></h2>
	<p>Modulo Forminator n. <?php echo (int) $c['form']; ?> → foglio "<?php echo esc_html( $c['foglio'] ); ?>"<?php echo $attivo ? esc_html( ', iscrizioni ' . $partenza ) : ''; ?>.</p>
	<?php if ( ! $attivo ) : ?>
		<div class="notice notice-warning inline"><p>Spento: servono <code><?php echo esc_html( $c['prefisso'] ); ?>_URL</code> e <code><?php echo esc_html( $c['prefisso'] ); ?>_TOKEN</code> nel tab <a href="<?php echo esc_url( vv_sheets_url() ); ?>">Configurazione</a> e Forminator attivo.</p></div>
	<?php endif; ?>
	<?php if ( is_wp_error( $mappa ) ) : ?>
		<div class="notice notice-warning inline"><p>Tab "configurazioni" del foglio non letto: <?php echo esc_html( $mappa->get_error_message() ); ?> La prova a secco raggruppa le righe per corso.</p></div>
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

	<h3>Colonne</h3>
	<table class="widefat striped" style="max-width:720px">
		<thead><tr><th>Colonna del foglio</th><th>Campo del modulo</th><?php echo $prova ? '<th>Righe con la colonna piena</th>' : ''; ?></tr></thead>
		<tbody>
			<?php foreach ( $colonne as $col => $campo ) : ?>
				<tr>
					<td><?php echo esc_html( $col ); ?></td>
					<td><code><?php echo esc_html( implode( ' → ', (array) $campo ) ); ?></code></td>
					<?php if ( $prova ) : ?>
						<td><?php echo (int) ( $prova['piene'][ $col ] ?? 0 ); ?> su <?php echo (int) $prova['righe']; ?></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( 'corsi' === $id && $attivo ) : ?>
		<?php vv_corsi_sezione_mappa( $mappa ); ?>
	<?php endif; ?>

	<?php if ( class_exists( 'Forminator_API' ) ) : ?>
		<details style="margin-top:1em;max-width:720px">
			<summary>Campi del modulo n. <?php echo (int) $c['form']; ?></summary>
			<p>Per controllare la corrispondenza delle colonne (<code>vv_<?php echo esc_html( $id ); ?>_colonne()</code> in <code>inc/sheets.php</code>). Solo la struttura del modulo, nessun dato delle iscrizioni.</p>
			<table class="widefat striped">
				<thead><tr><th>Id</th><th>Etichetta</th><th>Tipo</th><th>Opzioni</th></tr></thead>
				<tbody>
					<?php foreach ( vv_sheets_campi( $c['form'] ) as $f ) : ?>
						<tr>
							<td><code><?php echo esc_html( $f['id'] ); ?></code></td>
							<td><?php echo esc_html( $f['etichetta'] ); ?></td>
							<td><?php echo esc_html( $f['tipo'] ); ?></td>
							<td>
								<?php foreach ( $f['opzioni'] as $o ) : ?>
									<?php echo esc_html( $o['etichetta'] === $o['valore'] ? $o['valore'] : "{$o['etichetta']} = {$o['valore']}" ); ?><br>
								<?php endforeach; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
	<?php endif; ?>

	<?php if ( $attivo ) : ?>
		<?php vv_sheets_form_apri( $id, $id ); ?>
			<p style="margin-top:1.5em">
				<button class="button button-primary" name="vv_azione" value="invia">Invia adesso</button>
				<button class="button" name="vv_azione" value="riparti" onclick="return confirm('Ricominciare <?php echo esc_js( $partenza ); ?>? Le righe già nel foglio non vengono duplicate.');">Ricomincia dal punto di partenza</button>
			</p>
		</form>
	<?php endif; ?>
	<?php
}

/**
 * Corsi: opzioni del modulo e tab in cui finiscono, secondo il tab "configurazioni" del foglio.
 *
 * @param array|WP_Error $mappa Risultato di vv_corsi_mappa().
 */
function vv_corsi_sezione_mappa( $mappa ) {
	$opzioni = vv_corsi_opzioni();
	$ok      = ! is_wp_error( $mappa );
	?>
	<h3>Corsi e tab del foglio</h3>
	<p style="max-width:720px">Opzioni del campo <code><?php echo esc_html( VV_CORSI_CAMPO ); ?></code> del modulo e tab in cui il foglio le scrive, secondo il suo tab <code>configurazioni</code> (colonna A: tab, colonna B: testo del corso). Per spostare un corso in un altro tab, o aggiungerne uno, basta modificare <code>configurazioni</code> nel foglio.</p>
	<table class="widefat striped" style="max-width:720px">
		<thead><tr><th>Corso (testo dell'opzione, da copiare in colonna B)</th><th>Tab</th></tr></thead>
		<tbody>
			<?php foreach ( $opzioni as $corso ) : ?>
				<?php $tab = $ok ? vv_corsi_tab( $corso, $mappa ) : ''; ?>
				<tr>
					<td><?php echo esc_html( $corso ); ?></td>
					<td>
						<?php
						if ( ! $ok ) {
							echo '—';
						} elseif ( VV_CORSI_DA_SMISTARE === $tab ) {
							echo '<strong>' . esc_html( $tab ) . '</strong>';
						} else {
							echo '<code>' . esc_html( $tab ) . '</code>';
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $opzioni ) : ?>
				<tr><td colspan="2"><em>Nessuna opzione letta dal campo del modulo.</em></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php if ( $ok ) : ?>
		<?php
		$senza = array();
		foreach ( $mappa['mappa'] as $corso => $tab ) {
			if ( ! in_array( $tab, $mappa['tab'], true ) ) {
				$senza[] = "{$tab} ({$corso})";
			}
		}
		?>
		<?php if ( $senza ) : ?>
			<p>In <code>configurazioni</code> ci sono tab che il foglio non contiene: <?php echo esc_html( implode( '; ', $senza ) ); ?>.</p>
		<?php endif; ?>
	<?php endif; ?>
	<?php vv_sheets_form_apri( 'corsi', 'corsi' ); ?>
		<p><button class="button" name="vv_azione" value="mappa">Rileggi il tab "configurazioni"</button> <span class="description">Il sito ne tiene una copia per 5 minuti.</span></p>
	</form>
	<?php
}
