# Attivazione del foglio dei corsi: configurazione e collaudo

Guida passo per passo per mettere in funzione la 0.9.1: le iscrizioni del modulo "Iscrizione ai corsi 2026-2027" (Forminator n. 1617, pagina `/iscrizione-corso/`) arrivano da sole nel foglio Google "VivaVoce / Iscrizione Corsi", un tab per corso, secondo il tab `configurazioni` del foglio.

Il percorso ha tre tempi: collaudo su una copia del foglio, passaggio al foglio vero, controlli finali. Servono circa 45 minuti, con l'accesso da amministratore a WordPress e l'account Google che gestirà lo script (meglio quello dell'associazione). L'accesso FTP a `wp-config.php` serve solo per togliere le costanti delle versioni precedenti.

## 0. Prima di cominciare

1. **Copia di sicurezza.** Dal pannello dell'hosting, backup di file e database.
2. **Tab dei corsi.** Nel foglio vero, un tab per corso:
   - `lettura-ad-alta-voce-2026`
   - `tecnica-attoriale-2026`
   - `messa-in-scena-2027`
   - `gioco-del-teatro-2026`

   I nomi sono liberi: conta che coincidano con la colonna A di `configurazioni`.
3. **Intestazioni.** Un tab vuoto riceve dallo script, alla prima iscrizione, le intestazioni del sito: Rif., Nome, Cognome, Codice Fiscale, Email, Cellulare, Ricevuta, Regolamento, Socio, Privacy, Note (A–K). In un tab già intestato a mano lo script scrive per intestazione: maiuscole e spazi contano poco, la parola invece deve coincidere. Tutte le altre colonne sono della segreteria, libera di aggiungerle, chiamarle e ordinarle come preferisce, tab per tab: lo script le lascia intatte. Unica attenzione: evitare per queste colonne i nomi usati dal sito.
4. **Tab `configurazioni`.** Un tab con questo nome esatto. Riga 1: `Tab` in A, `Corso` in B. Dalla riga 2 una riga per corso: in A il nome del tab, in B il testo completo dell'opzione del modulo.

   | Tab | Corso |
   |---|---|
   | `lettura-ad-alta-voce-2026` | Adulti: Laboratorio di lettura ad alta voce corale, ottobre-dicembre 2026 (martedì 18.30-20.30) |
   | `tecnica-attoriale-2026` | Adulti: Laboratorio di tecniche attoriali, ottobre-dicembre 2026 (giovedì 18.30-20.30) |
   | `messa-in-scena-2027` | Adulti: Laboratorio di messa in scena, gennaio-giugno 2027 (giovedì 18.30-20.30) |
   | `gioco-del-teatro-2026` | Bambini (elementari): Il gioco del teatro, ottobre-dicembre 2026 |

   Il testo esatto delle opzioni si copia dal sito, Strumenti → Esporta iscrizioni → Iscrizioni corsi, tabella *Corsi e tab del foglio*.
5. **Chi aggiorna dalla 0.8.** Annotare da `wp-config.php` i valori di `VV_SHEETS_URL`, `VV_SHEETS_TOKEN`, `VV_CORSI_URL`, `VV_CORSI_TOKEN`: dalla 0.9.0 il tema li ignora e vanno riscritti nel pannello.

## 1. Aggiornamento del tema sul sito

1. Sul computer: scaricare lo zip del ramo `main` da GitHub (Code → Download ZIP) oppure, da una copia locale del repository, lanciare `bash bin/build-zip.sh`, che produce `build/wvoce-classic.zip` con la cartella giusta.
   - Con lo zip di GitHub la cartella interna si chiama `wvoce-classic-main`: va rinominata `wvoce-classic` e lo zip rifatto, altrimenti WordPress installa un tema separato.
2. WordPress → Aspetto → Temi → Aggiungi nuovo tema → Carica tema → scegliere lo zip → Installa ora.
3. Alla domanda "Il tema è già installato", scegliere **Sostituisci quello attuale con quello caricato**.
4. Controllo: Aspetto → Temi, il tema VivaVoce indica la versione **0.9.0**. Aprire la home e `/iscrizione-corso/`: l'aspetto resta quello di prima.
5. Strumenti → **Esporta iscrizioni**: quattro tab (Configurazione, Iscrizioni soci, Iscrizioni corsi, Script Apps Script). Finché i valori mancano, i collegamenti risultano "spenti"; il punto raggiunto dalla 0.8 resta salvato.

## 2. Collaudo su una copia del foglio

### 2.1 Copia e script

Lo script è un **progetto autonomo nel Drive condiviso "VivaVoce - Archivio Digitale"**, come quello dei soci ("wvoce.it - iscrizione soci"), e raggiunge il foglio indicato dal sito (`VV_CORSI_FOGLIO`). Uno script collegato al foglio (Estensioni → Apps Script) si può eseguire, ma il deployment come app web risponde "Non disponi dell'autorizzazione necessaria": per i file di un Drive condiviso il proprietario è il Drive, e la pubblicazione di uno script collegato resta bloccata anche per chi lo gestisce. Lo stesso progetto serve prima per la copia e poi per il foglio vero: cambia solo `VV_CORSI_FOGLIO` nel pannello, mentre script, URL e token restano gli stessi.

1. Nel foglio vero (con il tab `configurazioni` del punto 0.4): File → Crea una copia. Nome, per esempio, "PROVA Iscrizione Corsi". Dall'indirizzo della copia annotare l'ID, cioè la parte fra `/d/` e `/edit`.
2. In Google Drive → Drive condivisi → **VivaVoce - Archivio Digitale** (nella stessa cartella del progetto dei soci): Nuovo → Altro → **Google Apps Script**. Rinominare il progetto "wvoce.it - iscrizione corsi". In *Dettagli progetto* compare il proprietario "VivaVoce - Archivio Digitale" e nessun "Contenitore". Chi ha già il progetto dalla 0.8 lo riapre e passa al punto 3.
3. Sul sito, Strumenti → Esporta iscrizioni → **Script Apps Script** → Foglio corsi → **Copia lo script**. Nell'editor, file `Codice.gs`: cancellare tutto e incollare così com'è (la riga `var ID_FOGLIO = '';` resta vuota). Salvare (icona del dischetto).
4. Solo per un progetto nuovo: in alto, nel menu a tendina delle funzioni, scegliere **creaToken** e premere **Esegui**.
   - La prima volta Google chiede le autorizzazioni: Rivedi autorizzazioni → scegliere l'account → se compare "Google non ha verificato questa app": Avanzate → Vai a … (non sicuro) → Consenti. È lo script appena incollato, che chiede di modificare i fogli.
   - Nel registro di esecuzione, in basso, compare `VV_CORSI_TOKEN: …`: copiare il codice (64 caratteri).
5. Progetto nuovo: Esegui il deployment → **Nuovo deployment** → icona dell'ingranaggio → **App web**; Descrizione "Iscrizioni corsi"; Esegui come: **Me**; Chi ha accesso: **Chiunque**; Deployment → copiare l'**URL dell'app web**, che finisce con `/exec`. Progetto della 0.8: Esegui il deployment → Gestisci deployment → matita → Versione: **Nuova versione** → Esegui il deployment; URL e codice restano quelli annotati.

> **Se il deployment risponde "Non disponi dell'autorizzazione necessaria per eseguire questa azione".**
> 1. **Script collegato al foglio.** In *Dettagli progetto* compare la voce "Contenitore" e l'icona è quella verde dei Fogli: è il caso descritto sopra. Rimedio: il progetto autonomo del punto 2.
> 2. **Più account Google aperti nel browser.** L'editor usa l'account predefinito, che può essere diverso da quello giusto: l'indirizzo dell'editor contiene `/u/1/` o `/u/2/`. Rimedio: una finestra anonima con un solo account.
> 3. **Permesso appena cambiato.** Rimedio: chiudere la scheda dell'editor e riaprirla.
> 4. **Limiti del dominio Workspace.** Se in "Chi ha accesso" manca la voce "Chiunque", serve l'intervento dell'amministratore.

### 2.2 Valori nel pannello

1. Strumenti → Esporta iscrizioni → **Configurazione** → riquadro *Foglio corsi*.
2. `VV_CORSI_FOGLIO`: l'indirizzo della copia, copiato dalla barra del browser (basta anche il solo ID). `VV_CORSI_URL`: l'URL dell'app web. `VV_CORSI_TOKEN`: il codice di 64 caratteri. `VV_CORSI_FORM`: "n. 1617 — Iscrizione ai corsi 2026-2027". `VV_CORSI_DAL`: vuoto (i corsi partono comunque dalle iscrizioni nuove).
3. Premere **Salva e verifica il collegamento**. Messaggio atteso: "risponde il foglio «PROVA Iscrizione Corsi», indirizzo, codice e foglio sono giusti": il nome conferma che il sito punta alla copia. Un errore indica la causa (foglio non raggiungibile, codice sbagliato, HTTP 401/403 per "Chi ha accesso", HTTP 404 per un URL errato).
4. Stesso procedimento nel riquadro *Foglio soci* con l'indirizzo del foglio "Albo Soci" in `VV_SOCI_FOGLIO` e i valori di prima (`VV_SOCI_URL`, `VV_SOCI_TOKEN`). Se lo script dei soci era un progetto autonomo con `ID_FOGLIO` compilato, va incollata la versione nuova e pubblicata una Nuova versione del deployment.

### 2.3 Primo controllo nel tab Iscrizioni corsi

- la riga sotto il titolo indica "iscrizioni dopo l'iscrizione n. X": X è la più recente già presente, fissata come punto di partenza (dalla 0.8 resta quello già fissato). Le iscrizioni precedenti restano fuori dal foglio, come concordato;
- tabella di stato: Errore "nessuno";
- **Prova a secco**: "0 iscrizioni da inviare";
- **Corsi e tab del foglio**: per ognuna delle quattro opzioni compare il tab giusto. "Da smistare" in grassetto indica un'opzione assente da `configurazioni` o scritta in modo diverso: si corregge la colonna B nel foglio e si preme **Rileggi il tab "configurazioni"**. Un avviso sotto la tabella segnala i tab elencati in `configurazioni` che il foglio non contiene.

### 2.4 Iscrizioni di prova

Dal sito, in una finestra anonima, compilare il modulo `/iscrizione-corso/` quattro volte. Usare dati riconoscibili (cognome "Prova1", "Prova2"…) e un codice fiscale valido e già iscritto fra i soci dell'anno, anche il proprio: il modulo controlla il formato e la presenza fra le iscrizioni del modulo dei soci dal 1° settembre. Una quinta prova con un codice valido ma assente fra i soci deve fermarsi con il messaggio e il link al modulo di iscrizione a socio.

| Prova | Cosa compilare | Risultato atteso nella copia |
|---|---|---|
| 1 | Un solo corso: lettura ad alta voce. Cellulare con zero iniziale (es. 0612345678). Ricevuta allegata. Una nota | Una riga in `lettura-ad-alta-voce-2026`: "Rif." in A (`F1617-…`), dati in B–K, cellulare con lo zero, link alla ricevuta in G, TRUE in H–J; le colonne della segreteria restano vuote |
| 2 | Due corsi: tecniche attoriali e messa in scena | Una riga in `tecnica-attoriale-2026` e una in `messa-in-scena-2027`, con lo stesso Rif. I due tab, vuoti, ricevono prima le intestazioni del sito, con Rif. in A |
| 3 | Il gioco del teatro | Una riga in `gioco-del-teatro-2026` |
| 4 | Nome `=1+1`, un corso qualsiasi | Nella cella del nome si legge `=1+1` come testo, il calcolo resta fuori |

Dopo le quattro prove:

1. Tab Iscrizioni corsi: Prova a secco "4 iscrizioni da inviare, 5 righe", con il conto per tab. La tabella delle colonne mostra quante righe hanno ogni colonna piena: Nome, Cognome, Codice Fiscale, Email e i tre consensi 5 su 5; Ricevuta e Note solo le righe delle prove che le avevano.
2. Premere **Invia adesso**. Messaggio atteso: "Inviate 4 iscrizioni." (il conto è per iscrizione, le righe sono 5) Nel giro di qualche secondo le righe compaiono nella copia.
3. Controllare i tab come da tabella.

### 2.5 Doppioni, errori, Da smistare

1. **Reinvio:** premere **Ricomincia dal punto di partenza**, confermare, poi **Invia adesso**. Il messaggio dice di nuovo "Inviate 4 iscrizioni", ma nel foglio le righe restano quelle: lo script riconosce il Rif. e salta le righe già scritte.
2. **Da smistare:** nella copia, in `configurazioni`, cancellare per un momento la riga del gioco del teatro, compilare un'altra iscrizione a quel corso e premere Invia adesso. Compare il tab **Da smistare**, con le intestazioni del sito (Rif. in A), la colonna "Corso" con il testo dell'opzione e la riga della prova. Poi ripristinare la riga in `configurazioni` e cancellare "Da smistare".
3. **Errore di collegamento:** nel tab Configurazione scrivere un codice sbagliato in `VV_CORSI_TOKEN` e premere Salva e verifica. Atteso: "verifica non riuscita: Il foglio ha risposto: codice segreto sbagliato". Compilare un'iscrizione e premere Invia adesso: "Inviate 0 iscrizioni. Errore: …" e riga Errore valorizzata nella tabella di stato; nessuna riga nel foglio. Rimettere il codice giusto e premere Invia adesso: la riga arriva, l'errore sparisce.
4. **Invio automatico:** compilare un'ultima iscrizione e attendere senza premere nulla. Entro 15–20 minuti la riga arriva da sola (il controllo gira ogni 15 minuti con wp-cron, che parte con le visite al sito: su un sito poco visitato può tardare). "Prossimo controllo" nella tabella di stato indica l'orario previsto.

## 3. Passaggio al foglio vero

1. **Pulizia delle prove.** WordPress → Forminator → Iscrizioni (Submissions) → modulo "Iscrizione ai corsi 2026-2027": selezionare le iscrizioni di prova ed eliminarle. Così il punto 3.4 le lascia fuori dal foglio vero.
2. **Foglio vero nel pannello.** Tab Configurazione → *Foglio corsi*: in `VV_CORSI_FOGLIO` l'indirizzo del foglio vero (ID `1TvSREeUfoeAcA1uI3Hx9qpytOVUOMQQc5664DqwxUOw`), poi **Salva e verifica il collegamento**: il messaggio riporta il nome del foglio vero. Script, URL e codice restano gli stessi, nessuna nuova pubblicazione.
3. **Mappa dei corsi.** Nel tab Iscrizioni corsi controllare la tabella *Corsi e tab del foglio*: dopo il salvataggio la mappa viene già dal foglio vero.
4. **Iscrizioni arrivate durante il collaudo.** Eventuali iscrizioni vere compilate mentre il sito puntava alla copia sono finite lì. Per recuperarle: tab Iscrizioni corsi → **Ricomincia dal punto di partenza**, poi **Invia adesso**. Riparte tutto dal punto fissato al primo avvio; le prove, già eliminate, restano fuori.
5. **Controllo.** Una sola iscrizione di prova sul sito, verifica della riga nel tab giusto del foglio vero, poi cancellazione della riga nel foglio e dell'iscrizione in Forminator.
6. **Copia di prova.** Lo script ormai scrive nel foglio vero: la copia si può eliminare.
7. **Costanti vecchie.** Se in `wp-config.php` restano `VV_SHEETS_*` o `VV_CORSI_*` delle versioni 0.7–0.8, toglierle: il tema le ignora.

## 4. Riservatezza e chiusura

1. **Condivisione del foglio.** Pulsante Condividi del foglio vero → Accesso generale → **Con limitazioni**. Lo script scrive con l'account che lo ha pubblicato, quindi il link pubblico in modifica serve più a nulla. Restano gli account nominativi della segreteria.
2. **Colonna Rif.** Si può nascondere (clic destro sull'intestazione della colonna A → Nascondi colonna). Va lasciata nel foglio: è il riferimento contro i doppioni.
3. **Tab `configurazioni`.** Si può proteggere (Dati → Proteggi fogli e intervalli) lasciando la modifica a chi gestisce i corsi.
4. **Informativa privacy.** Riportare nel modulo 1617 e nella pagina privacy il testo di `docs/informativa-fogli-google.md`, dopo la verifica di chi cura la privacy. Da decidere a parte: i dati del genitore per le iscrizioni dei bambini, che il modulo oggi raccoglie solo per l'iscritto.
5. **Avvisi d'errore.** Se il collegamento resta in errore per 24 ore, il sito scrive all'email dell'amministratore (Impostazioni → Generali → Indirizzo email di amministrazione): conviene che sia una casella letta.

## 5. Manutenzione

- **Nuovo corso:** tab nuovo nel foglio, vuoto o con le intestazioni; in `configurazioni` una riga con il nome del tab in A e il testo esatto dell'opzione del modulo in B. Il sito resta com'è. Il tab vuoto riceve le intestazioni del sito alla prima riga; le colonne della segreteria si aggiungono dopo, a piacere.
- **Opzione del modulo riscritta** (un orario, una data): aggiornare il testo in colonna B di `configurazioni`. Il tab Iscrizioni corsi mostra le opzioni dirette a "Da smistare"; le righe già finite lì si spostano a mano.
- **Script modificato** (nuova versione del tema): copiarlo dal tab Script Apps Script, incollarlo così com'è, poi Esegui il deployment → Gestisci deployment → matita → Versione: **Nuova versione** → Esegui il deployment. URL, codice e foglio restano gli stessi.
- **Sospendere il collegamento:** svuotare `VV_CORSI_URL` nel tab Configurazione. Alla ripresa, le iscrizioni arrivate nel frattempo partono al primo controllo.
- **Cambiare modulo:** scegliere il nuovo modulo in `VV_CORSI_FORM`; l'invio riparte da capo per quel modulo.

## Promemoria dei risultati attesi

| Controllo | Esito |
|---|---|
| Tema | Versione 0.9.1, aspetto invariato |
| Verifica collegamento | Nome del foglio giusto per soci e corsi |
| Soci | Stessi numeri di prima nel tab Iscrizioni soci |
| Corsi, primo avvio | Partenza dopo l'iscrizione più recente, 0 da inviare |
| Quattro opzioni | Ognuna nel suo tab, nessuna in grassetto |
| Più corsi | Una riga per corso, stesso Rif. |
| Reinvio | Nessun doppione |
| Corso assente da `configurazioni` | Riga in "Da smistare" con la colonna "Corso" |
| Nome `=1+1` | Testo |
| Codice sbagliato | Errore visibile, nessuna riga |
| Colonne della segreteria | Sempre intatte |
| Codice fiscale assente fra i soci dell'anno | Errore nel modulo, con il link al modulo di iscrizione a socio |
