# Attivazione del foglio dei corsi: configurazione e collaudo

Guida passo per passo per mettere in funzione la 0.8.0: le iscrizioni del modulo "Iscrizione ai corsi 2026-2027" (Forminator n. 1617, pagina `/iscrizione-corso/`) arrivano da sole nel foglio Google "VivaVoce / Iscrizione Corsi", un tab per corso.

Il percorso ha tre tempi: collaudo su una copia del foglio, passaggio al foglio vero, controlli finali. Servono circa 45 minuti, con l'accesso da amministratore a WordPress, l'accesso FTP o al pannello dell'hosting per `wp-config.php` e l'account Google che gestirà lo script (meglio quello dell'associazione).

## 0. Prima di cominciare

1. **Copia di sicurezza.** Dal pannello dell'hosting, backup di file e database; in alternativa, almeno una copia di `wp-config.php` e della cartella `wp-content/themes/wvoce-classic`.
2. **Tab del foglio.** Nel foglio vero i tab devono chiamarsi esattamente:
   - `lettura-ad-alta-voce-2026` (il modello, con le intestazioni A–N)
   - `tecnica-attoriale-2026`
   - `messa-in-scena-2027`
   - `gioco-del-teatro-2026` (se il tab si chiama ancora `gioco-del-teatro-2206`, va rinominato: doppio clic sul nome del tab)
3. **Intestazioni del modello.** Nella riga 1 di `lettura-ad-alta-voce-2026`: Nome, Cognome, Codice Fiscale, Email, Cellulare, Ricevuta, Regolamento, Socio, Privacy, Note (A–J), poi le colonne della segreteria (K–N). Lo script scrive per intestazione: maiuscole e spazi contano poco, la parola invece deve coincidere.

## 1. Aggiornamento del tema sul sito

1. Sul computer: scaricare lo zip del ramo `main` da GitHub (Code → Download ZIP) oppure, da una copia locale del repository, lanciare `bash bin/build-zip.sh`, che produce `build/wvoce-classic.zip` con la cartella giusta.
   - Con lo zip di GitHub la cartella interna si chiama `wvoce-classic-main`: va rinominata `wvoce-classic` e lo zip rifatto, altrimenti WordPress installa un tema separato.
2. WordPress → Aspetto → Temi → Aggiungi nuovo tema → Carica tema → scegliere lo zip → Installa ora.
3. Alla domanda "Il tema è già installato", scegliere **Sostituisci quello attuale con quello caricato**.
4. Controllo: Aspetto → Temi, il tema VivaVoce indica la versione **0.8.0**. Aprire la home e `/iscrizione-corso/`: l'aspetto resta quello di prima.
5. Strumenti → **Fogli Google** (prima "Foglio soci"): la sezione dei soci mostra gli stessi numeri di prima; la sezione dei corsi risulta "Spento", perché mancano ancora le costanti.

## 2. Collaudo su una copia del foglio

### 2.1 Copia e script

Lo script è un **progetto autonomo nel Drive condiviso "VivaVoce - Archivio Digitale"**, come quello dei soci ("wvoce.it - iscrizione soci"), e raggiunge il foglio tramite `ID_FOGLIO`. Uno script collegato al foglio (Estensioni → Apps Script) si può eseguire, ma il deployment come app web risponde "Non disponi dell'autorizzazione necessaria": per i file di un Drive condiviso il proprietario è il Drive, e la pubblicazione di uno script collegato resta bloccata anche per chi lo gestisce. Lo stesso progetto serve prima per la copia e poi per il foglio vero: cambia solo `ID_FOGLIO`, mentre URL e token restano gli stessi.

1. Nel foglio vero: File → Crea una copia. Nome, per esempio, "PROVA Iscrizione Corsi". Dall'indirizzo della copia annotare l'ID, cioè la parte fra `/d/` e `/edit`.
2. In Google Drive → Drive condivisi → **VivaVoce - Archivio Digitale** (nella stessa cartella del progetto dei soci): Nuovo → Altro → **Google Apps Script**. Rinominare il progetto "wvoce.it - iscrizione corsi". In *Dettagli progetto* compare il proprietario "VivaVoce - Archivio Digitale" e nessun "Contenitore".
3. Nell'editor, file `Codice.gs`: cancellare tutto e incollare il testo di `docs/apps-script-iscrizione-corsi.gs` (da GitHub: aprire il file → pulsante "Raw" → selezionare tutto e copiare). Alla riga `var ID_FOGLIO = '';` mettere l'ID della copia fra gli apici. Salvare (icona del dischetto).
4. In alto, nel menu a tendina delle funzioni, scegliere **creaToken** e premere **Esegui**.
   - La prima volta Google chiede le autorizzazioni: Rivedi autorizzazioni → scegliere l'account → se compare "Google non ha verificato questa app": Avanzate → Vai a … (non sicuro) → Consenti. È lo script appena incollato, che chiede di modificare i fogli.
   - Nel registro di esecuzione, in basso, compare `VV_CORSI_TOKEN: …`: copiare il codice (64 caratteri) in un posto sicuro.
5. Esegui il deployment → **Nuovo deployment** → icona dell'ingranaggio → **App web**.
   - Descrizione: "Iscrizioni corsi".
   - Esegui come: **Me**.
   - Chi ha accesso: **Chiunque**.
   - Deployment → copiare l'**URL dell'app web**, che finisce con `/exec`.
6. Uno script eventualmente incollato prima dentro il foglio (Estensioni → Apps Script) resta inutilizzato: si può svuotare o eliminare.

> **Se il deployment risponde "Non disponi dell'autorizzazione necessaria per eseguire questa azione".**
> 1. **Script collegato al foglio.** In *Dettagli progetto* compare la voce "Contenitore" e l'icona è quella verde dei Fogli: è il caso descritto sopra. Rimedio: il progetto autonomo del punto 2.
> 2. **Più account Google aperti nel browser.** L'editor usa l'account predefinito, che può essere diverso da quello giusto: l'indirizzo dell'editor contiene `/u/1/` o `/u/2/`. Rimedio: una finestra anonima con un solo account.
> 3. **Permesso appena cambiato.** Rimedio: chiudere la scheda dell'editor e riaprirla.
> 4. **Limiti del dominio Workspace.** Se in "Chi ha accesso" manca la voce "Chiunque", serve l'intervento dell'amministratore.

### 2.2 Costanti in WordPress

1. Aprire `wp-config.php` (FTP o file manager dell'hosting), nella cartella principale di WordPress.
2. Sopra la riga `/* That's all, stop editing! */` (o `/* Questo è tutto, smetti di modificare! */`), aggiungere:

   ```php
   define( 'VV_CORSI_URL', 'https://script.google.com/macros/s/…/exec' );
   define( 'VV_CORSI_TOKEN', 'il-codice-di-64-caratteri' );
   ```

3. Salvare. Un errore di battitura in questo file blocca il sito: in quel caso basta ripristinare la copia del passo 0.

### 2.3 Primo controllo nella pagina degli strumenti

Strumenti → Fogli Google → sezione dei corsi:

- la riga sotto il titolo indica "iscrizioni dopo l'iscrizione n. X": X è la più recente già presente, fissata come punto di partenza. Le iscrizioni precedenti restano fuori dal foglio, come concordato;
- tabella di stato: Errore "nessuno";
- **Prova a secco**: "0 iscrizioni da inviare";
- tendina **Campi del modulo n. 1617**: per ognuna delle quattro opzioni del corso compare il tab giusto dopo la freccia. Una freccia verso "Da smistare" indica un'opzione del modulo diversa dal testo previsto (vedi *Manutenzione*).

### 2.4 Iscrizioni di prova

Dal sito, in una finestra anonima, compilare il modulo `/iscrizione-corso/` quattro volte. Usare dati riconoscibili (cognome "Prova1", "Prova2"…) e un codice fiscale valido, anche il proprio: il modulo lo controlla.

| Prova | Cosa compilare | Risultato atteso nella copia |
|---|---|---|
| 1 | Un solo corso: lettura ad alta voce. Cellulare con zero iniziale (es. 0612345678). Ricevuta allegata. Una nota | Una riga in `lettura-ad-alta-voce-2026`, A–J compilate, cellulare con lo zero, link alla ricevuta in F, TRUE in G–I, K–N vuote, "Rif." in O (`F1617-…`) |
| 2 | Due corsi: tecniche attoriali e messa in scena | Una riga in `tecnica-attoriale-2026` e una in `messa-in-scena-2027`, con lo stesso Rif. I due tab, vuoti, ricevono prima le intestazioni del modello |
| 3 | Il gioco del teatro | Una riga in `gioco-del-teatro-2026` |
| 4 | Nome `=1+1`, un corso qualsiasi | Nella cella del nome si legge `=1+1` come testo, il calcolo resta fuori |

Dopo le quattro prove:

1. Strumenti → Fogli Google → corsi: Prova a secco "4 iscrizioni da inviare, 5 righe", con il conto per tab. La tabella delle colonne mostra quante righe hanno ogni colonna piena: Nome, Cognome, Codice Fiscale, Email e i tre consensi 5 su 5; Ricevuta e Note solo le righe delle prove che le avevano.
2. Premere **Invia adesso**. Messaggio atteso: "Inviate 4 iscrizioni." (il conto è per iscrizione, le righe sono 5) Nel giro di qualche secondo le righe compaiono nella copia.
3. Controllare i tab come da tabella.

### 2.5 Doppioni, errori, Da smistare

1. **Reinvio:** premere **Ricomincia dal punto di partenza**, confermare, poi **Invia adesso**. Il messaggio dice di nuovo "Inviate 4 iscrizioni", ma nel foglio le righe restano quelle: lo script riconosce il Rif. e salta le righe già scritte.
2. **Da smistare:** nella copia, rinominare per un momento il tab `gioco-del-teatro-2026` (per esempio `gioco-x`), compilare un'altra iscrizione al gioco del teatro e premere Invia adesso. Compare il tab **Da smistare**, con le intestazioni del modello, la colonna "Corso" con il testo dell'opzione e la riga della prova. Poi rimettere il nome giusto al tab e cancellare "Da smistare".
3. **Errore di collegamento:** in `wp-config.php` cambiare un carattere del token, compilare un'iscrizione, premere Invia adesso. Atteso: "Inviate 0 iscrizioni. Errore: codice segreto sbagliato" e riga Errore valorizzata nella tabella di stato; nessuna riga nel foglio. Rimettere il token giusto e premere Invia adesso: la riga arriva, l'errore sparisce.
4. **Invio automatico:** compilare un'ultima iscrizione e attendere senza premere nulla. Entro 15–20 minuti la riga arriva da sola (il controllo gira ogni 15 minuti con wp-cron, che parte con le visite al sito: su un sito poco visitato può tardare). "Prossimo controllo" nella tabella di stato indica l'orario previsto.

## 3. Passaggio al foglio vero

1. **Pulizia delle prove.** WordPress → Forminator → Iscrizioni (Submissions) → modulo "Iscrizione ai corsi 2026-2027": selezionare le iscrizioni di prova ed eliminarle. Così il punto 3.4 le lascia fuori dal foglio vero.
2. **Script sul foglio vero.** Nel progetto "wvoce.it - iscrizione corsi": `ID_FOGLIO` con l'ID del foglio vero (`1TvSREeUfoeAcA1uI3Hx9qpytOVUOMQQc5664DqwxUOw`), salvare, poi Esegui il deployment → Gestisci deployment → icona della matita → Versione: **Nuova versione** → Esegui il deployment.
3. **Costanti.** URL e token restano gli stessi: `wp-config.php` resta com'è.
4. **Iscrizioni arrivate durante il collaudo.** Eventuali iscrizioni vere compilate mentre il sito puntava alla copia sono finite lì. Per recuperarle: Strumenti → Fogli Google → corsi → **Ricomincia dal punto di partenza**, poi **Invia adesso**. Riparte tutto dal punto fissato al primo avvio; le prove, già eliminate, restano fuori.
5. **Controllo.** Una sola iscrizione di prova sul sito, verifica della riga nel tab giusto del foglio vero, poi cancellazione della riga nel foglio e dell'iscrizione in Forminator.
6. **Copia di prova.** Lo script ormai scrive nel foglio vero: la copia si può eliminare.

## 4. Riservatezza e chiusura

1. **Condivisione del foglio.** Pulsante Condividi del foglio vero → Accesso generale → **Con limitazioni**. Lo script scrive con l'account che lo ha pubblicato, quindi il link pubblico in modifica serve più a nulla. Restano gli account nominativi della segreteria.
2. **Colonna Rif.** Si può nascondere (clic destro sull'intestazione della colonna O → Nascondi colonna). Va lasciata al suo posto: è il riferimento contro i doppioni.
3. **Informativa privacy.** Riportare nel modulo 1617 e nella pagina privacy il testo di `docs/informativa-fogli-google.md`, dopo la verifica di chi cura la privacy. Da decidere a parte: i dati del genitore per le iscrizioni dei bambini, che il modulo oggi raccoglie solo per l'iscritto.
4. **Avvisi d'errore.** Se il collegamento resta in errore per 24 ore, il sito scrive all'email dell'amministratore (Impostazioni → Generali → Indirizzo email di amministrazione): conviene che sia una casella letta.

## 5. Manutenzione

- **Opzione del modulo riscritta** (un orario, una data): finché il codice resta quello attuale, le iscrizioni a quell'opzione vanno in "Da smistare". La tendina *Campi del modulo n. 1617* lo segnala con la freccia verso "Da smistare". Si corregge aggiornando il testo in `vv_corsi_tabs()` (`inc/sheets.php`) e pubblicando il tema; le righe già in "Da smistare" si spostano a mano.
- **Nuovo corso:** tab nuovo nel foglio, con lo stesso stile di nome (`nome-corso-anno`); nel codice il nome in `vv_corsi_tab_nomi()` e il testo esatto dell'opzione in `vv_corsi_tabs()`. Il tab vuoto riceve le intestazioni alla prima riga.
- **Script modificato:** Esegui il deployment → Gestisci deployment → icona della matita → Versione: **Nuova versione** → Esegui il deployment. L'URL resta lo stesso, le costanti pure.
- **Sospendere il collegamento:** togliere le due costanti da `wp-config.php`. Alla ripresa, le iscrizioni arrivate nel frattempo partono al primo controllo.

## Promemoria dei risultati attesi

| Controllo | Esito |
|---|---|
| Tema | Versione 0.8.0, aspetto invariato |
| Soci | Stessi numeri di prima in Strumenti → Fogli Google |
| Corsi, primo avvio | Partenza dopo l'iscrizione più recente, 0 da inviare |
| Quattro opzioni | Ognuna nel suo tab |
| Più corsi | Una riga per corso, stesso Rif. |
| Reinvio | Nessun doppione |
| Corso senza tab | Riga in "Da smistare" con la colonna "Corso" |
| Nome `=1+1` | Testo |
| Token sbagliato | Errore visibile, nessuna riga |
| Colonne K–N | Sempre intatte |
