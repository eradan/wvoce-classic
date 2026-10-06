# Iscrizioni ai corsi 2026-2027 nel foglio "VivaVoce / Iscrizione Corsi"

> Documento storico della 0.8.0. Dalla 0.9.0 la configurazione sta in Strumenti → Esporta iscrizioni, gli script in `inc/apps-script/` e la corrispondenza corso → tab nel tab `configurazioni` del foglio: vedi README, *Versione 0.9.0*, e `docs/attivazione-corsi.md`.

## Stato (6 ottobre 2026)

- Fatti i punti 1–5 dell'*Approccio* sul ramo `claude/great-allen-4cfg7x`: collegamenti multipli in `inc/sheets.php`, righe dei corsi, `docs/apps-script-iscrizione-corsi.gs`, Strumenti → Fogli Google, test, README 0.8.0, `docs/informativa-fogli-google.md`. Lo script è stato provato su un foglio simulato (tab giusto, due corsi, "Da smistare", reinvio senza doppioni, K–N intatte, `=1+1` come testo).
- Collegamento a wvoce.it attivo (sessione del 6 ottobre, ramo `claude/funny-cannon-9pes3w`): l'API risponde con l'utente amministratore. Forminator espone via REST solo le anteprime, quindi i campi del modulo 1617 sono stati letti dalla pagina pubblica `/iscrizione-corso/` (id 943).
- Fatto: `vv_corsi_colonne()` completa (name-1, name-2, text-1, email-1, phone-2, upload-1, consent-1/2/3, textarea-1), `vv_corsi_campo()` = `checkbox-1`, le quattro opzioni del modulo in `vv_corsi_tabs()`. Le opzioni cominciano con "Adulti: Laboratorio di…" e il confronto tollerante le avrebbe mandate tutte in "Da smistare"; "tecniche attoriali" avrebbe comunque mancato la corrispondenza. Se il valore delle caselle arriva come testo unito ("a, b") le opzioni si riconoscono dentro il testo.
- Da fare: installazione dello script sul foglio (o su una copia), costanti `VV_CORSI_URL` / `VV_CORSI_TOKEN` in `wp-config.php`, prova a secco in Strumenti → Fogli Google, verifica dell'elenco qui sotto. Il modulo indica "Bambini (elementari)", non più 3-6 anni: resta da chiarire quali dati del genitore servono per i minori.

## Context

Il modulo Forminator "Iscrizione ai corsi 2026-2027" (id 1617) oggi raccoglie le iscrizioni solo sul sito. La segreteria lavora sul foglio Google `1TvSREeUfoeAcA1uI3Hx9qpytOVUOMQQc5664DqwxUOw`, un tab per corso:

| Tab | Stato |
|---|---|
| `lettura-ad-alta-voce-2026` | modello: A Nome · B Cognome · C Codice Fiscale · D Email · E Cellulare · F Ricevuta · G Regolamento · H Socio · I Privacy · J Note · K–N libere (dovuto, quota associativa, pagamento / come, pagamento / quando) |
| `tecnica-attoriale-2026`, `messa-in-scena-2027`, `gioco-del-teatro-2026` | vuoti |

Obiettivo: ogni iscrizione arriva da sola nel tab del corso scelto, colonne A–J; K–N restano alla segreteria. Lo schema è quello già in produzione per i soci (`inc/sheets.php` + `docs/apps-script-albo-soci.gs`).

Scelte concordate:
- doppioni evitati con la colonna **"Rif."** in O (`F1617-<n. iscrizione>`), nascondibile;
- più corsi nella stessa iscrizione → **una riga per corso**, stesso Rif.;
- corso senza tab → tab **"Da smistare"**, con una colonna "Corso" in più; l'invio prosegue;
- si parte **dalle sole iscrizioni nuove**.

## Passaggio alla sessione nuova

Il collegamento a wvoce.it da questa sessione è bloccato su due fronti: la rete dell'ambiente rifiuta l'host e il controllo dei permessi nega le credenziali incollate in chat.

1. In questa sessione, dopo l'approvazione: ripartire il ramo da `origin/main`, salvare questo piano come `docs/piano-iscrizione-corsi.md` e fare commit e push, così la sessione nuova lo ritrova.
2. Cosa fa l'utente:
   - aggiunge `wvoce.it` agli *Allowed domains* dell'ambiente;
   - revoca la password per le applicazioni condivisa in chat;
   - ne crea una nuova (meglio su un utente dedicato) e la salva come `WP_USER` / `WP_APP_PASSWORD`.
3. Nella sessione nuova, prima di scrivere codice, una sola richiesta autenticata di prova (`/wp-json/wp/v2/users/me`). Se risponde 200, si leggono gli id e le opzioni dei campi del modulo 1617 da un endpoint REST di Forminator, se esposto. Altrimenti restano due strade: lo strumento del punto 4, oppure l'elenco incollato dall'utente. Si evitano tentativi ripetuti per via di fail2ban.

## Approccio

### 1. `inc/sheets.php`: da un collegamento a più collegamenti

Il ciclo esistente si generalizza invece di essere copiato: le funzioni ricevono l'id del collegamento (`soci` o `corsi`).

- `vv_sheets_collegamenti()` (filtrabile): per ciascuno modulo, costanti di `wp-config.php`, colonne, costruzione delle righe, nome dello stato.
  - `soci`: `VV_SHEETS_URL` / `VV_SHEETS_TOKEN` / `VV_SHEETS_FORM` / `VV_SHEETS_DAL`, opzioni `vv_sheets_ultimo` e `vv_sheets_stato` **invariate** (nessuna migrazione, nessun reinvio).
  - `corsi`: `VV_CORSI_URL` / `VV_CORSI_TOKEN`, facoltativa `VV_CORSI_FORM` (1617), opzioni `vv_sheets_corsi_ultimo` e `vv_sheets_corsi_stato`.
- Da parametrizzare: `vv_sheets_config()`, `vv_sheets_ready()`, `vv_sheets_da_inviare()`, `vv_sheets_post()`, `vv_sheets_sync()`, `vv_sheets_prova()`.
- Restano come sono: `vv_sheets_valore()` (consensi → TRUE, file → URL, date, caselle), redirect di Apps Script letto con GET, avanzamento solo sulle righe confermate, email dopo 24 ore di errori.
- **Partenza dalle nuove:** al primo giro con `corsi` configurato, se `vv_sheets_corsi_ultimo` manca, si salva come punto di partenza l'id più alto già presente (opzione `vv_sheets_corsi_inizio`). "Ricomincia" torna a quel punto, invece che a zero.
- Il cron `vv_sheets_sync` resta uno solo e fa un giro per ogni collegamento configurato. Un errore dei corsi blocca solo i corsi.

### 2. Righe dei corsi: `vv_corsi_righe( $entry )`

- Colonne (`vv_corsi_colonne()`, filtro): Nome, Cognome, Codice Fiscale, Email, Cellulare, Ricevuta, Regolamento, Socio, Privacy, Note → id dei campi del modulo 1617. Oggi è noto solo `text-1` (codice fiscale, da `inc/forminator.php`); gli altri id si leggono con lo strumento del punto 4 prima di attivare.
- Campo del corso (`vv_corsi_campo()`): scelta singola o multipla. Per ogni corso scelto una riga, con `_tab` dato da `vv_corsi_tab( $corso )`:
  - tabella valore del modulo → nome del tab (filtro `vv_corsi_tabs`), più un confronto tollerante con il nome del tab (minuscole, trattini);
  - senza corrispondenza → `Da smistare`, e il valore va nella chiave "Corso".
- Ogni riga porta "Rif." e "Corso". In un tab normale "Corso" viene ignorata, perché lo script scrive solo nelle colonne che trova.
- Blocchi di 20 iscrizioni come per i soci. La conferma usa i Rif.: un'iscrizione con due corsi conta come confermata quando lo script restituisce il suo Rif.

### 3. Apps Script: `docs/apps-script-iscrizione-corsi.gs` (nuovo)

Stessa ossatura dello script dei soci: `doPost`, token nelle proprietà dello script, `LockService`, `creaToken`, `testo_()` contro le formule, scrittura per intestazione, `setNumberFormat('@')`. Cambia la scelta del tab:

- **Tab modello:** il primo tab con la riga 1 compilata, oppure quello indicato in `TAB_MODELLO` (oggi `lettura-ad-alta-voce-2026`).
- **Tab del corso esistente ma vuoto** (i tre di oggi): riceve le intestazioni A–N del modello prima della prima riga.
- **Tab inesistente:** solo `Da smistare`, creato copiando le intestazioni del modello e aggiungendo "Corso". Ogni altro nome sconosciuto viene ricondotto lì, così il sito non crea mai tab a piacere.
- **"Rif."** si aggiunge alla prima colonna libera dopo le intestazioni (O). Il controllo dei doppioni vale per tab.
- Righe scritte solo nelle colonne con intestazione corrispondente. K–N restano intatte, anche se la segreteria le compila prima che arrivino altre righe: si scrive sempre in fondo, dopo l'ultima riga usata.

### 4. Strumenti → Foglio soci diventa **Strumenti → Fogli Google**

Una sezione per collegamento, con stato, prova a secco, "Invia adesso" e "Ricomincia" come oggi. Per i corsi la prova a secco mostra anche:
- **i campi del modulo 1617** (id, etichetta, tipo, opzioni, da `Forminator_API::get_form_fields()`), senza dati personali, per completare la mappa del punto 2;
- quante righe andrebbero in ciascun tab, `Da smistare` compreso, e quante hanno ogni colonna piena.

### 5. Test, documentazione, privacy

- `tests/run.php`: righe dei corsi (corso singolo, due corsi → due righe con lo stesso Rif., corso sconosciuto → `Da smistare` con "Corso"), confronto tollerante dei nomi, partenza dalle nuove, invarianza delle righe dei soci.
- README: voce 0.8.0 e sezione *Foglio dei corsi* (installazione, costanti, come aggiungere un corso: nuovo tab nel foglio + voce in `vv_corsi_tabs` se il nome differisce).
- `docs/informativa-albo-soci.md` → `docs/informativa-fogli-google.md`, con i dati dei corsi.
  - Da verificare: se il modulo riguarda minori (Gioco del teatro 3-6 anni), quali dati del genitore passano.
- `style.css`: Version 0.8.0.

## File toccati

- `inc/sheets.php` (rifattorizzato, logica dei corsi; funzioni `vv_sheets_valore()` e `vv_sheets_data()` riusate)
- `docs/apps-script-iscrizione-corsi.gs` (nuovo); `docs/apps-script-albo-soci.gs` invariato
- `tests/run.php`, `README.md`, `docs/informativa-*.md`, `style.css`
- Ramo: `claude/bold-pascal-s1my9f` ripartito da `origin/main`, perché la PR precedente è già unita

## Installazione (dopo il merge)

1. Foglio corsi → Estensioni → Apps Script → incollare lo script → `creaToken` → deployment come App web ("Esegui come: me", "Chi ha accesso: chiunque").
2. `wp-config.php`: `VV_CORSI_URL`, `VV_CORSI_TOKEN`.
3. Strumenti → Fogli Google: leggere i campi del modulo 1617, confermare la mappa (eventuale piccolo commit con gli id), controllare la prova a secco.
4. **Togliere il permesso di modifica a "chiunque abbia il link"**: lo script scrive con l'account di chi lo pubblica, quindi il foglio può tornare riservato.
5. Nome del tab dei bambini: `gioco-del-teatro-2026` (confermato il 6 ottobre; nel foglio va rinominato il vecchio `gioco-del-teatro-2206`).

## Verifica

- `php tests/run.php` e `php -l` su tutti i file: CI verde su PHP 7.4 e 8.3.
- Su una **copia del foglio**, con un deployment di prova:
  - un'iscrizione di prova per corso: riga nel tab giusto, A–J compilate, Rif. in O, K–N vuote;
  - un'iscrizione con due corsi: due righe;
  - un corso fittizio: tab `Da smistare` con "Corso";
  - "Invia adesso" ripetuto: nessun doppione;
  - un nome `=1+1`: resta testo.
- Foglio soci: la prova a secco mostra gli stessi numeri di prima del cambiamento.
