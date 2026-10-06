# Tema child VivaVoce

Tema child di **Sydney 2.71** per wvoce.it, aspetto "classico" vicino al sito originale. Le note di versione vengono prima; da *Messa in produzione* in poi la guida d'uso.

## Versione 0.8.0: iscrizioni ai corsi nel foglio Google

- **Iscrizioni ai corsi nel foglio "VivaVoce / Iscrizione Corsi"** (`inc/sheets.php`, `docs/apps-script-iscrizione-corsi.gs`): ogni iscrizione del modulo "Iscrizione ai corsi 2026-2027" (n. 1617) arriva nel tab del corso scelto, una riga per corso. Vedi *Foglio dei corsi*.
- **Un solo meccanismo per più fogli.** Le funzioni di `inc/sheets.php` ricevono l'id del collegamento (`soci`, `corsi`; elenco in `vv_sheets_collegamenti()`, filtrabile). Il foglio dei soci funziona come prima: stesse costanti, stesse opzioni (`vv_sheets_ultimo`, `vv_sheets_stato`), nessun reinvio.
- **Strumenti → Fogli Google** (prima *Foglio soci*): una sezione per collegamento, con stato, prova a secco, "Invia adesso" e "Ricomincia". Per i corsi mostra anche i campi del modulo e il tab in cui finisce ogni opzione.
- **Informativa privacy** estesa ai corsi: `docs/informativa-fogli-google.md` (prima `informativa-albo-soci.md`).

### Foglio dei corsi

Le iscrizioni del modulo n. 1617 arrivano nel foglio "VivaVoce / Iscrizione Corsi", un tab per corso (`lettura-ad-alta-voce-2026`, `tecnica-attoriale-2026`, `messa-in-scena-2027`, `gioco-del-teatro-2206`). Lo script scrive Nome, Cognome, Codice Fiscale, Email, Cellulare, Ricevuta, Regolamento, Socio, Privacy e Note (A–J); dovuto, quota associativa e pagamento (K–N) restano alla segreteria e lo script li lascia intatti. In O compare "Rif." (`F1617-` + numero dell'iscrizione), che evita i doppioni e si può nascondere.

- **Più corsi in un'iscrizione:** una riga in ciascun tab, con lo stesso Rif.
- **Tab del corso.** Il valore scelto nel modulo trova il suo tab anche scritto in altro modo: "Lettura ad alta voce" → `lettura-ad-alta-voce-2026` (minuscole, senza accenti, trattini al posto degli spazi, anno finale facoltativo; un anno diverso da quello del tab non combacia). I casi che il confronto non risolve vanno in `vv_corsi_tabs()` (valore del modulo => nome del tab). Un tab esistente ma vuoto riceve le intestazioni del tab modello (`TAB_MODELLO` nello script).
- **Corso senza tab:** la riga va in "Da smistare", creato dallo script la prima volta con le intestazioni del modello e una colonna "Corso". L'invio prosegue; lo script non crea altri tab.
- **Si parte dalle iscrizioni nuove.** Alla prima apertura di Strumenti → Fogli Google (o al primo giro di wp-cron) con le costanti in `wp-config.php`, il sito fissa come punto di partenza l'iscrizione più recente già presente (opzione `vv_sheets_corsi_inizio`). "Ricomincia" torna a quel punto. Opzioni: `vv_sheets_corsi_ultimo`, `vv_sheets_corsi_stato`.
- **Corrispondenza colonne → campi:** `vv_corsi_colonne()`; campo del corso: `vv_corsi_campo()`; nomi dei tab: `vv_corsi_tab_nomi()`. Tutti filtrabili. Oggi è noto solo il codice fiscale (`text-1`): gli altri id si leggono in Strumenti → Fogli Google → *Campi del modulo n. 1617*. Finché `vv_corsi_campo()` è vuoto il collegamento resta fermo; le iscrizioni arrivate nel frattempo partono appena è pronto.
- **Installazione.**
  1. Nel foglio dei corsi: Estensioni → Apps Script, incollare `docs/apps-script-iscrizione-corsi.gs`, eseguire `creaToken`, poi Esegui il deployment → Nuovo deployment → App web, "Esegui come: me", "Chi ha accesso: chiunque".
  2. In `wp-config.php`:

     ```php
     define( 'VV_CORSI_URL', 'https://script.google.com/macros/s/…/exec' );
     define( 'VV_CORSI_TOKEN', '…' );
     // facoltativi: define( 'VV_CORSI_FORM', 1617 ); define( 'VV_CORSI_DAL', '2026-09-01' );
     ```

  3. Strumenti → Fogli Google: leggere i campi del modulo, completare la corrispondenza (un piccolo commit con gli id), controllare la prova a secco. Conviene provare prima su una copia del foglio.
  4. Togliere al foglio il permesso "Chiunque abbia il link": lo script scrive con l'account di chi lo pubblica.
- **Nuovo corso:** un tab nuovo nel foglio, il suo nome in `vv_corsi_tab_nomi()` e, se il valore del modulo è molto diverso, una voce in `vv_corsi_tabs()`.

## Versione 0.7.4: manutenzione, test e privacy

- **Foglio dei soci al riparo dalle formule.** Un valore del modulo che comincia con `=` arriva nel foglio preceduto da un apostrofo (che il foglio non mostra) e resta testo: prima un "nome" come `=IMPORTXML(…)` poteva diventare una formula. È nello script Apps Script (`docs/apps-script-albo-soci.gs`, funzione `testo_`): **va incollato di nuovo nell'editor e pubblicato** con Esegui il deployment → Gestisci deployment → modifica → Nuova versione (l'URL resta lo stesso).
- **Informativa privacy** per il trasferimento delle domande nel foglio Google: bozza e controlli in `docs/informativa-albo-soci.md` (dalla 0.8.0 `docs/informativa-fogli-google.md`), da far verificare e poi riportare nel modulo "Iscrizione Socio" e nella pagina privacy.
- **Nessun tetto agli elenchi.** Le *Pagine elenco* mostrano tutti gli articoli della categoria (prima i primi 200) e i collegamenti pagina ↔ categoria valgono per tutte le pagine (prima le prime 50).
- **Filtri che si sommano.** In `[vv_cards]` "In evidenza" e le date dell'evento ora restano entrambi nella query al database (`vv_add_meta_query()` in `inc/cards.php`); prima uno dei due si perdeva e il conto si rifaceva in PHP su più articoli.
- **Robustezza.** Una data dell'evento salvata in un formato diverso da AAAA-MM-GG conta come assente, invece di fermare la pagina con un errore. I dati strutturati dei breadcrumb hanno `<` `>` `&` codificati, così un titolo resta dentro il suo `<script>`.
- **Plugin di elenchi e slider tolti dal codice:** `featured-post-creative`, `wp-responsive-recent-post-slider` e `post-carousel` sono stati rimossi dal sito. La spunta "In evidenza" è sempre nel riquadro "VivaVoce: date e luogo" e usa lo stesso campo, quindi le spunte già messe restano.
- **Via l'aspetto "ritaglio di carta".** `assets/css-ritaglio/`, i font Baloo 2 e Nunito e `divisore.svg` erano usati solo da lì e sono nella storia git (commit `cc6b27b`). La procedura per tornarci non funzionava più: i fogli attuali usano token che quella versione non ha.
- **Strumenti di sviluppo:** test, regole di scrittura, zip e controlli automatici su GitHub (vedi *Sviluppo*).

## Versione 0.7.3

- **Linguetta "Cookie" sul bordo sinistro**, in verticale, come il badge di reCAPTCHA su quello destro (`plugins.css`). Prima stava in basso a 16px dal bordo e su telefono copriva la faccina di sinistra della fascia turchese; ora sale con la fascia (`--vv-sotto`) come il badge e il "torna su". Posizione e larghezza le scrive il plugin nello stile inline: nel tema ci sono gli `!important`.
- **Grigi del tema chiaro un poco azzurri** (`tokens.css`): card #f1f5f9, riquadro e bordi #d7e0e9, testo secondario #607189 (ora 4,5:1 anche sulla card, prima 4,2:1), date #55657a, testo sul riquadro #384a5e, titoli di sezione #3a4a5c, pulsanti "Copia" e testo sul giallo #2c3a4a. Anche i colori globali 5, 7 e 8 di Sydney, che nel Customizer hanno ancora i grigi neutri, ora vengono dai ruoli in ogni tema (`html:root`). Il tema scuro non cambia.

## Versione 0.7.2

- Foglio dei soci: messaggi d'errore chiari quando Google chiede di accedere (HTTP 401/403: l'app web va pubblicata con "Chi ha accesso: Chiunque" e l'URL deve finire con `/exec`) o quando l'URL è sbagliato (404).

## Versione 0.7.1: moduli di iscrizione, foglio dei soci, avviso sui cookie

- **Moduli Forminator nello stile del sito** (in fondo a `assets/css/plugins.css`, valgono per tutti i moduli, nel tema chiaro e in quello scuro): etichette 14px in inchiostro, campi sul fondo delle card con bordo grigio, testo d'esempio a 5,5:1 (era 3:1), scelte e consensi leggibili anche sul blu, link turchesi, titoli di sezione, pulsante d'invio giallo come gli altri del sito (prima testo #333 su blu, 2,7:1), "Scegli il file" come i pulsanti "Copia". Forminator scrive i suoi colori modulo per modulo con un id nel selettore: le regole del tema usano `!important`.
- **Forminator in italiano** dove il plugin non ha la traduzione: "Scegli il file", "Nessun file scelto", messaggi d'obbligo (`vv_plugin_strings()` in `inc/setup.php`).
- **Codice fiscale controllato davvero** (`inc/forminator.php`): formato, lettere dell'omocodia e carattere di controllo; se non torna, l'errore compare accanto al campo e il modulo non parte. Vale per il modulo "Iscrizione ai corsi 2026-2027" (n. 1617); per aggiungerne altri: `vv_forminator_cf_fields()` (id del modulo => id del campo). Nel campo le lettere si vedono maiuscole. Il modulo "Iscrizione Socio" non è incluso perché per le aziende il codice fiscale è di 11 cifre.
- **Avviso sui cookie** con i colori e i pulsanti del sito (`plugins.css`): fascia bianca (blu nel tema scuro) con filetto giallo, "Accetta" giallo, "Rifiuta" grande uguale con il bordo (il Garante chiede che rifiutare sia facile come accettare), "Preferenze" come link, linguetta gialla per riaprire le scelte, finestra delle preferenze coi token. I testi si scrivono nelle impostazioni di GDPR Cookie Consent.
- **Domande dei soci nel foglio Google** (`inc/sheets.php`, `docs/apps-script-albo-soci.gs`): vedi *Foglio dei soci*.

### Foglio dei soci

Le iscrizioni del modulo "Iscrizione Socio" (n. 274) dal 1° settembre 2026 arrivano da sole nel foglio "VivaVoce / Albo Soci", nel tab dell'anno sociale (da settembre ad agosto: "DomandeAmmissione 2026-2027"). Se il tab non c'è, lo script lo crea con le intestazioni del tab DomandeAmmissione più recente. Ogni valore va nella colonna con la stessa intestazione: le colonne si possono spostare o aggiungere. La colonna "Assicurazione" resta vuota: il "sì" lo mette chi decide. In fondo c'è la colonna "Rif." (`F274-` + numero dell'iscrizione) che evita i doppioni.

- **Come funziona.** Ogni 15 minuti (wp-cron, `vv_sheets_sync`) il sito prende con `Forminator_API::get_entries()` le iscrizioni non ancora confermate dal foglio, niente spam né bozze, e le manda a blocchi di 20 all'app web dello script. Avanza (opzione `vv_sheets_ultimo`) solo sulle righe che il foglio conferma; se Google non risponde riprova al giro dopo, e dopo 24 ore di errori manda un'email all'amministratore. Corrispondenza colonne → campi: `vv_sheets_colonne()`.
- **Strumenti → Fogli Google** (fino alla 0.7.4 *Foglio soci*): stato, prova a secco (quante iscrizioni partirebbero, verso quali tab, quante hanno ogni colonna piena, senza mostrare i dati), "Invia adesso", "Ricomincia dalla data iniziale" (non crea doppioni).
- **Installazione.** Nel foglio: Estensioni → Apps Script, incollare `docs/apps-script-albo-soci.gs`, eseguire `creaToken` (il codice segreto compare nel registro), poi Esegui il deployment → Nuovo deployment → App web, "Esegui come: me", "Chi ha accesso: chiunque". In `wp-config.php`:

  ```php
  define( 'VV_SHEETS_URL', 'https://script.google.com/macros/s/…/exec' );
  define( 'VV_SHEETS_TOKEN', '…' );
  // facoltativi: define( 'VV_SHEETS_FORM', 274 ); define( 'VV_SHEETS_DAL', '2026-09-01' );
  ```

  Senza le due costanti il collegamento è spento. Conviene provarlo prima su una copia del foglio. Il trasferimento va dichiarato nell'informativa: vedi `docs/informativa-fogli-google.md`.
- **Frequenza reale.** wp-cron parte solo quando qualcuno visita il sito: con poche visite i 15 minuti si allungano. Per un ritmo regolare si può far chiamare `wp-cron.php` da un cron dell'hosting e mettere `define( 'DISABLE_WP_CRON', true );` in `wp-config.php`.
- **Se il deployment dà "Non disponi dell'autorizzazione necessaria"**: di solito il browser ha più account Google aperti e l'editor di Apps Script usa quello sbagliato (si risolve in una finestra anonima con un solo account), oppure il permesso sul foglio è appena cambiato (chiudere l'editor e riaprirlo dal foglio). In alternativa lo script si crea nel proprio Drive (script.google.com → Nuovo progetto) mettendo l'ID del foglio in `ID_FOGLIO`.
- **Tab "Assicurazione".** Deve leggere il tab dell'anno in corso, per esempio `=FILTER(CHOOSECOLS('DomandeAmmissione 2026-2027'!D2:O; 1;2;3;4;6;7;8;9;10;11;12); 'DomandeAmmissione 2026-2027'!A2:A="sì")` (Nome…Data di nascita, senza Telefono).

## Versione 0.7: palette centralizzata, tema scuro e switcher

- **Palette su due livelli** in `assets/css/tokens.css`: *marchio* (giallo, turchese, rosso, inchiostro, bianco, perla), uguale in ogni tema, e *ruoli* (fondo, testo, link, bordi…), gli unici che un tema ridefinisce. Prima alcuni token avevano il nome della tinta e due usi diversi (`--vv-bianco` era sia il fondo pagina sia il testo sulle foto scure): ora ogni uso ha il suo ruolo. Nessun colore scritto a mano negli altri fogli né in `style.css`. A schermo il tema chiaro non cambia.
- **Tema scuro "Inchiostro"**: il blu notte del testo diventa il fondo (#0c1a33, card #14264a, footer #1b3059), testo #eaf0f8, link turchese chiaro #4fd6e3; tutto il testo ad almeno 4,5:1. Restano uguali header giallo, sottomenu e menu mobile turchesi, fascia turchese con le faccine, pulsanti gialli, badge, "torna su". È un solo blocco, `:root[data-vv-tema="scuro"]` in `tokens.css`, che ridefinisce anche i colori globali di Sydney 4, 5, 7, 8 e 9 (titoli, testo grigio, citazioni, bordi, fondo). Le parti di Sydney con colori chiari scritti a mano (campi dei moduli, commenti, tabelle, schermata di caricamento) sono in `assets/css/scuro.css`. L'editor a blocchi resta chiaro.
- **Switcher** nell'header, accanto alla lente (desktop e telefono): l'icona mostra il tema in uso (sole, luna, mezzo cerchio per "auto") e un clic passa al successivo, chiaro → scuro → auto. "Auto" segue l'impostazione del dispositivo, anche se cambia a pagina aperta. La scelta resta nel browser del visitatore (`localStorage`, chiave `vv-tema`). Uno script in testa alla pagina (`vv_tema_script()` in `functions.php`) mette `data-vv-tema` su `<html>` prima che la pagina si disegni, quindi niente lampo bianco; lì c'è anche il tema per chi non ha mai scelto (`$predefinito`, ora `'chiaro'`). Il pulsante lo crea `vv.js`; senza JavaScript il sito resta chiaro.
- **Ovale dietro testa e mano dell'hero** (`.vv-hero__tela::before` in `home.css`): nel tema scuro è perla e stacca dal blu la parte nera della testa (sul blu sarebbe 1,1:1); le punte dei capelli ne escono. Nel tema chiaro c'è ma ha il colore del fondo. Colore in `--vv-fondo-disco` (`tokens.css`), forma e posizione nelle variabili `--vv-disco-*`, in % della tela dell'illustrazione. Con un'immagine dell'hero diversa dall'illustrazione originale l'ovale non c'è.

## Versione 0.6: contenuti riclassificati, eventi dalle loro date

Va insieme alla riclassificazione dei contenuti (categorie, tag, date degli eventi), che si fa dal pannello o dall'API e non è nel tema.

- **Categorie.** *Eventi* è la madre di *Corsi e laboratori* (`laboratori`), *Spettacoli e incontri* (`spettacoli`) e *Partecipazioni e collaborazioni* (`partecipazioni`, attività di altri a cui VivaVoce partecipa o che sostiene). Poi *Progetti*, *Dicono di noi* (slug `rassegna-stampa`, che il tema usa per le card dei ritagli), *Associazione* e *Soci*. Un articolo ha una sola categoria, la più specifica. Le pagine *Pagina elenco* con categoria `eventi` mostrano anche le figlie.
- **Eventi dalle loro date.** `[vv_cards periodo="futuro"]` mostra gli eventi non ancora finiti, dal più vicino; `periodo="dal-3-mesi"` anche quelli finiti da meno di 3 mesi, dopo i prossimi (`vv_posts_by_period()` in `inc/cards.php`). Contano i campi "date e luogo" dell'articolo (`vv_data`, `vv_data_fine`), non la data di pubblicazione: un evento senza date non compare. In home, se non c'è niente, compaiono gli articoli "In evidenza"; nella pagina "In programma" una frase.
- **Caroselli che scorrono da soli.** `autoplay="si"` su un `[vv_cards layout="carosello"]`: una card ogni 5 secondi, poi si riparte. Si ferma con il mouse sopra, il focus dentro, il dito sul binario, fuori schermo e a scheda nascosta; il pulsante accanto al contatore lo ferma del tutto, anche su telefono. Con "riduci movimento" non parte (`assets/js/vv.js`).
- **Pagine elenco** (0.6.2): in cima almeno 9 card grandi, prima gli eventi in programma (dal più vicino) e, se non bastano, i più recenti già passati («In programma e ultimi appuntamenti»); sotto il resto, anno per anno (filtro `vv_elenco_grandi` per cambiare il 9). Se nessun articolo della categoria ha le date dell'evento, come i progetti, è un'unica griglia di schede con il riassunto intero, senza anni, date ed etichette. Anche la pagina *In programma* è una Pagina elenco sugli eventi.
- **Pagine a sezioni con il testo attorno alle foto** (0.6.6, in fondo a `assets/css/pages.css`): un Gruppo con classe `vv-sezione` che comincia con un'immagine allineata a sinistra o a destra; l'immagine è larga il 45% (al massimo 560px), il testo le scorre attorno e la sezione dopo comincia sotto. Su telefono (sotto i 600px) l'immagine va sopra il testo, a tutta larghezza. Una pagina che contiene sezioni usa tutta la larghezza del contenitore invece degli 830px della colonna di lettura (`:has()`; nei browser che non lo conoscono resta a 830px). La usa *Partecipa*.
- **Testata più bassa ed elegante** (0.6.5, `assets/css/header.css`):
  - **Altezza:** su desktop 88px in cima alla pagina e 64px dopo lo scorrimento, con un'ombra leggera. La classe `vv-scrolled` la mette `vv.js`, con un'isteresi fra 80 e 10px; un margine compensa i 24px, così il contenuto non salta. La testata mobile (fino a 1024px) è alta 64px e scorre via con la pagina.
  - **Voci:** in maiuscoletto (13px, Poppins 600, spaziatura 0,08em), con le freccine piccole e sempre visibili. La linea rossa sotto segna la voce sotto il mouse e la **sezione corrente**: la classe `vv-current` va sulle voci di menu che compaiono nel percorso in alto (`vv_menu_current_section()` in `inc/breadcrumbs.php`), quindi un corso accende "Cosa facciamo".
  - **Sottomenu:** turchesi come nel Customizer, ma con il carattere normale, più aria, larghi quanto la voce più lunga e con un'ombra leggera.
  - **Pannello mobile:** voci principali in maiuscoletto.
- **Riquadri delle card sempre visibili** (0.6.4): con l'editor classico "Immagine in evidenza" e "Riassunto" non si possono più nascondere da "Impostazioni schermata", per articoli e pagine (`vv_always_show_card_boxes()` in `inc/meta.php`). Nel riquadro VivaVoce delle pagine una nota spiega che sono quelli della card nella pagina madre.
- **Pagina hub con le card dove si vuole** (0.6.3): se il testo contiene `[vv_sottopagine]` le card delle pagine figlie stanno lì e non vengono ripetute in fondo. *Cosa facciamo* lo usa per avere introduzione, card delle tre sezioni (riassunto e immagine in evidenza delle pagine Eventi, Corsi e laboratori, Progetti; ordine dagli attributi di pagina) e sotto i prossimi 3 appuntamenti. Le *Pagine elenco* non mostrano più l'immagine in evidenza in testa: serve alla loro card nell'hub. Nel testo, i titoli di sezione e i paragrafi centrati sono larghi quanto le griglie di card.
- **`[vv_dati_fiscali mostra="iban"]`** (o `mostra="cf"`): un solo dato, per la pagina *Fai una donazione*; nel testo di una pagina ha lo stacco dal paragrafo che segue.
- **Frecce dei caroselli anche su telefono** (0.6.1): sotto il carosello, tonde, nella riga con pausa e contatore (‹ ❚❚ 2 di 8 ›); su schermo largo restano ai lati delle card. Al passaggio del mouse non perdono più il fondo (lo toglieva lo stile dei pulsanti di Sydney).
- **Colonna degli articoli.** Sotto "In breve", dove il link della categoria porta alla pagina della sezione:
  - "Altre edizioni": gli articoli con lo stesso tag di percorso (Il gioco del teatro, Corre Voce, ALICE…);
  - "Ne hanno parlato": i ritagli con lo stesso tag;
  - "Da non perdere": i prossimi 3 eventi; se non ce ne sono, gli "In evidenza".

  Su un ritaglio c'è invece "Di cosa parla". Sono tag di percorso tutti tranne pubblico e linguaggio (`bambini`, `ragazzi`, `adulti`, `teatro`, `lettura-ad-alta-voce`, `narrazione`, `scrittura`, `musica`, `multimedia`; filtro `vv_generic_tags`).
- **Percorso in alto.** Le sottocategorie usano la pagina della madre: un articolo in *Spettacoli e incontri* sta sotto Cosa facciamo / Eventi. Eventi, Corsi e laboratori e Progetti sono *Pagina elenco* con la loro categoria: i progetti sono articoli, mostrati a card con il riassunto.
- **Redirect.** Gli indirizzi spariti con la riclassificazione (categorie eliminate, vecchie sottopagine di Formazione, pagine diventate articoli, articoli uniti) portano con un 301 a quelli nuovi, solo se altrimenti darebbero "pagina non trovata" (`inc/redirects.php`, filtro `vv_redirects`).

## Versione 0.5: immagine in testa alle pagine, archivi a pannelli, leggibilità

- **Pagine con immagine in testa.** Titolo, poi l'immagine in evidenza della pagina (16:9, 4:3 su telefono, ritagliata al centro), poi il testo, come le pagine di Spazio Mumu. È l'opzione di Sydney "Enable featured images on all pages", accesa dal tema (`vv_page_featured_image` in `inc/setup.php`) tranne che in home. Senza immagine in evidenza la pagina resta com'era. Negli articoli la locandina resta intera ma non supera `min(80vh, 720px)` di altezza.
- **Pagina elenco tutta a pannelli.** "In programma" a card grandi (3 per riga) e l'archivio, anno per anno, a pannelli piccoli con foto, titolo e data (4 per riga, 2 su tablet e telefono), come i corsi del Teatro Nuovo.
- **Leggibilità.** I colori del Customizer davano testo giallo sul bianco (h2, link al passaggio del mouse: 1,4:1), turchese sul bianco (link, titoli delle card, breadcrumb: 2,6:1), bianco sul turchese (sottomenu, fascia copyright) e grigio sul grigio nel footer. Ora tutto il testo è ad almeno 4,5:1: link in turchese scuro sottolineati, testo inchiostro su turchese e giallo, badge più scuri. Il turchese e il giallo pieni restano per fondi e filetti. Caratteri e spazi seguono una scala sola (vedi *Tipografia, spazi e colori*).
- **Home e footer.** Pulsanti dell'hero sopra la parte bassa dell'illustrazione (a 4/5 dell'altezza su desktop), illustrazione un po' più grande. Se l'immagine dell'hero è l'illustrazione originale (`vivavoce-logo-pearl`), il tema la mostra a livelli separati (`assets/img/hero/`: testa, mano, tre trattini) e la anima in CSS una volta sola, all'apertura della home: la testa annuisce come se parlasse (due frasi e una pausa, 6 s), la mano, ruotata di 3° per staccare il pollice dalla bocca, gesticola con un suo ritmo (7,2 s) e i trattini della voce pulsano mentre parla; poi tutto si ferma (`vv_hero_animata()` in `inc/home.php`, animazioni in `home.css`). Con un'altra immagine nel Customizer resta l'immagine sola; con "riduci movimento" attivo i livelli restano fermi. Al passaggio del mouse i pulsanti diventano turchese scuro con bordo giallo, come le voci del sottomenu (prima nero). Nella fascia turchese sotto il footer: "© anno" e la scritta VIVA VOCE del logo, con due faccine su cerchi gialli ai lati che si guardano (`assets/img/`). La prima volta che si arriva in fondo alla pagina le faccine corrono al centro, si scontrano e rimbalzano al loro posto saltando in modo diverso: solo CSS (`scroll-state()` e un interruttore `@property`, in fondo a `footer.css`), una volta per pagina. Nei browser senza `scroll-state()`, sulle pagine che non scorrono e con "riduci movimento" attivo restano ferme. Il testo "Credits" e le icone social del Customizer lì non si usano più (`vv_footer_credits` in `inc/setup.php`). Colonne del footer uguali (320px) e centrate nella pagina da 1200px in su; nei dati fiscali l'etichetta sta sopra il valore, i valori hanno la stessa larghezza e i pulsanti "Copia" sono in colonna.
- **"Torna su" e reCAPTCHA.** In basso a destra il badge di reCAPTCHA e, sopra, il pulsante "torna su": un disco giallo con la mano rossa del logo, ruotata di 9° perché le dita puntino dritte in alto (`assets/img/mano-su.webp`). Quando la fascia turchese entra in vista salgono con lei, così non coprono più la faccina di destra (`--vv-sotto`, misurato da `vv.js`); col banner dei cookie aperto restano sopra il banner. Il pulsante di Sydney ha ora un nome per i lettori di schermo e si usa da tastiera.
- **12 articoli per pagina negli archivi** (categorie, tag, blog), invece dei 10 di *Impostazioni → Lettura*: con 1, 2 o 3 colonne le righe sono sempre piene (`vv_archive_posts_per_page()` in `inc/archive.php`).
- **Poppins in tutto il sito.** Il carattere delle locandine vale per testi, titoli, menu, pulsanti, moduli, banner dei cookie e testo incollato con un altro font (regola in cima a `base.css`); restano fuori solo i font delle icone. È ospitato nel tema (`assets/fonts/poppins-*.woff2`: 400, 500, 600, 700, corsivi 400 e 700, latin e latin-ext; licenza OFL), quindi niente richieste a Google; normale e grassetto hanno il preload (`vv_preload_fonts()` in `functions.php`).
- **Colonna laterale nelle pagine.** Le pagine con il modello predefinito hanno a destra (sotto il testo su telefono) la colonna degli articoli: "In questa sezione", con la pagina madre e le altre pagine dello stesso ramo (quella corrente evidenziata, le sue sottopagine rientrate), e "Da non perdere" (3 articoli "In evidenza"). Tutto dall'albero delle pagine: le sottopagine con "Non mostrare fra le card della pagina madre" restano fuori anche qui. Niente colonna in home, Pagina hub, Pagina elenco, pagine privacy/cookie, pagine fatte con SiteOrigin e pagine con `[vv_cards]`, `[vv_elenco]` o `[vv_sottopagine]` nel testo (`inc/page.php`; filtro `vv_page_has_aside` per i casi particolari).
- **Niente più `VV_PREVIEW`.** Il tema vale per tutti i visitatori appena attivato. Tolto anche il preload dei font Baloo 2 e Nunito, che il tema classico non usa (se si torna al "ritaglio di carta" va rimesso in `functions.php`).

## Versione 0.4: aspetto "classico"

Dalla 0.4.0 il tema ha un aspetto il più vicino possibile al sito originale su Sydney: font di sistema, colori del Customizer (header giallo, sottomenu e pannello mobile turchesi, hover rosso), titoli di sezione in maiuscolo centrati con barretta gialla, card nello stile dei vecchi plugin di elenchi, footer grigio come la vecchia sidebar dei contatti, fascia copyright turchese. PHP, modelli e contenuti sono gli stessi della 0.3.x: cambia solo il CSS.

Le parti che nel sito originale non esistevano (hero, card, breadcrumb, scheda "In breve", colonne del footer, carosello) sono vestite nello stile originale. Poche correzioni sono rimaste di proposito: testo scuro sui pulsanti gialli di invio (prima bianco su giallo), header giallo anche dopo lo scroll (prima diventava blu scuro), link del testo turchesi, testo incollato da Word con il font del sito, gallerie a griglia, breadcrumb cliccabili.

**Due temi installati.** In produzione ci sono "VivaVoce" (cartella `wvoce`) e "VivaVoce Classic" (cartella `wvoce-classic`). WordPress salva le impostazioni del Customizer tema per tema: un tema appena attivato parte vuoto. Dalla 0.4.1 il tema copia da solo le impostazioni da un altro tema VivaVoce già configurato (quello con il logo), altrimenti da Sydney. I visitatori le vedono subito; la copia viene salvata alla prima visita del pannello di amministrazione, oppure all'attivazione. Dal tema nuovo restano solo le posizioni dei menu e il suo CSS aggiuntivo. Il segno della copia è l'opzione `vv_theme_mods_migrated_<cartella>`.

**L'aspetto "ritaglio di carta" (0.3.x)** è nella storia git (commit `cc6b27b`, cartella `assets/css-ritaglio/`, font Baloo 2 e Nunito). Dalla 0.7.4 è fuori dal tema: i fogli attuali, `style.css` compreso, usano una palette a ruoli e una scala di caratteri e spazi che quella versione non conosce, quindi ripristinarlo richiede di riscriverne i token.


Il tema mantiene i colori pieni del marchio con regole precise di leggibilità e riordina i primi 3 livelli del sito.

## Messa in produzione

1. **Backup** di file e database dal pannello dell'hosting.
2. **Carica il tema**: *Aspetto → Temi → Aggiungi → Carica* con lo zip (`bin/build-zip.sh`, vedi *Sviluppo*) e scegli *Sostituisci l'attuale con quello caricato*. Lo zip deve avere lo stesso nome della cartella sul server (`wvoce-classic`): con un altro nome si crea un secondo tema senza impostazioni. Il padre è `sydney` (minuscolo: `Template: sydney` in `style.css`).
3. **Attiva il child.** Le impostazioni del Customizer vengono copiate da sole (`inc/migrate.php`). Il "CSS aggiuntivo" di Sydney è già riportato in `style.css`.

**Tornare indietro:** si riattiva il tema precedente, le cui impostazioni restano intatte.

## Cosa preparare

| Dove | Cosa |
|---|---|
| Aspetto → Widget | Blocco contatti in **Footer 1**, social in **Footer 2**, `[vv_dati_fiscali]` in **Footer 3** (Customizer → Footer: 3 colonne). In cima a ogni widget un blocco Titolo (Contatti, Seguici, Dati fiscali). I blocchi `side-contacts` già presenti vanno bene: le icone Font Awesome (telefono, e-mail, Facebook, Instagram…) il tema le mette accanto al testo; in Footer 3 il testo con CF, P.IVA e IBAN va sostituito dallo shortcode, che aggiunge i pulsanti "Copia". |
| Tutte le pagine | **Immagine in evidenza** orizzontale, larga almeno 1600px, col soggetto al centro (viene ritagliata 16:9 e, su telefono, 4:3). Dalle gallerie in fondo alle pagine togli le foto vecchie. |
| Aspetto → Menu | Aggiungi al primo livello una voce che porta alla pagina "Diventa socio": diventa da sola il pulsante rosso (non serve la classe CSS). |
| Customizer → VivaVoce | Testi, pulsanti e illustrazione dell'hero della home; CF, P.IVA, IBAN. |
| Home (SiteOrigin) | Nei due widget di testo sostituisci `[fpc_post_grid …]` con `[vv_cards in_evidenza="si" numero="3"]` e `[recent_post_carousel …]` con `[vv_cards fissati="si" numero="10" layout="carosello"]`; aggiungi una riga con `[vv_sostienici]`. Dai alla prima riga l'ID `appuntamenti` (serve al pulsante dell'hero). |
| Chi siamo, Cosa facciamo, Cosa puoi fare | Attributi pagina → Modello **Pagina hub**. Dai alle sottopagine un'immagine in evidenza e un riassunto. Per nascondere una sottopagina dalle card: riquadro "VivaVoce". |
| Chi siamo | Sostituisci il carosello dei fondatori con `[vv_cards categoria="soci" variante="persona" numero="8" ordine="titolo"]`. |
| Eventi, Formazione, Progetti | Modello **Pagina elenco** e, nel riquadro "VivaVoce", la categoria (es. `laboratori`). Negli articoli, riquadro "VivaVoce: date e luogo", e un'immagine in evidenza (senza, il pannello mostra un riquadro grigio chiaro). Finché la pagina ha il modello predefinito resta l'elenco scritto a mano. |
| Plugin | `featured-post-creative`, `wp-responsive-recent-post-slider` e `post-carousel` sono stati rimossi (ottobre 2026): home e Chi siamo usano `[vv_cards]`. |

## Home senza SiteOrigin

Con SiteOrigin disattivato la home mostra l'istantanea HTML salvata dal plugin, con le card **già renderizzate e quindi congelate** (i nuovi articoli non compaiono). Va sostituita con i blocchi: modifica la pagina, menu ⋮ → *Editor di codice*, incolla questo e aggiorna.

```html
<!-- wp:heading {"className":"vv-section-title","anchor":"appuntamenti"} -->
<h2 class="wp-block-heading vv-section-title" id="appuntamenti">…questo non potete perdervelo!</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[vv_cards in_evidenza="si" numero="3"]
<!-- /wp:shortcode -->

<!-- wp:heading {"className":"vv-section-title"} -->
<h2 class="wp-block-heading vv-section-title">…succedono diverse cose da queste parti!</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[vv_cards fissati="si" numero="10" layout="carosello"]
<!-- /wp:shortcode -->

<!-- wp:heading {"className":"vv-section-title"} -->
<h2 class="wp-block-heading vv-section-title">Sostienici!</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[vv_sostienici]
<!-- /wp:shortcode -->
```

I titoli usano la classe `vv-section-title` (stile e spaziature del tema); il primo ha l'àncora `appuntamenti` per il pulsante dell'hero.

## Menu mobile

Il pannello laterale di Sydney è ridisegnato: una voce per riga, frecce tonde che aprono i sottomenu (e si girano quando sono aperti), il sottomenu della sezione in cui ci si trova già aperto, "Diventa socio" come pulsante pieno, pagina oscurata dietro. Si chiude con la X, toccando fuori o con Esc. Le regole del menu desktop valgono solo nell'header (`.main-header #mainnav`), quelle del pannello solo in `.sydney-offcanvas-menu`.

## Tipografia, spazi e colori

Tutto in `assets/css/tokens.css`: per cambiare le proporzioni si modificano solo i token, non le singole regole.

- **Caratteri** (`--vv-fs-*`), fluidi fra 360px e 1200px di larghezza (telefono → desktop): meta, breadcrumb e date 14 · testi secondari e footer 15 · corpo del testo 17 → 18 (interlinea 1,65) · titoli card 19 → 21 (archivio 16–17) · h4 18 → 20 · h3 20 → 24 · h2 24 → 30 · titoli di sezione in maiuscolo 24 → 32 · titolo di pagina 30 → 44. Prima erano i valori del Customizer di Sydney (corpo 16, h1 52, h2 42), che sovrascriviamo.
- **Spazi** (`--vv-s-1…5`): 8 · 16 · 24 · 40 · 40 → 64. Breadcrumb → titolo 16, titolo → immagine o testo 24, immagine → testo 40, fra le sezioni 40 → 64, fra le card 30 (20 su telefono). Un titolo nel testo ha 1,8em sopra e 0,6em sotto, così sta vicino al paragrafo che introduce.
- **Colori**: due livelli. *Marchio* (`--vv-giallo`, `--vv-turchese`, `--vv-rosso`, `--vv-inchiostro`, `--vv-bianco`, `--vv-perla`), uguali in ogni tema. *Ruoli* (`--vv-fondo`, `--vv-fondo-card`, `--vv-riquadro`, `--vv-testo`, `--vv-testo-2`, `--vv-link`, `--vv-fondo-attivo`, `--vv-header-testo`, `--vv-bordo`, `--vv-ombra`…), gli unici che un tema ridefinisce (il tema scuro: vedi *Versione 0.7*). Nei fogli del tema e in `style.css` non ci sono colori scritti a mano. Testo sempre ad almeno 4,5:1: `--vv-link` (#007a87, 5,1:1) per link e titoli delle card, `--vv-testo-riquadro` (#3f4654) per il testo secondario sul grigio. Il turchese #00b2c3 e il giallo restano come fondi: sopra ci va il testo inchiostro.

## Breadcrumb

Automatiche su tutte le pagine tranne la home ("Home › Chi siamo › Statuto e organigramma"). Seguono il menu principale; per gli articoli usano la pagina collegata alla loro categoria (riquadro VivaVoce della pagina → "Categorie collegate") oppure la categoria del menu (es. Rassegna stampa). Gli articoli della categoria **Soci** portano a Chi siamo anche senza impostare nulla. Includono i dati strutturati BreadcrumbList per i motori di ricerca.

## Articoli e pagine dei soci

- Articolo: testo a sinistra, a destra la scheda **In breve** (quando, dove, categorie) e **Da non perdere** (articoli in evidenza); in fondo precedente/successivo come due card.
- Soci fondatori (modello "Page builder ready"): il child aggiunge breadcrumb e titolo e, in fondo, **Gli altri soci fondatori**.

## Shortcode

- `[vv_cards categoria="slug,slug" numero="6" variante="evento|persona|stampa|pagina" colonne="3" ordine="data|titolo" ids="1,2"]`: non ripete articoli già mostrati più su nella stessa pagina (`doppioni="si"` per permetterlo).
  - `in_evidenza="si"`: solo gli articoli con la spunta **In evidenza** (lo stesso campo del vecchio plugin Featured Post Creative, quindi le spunte già messe restano valide), nel riquadro "VivaVoce: date e luogo"; nell'elenco articoli c'è la colonna ★. Si somma alle altre condizioni, `periodo` compreso.
  - `periodo="futuro"` o `periodo="dal-3-mesi"`: eventi dalle loro date (vedi *Versione 0.6*).
  - `fissati="si"`: aggiunge agli ultimi articoli quelli **Fissati in cima al blog** (casella di WordPress nell'editor), come faceva il vecchio carosello; tutto in ordine dal più recente al più vecchio.
  - `layout="carosello"`: una riga che scorre con dito, rotella o frecce, con il contatore "1–3 di 18"; con `autoplay="si"` scorre da solo, una card ogni 5 secondi, con il pulsante di pausa.
- `[vv_sottopagine padre="ID"]`: card delle pagine figlie.
- `[vv_elenco categoria="slug"]`: "In programma" + archivio per anno, tutto a pannelli.
- `[vv_sostienici]`: socio / donazione / 5×1000.
- `[vv_dati_fiscali]`: CF, P.IVA, IBAN con pulsante "Copia".

## Header e footer di Sydney

Cambiando tema, Sydney accende da solo il suo costruttore header/footer (che ha un markup diverso e non mostra le aree widget del footer) e non ha un interruttore per spegnerlo. Il child lo tiene spento (`inc/migrate.php`, senza modificare l'opzione salvata). Per usarlo davvero: `define( 'VV_SYDNEY_BUILDER', true );` in `wp-config.php` (il CSS del child andrebbe però adattato).

## Cache

I fogli di `assets/css/` e `vv.js` escono con `?ver=` uguale alla data di modifica del file (`filemtime()` in `functions.php`): dopo un aggiornamento i browser scaricano da soli la copia nuova. A ogni rilascio conviene comunque alzare `Version:` in `style.css` (per esempio 0.7.3 → 0.7.4): vale per `style.css` stesso, caricato da Sydney, e dice quale versione è installata.

## Correzioni automatiche dei contenuti

- Righe di trattini bassi `________` → divisore a pennellata.
- Lettere "decorative" incollate dai social (𝕍𝕠𝕔𝕖, 𝐁𝐨𝐥𝐝…) → testo normale in grassetto: nei font del sito comparivano come □.
- Font e corpi incollati da Word o e-mail → quelli del sito.
- Titoli colorati a mano (per esempio giallo su bianco) → inchiostro.

- Voci di elenco rimaste fuori da un `</ul>` (HTML rotto, per esempio in Formazione e laboratori) → riavvolte in un elenco. Il contenuto salvato non cambia; correggerlo nell'editor resta comunque una buona idea.

## Sviluppo

- **Test:** `php tests/run.php` controlla senza WordPress le funzioni pure: codice fiscale, righe e tab del foglio dei soci, date, slug, `meta_query`, lettere decorative.
- **Regole di scrittura:** `composer install`, poi `composer lint` (phpcs con WordPress-Extra e compatibilità PHP 7.4, regole in `phpcs.xml.dist`). Nel codice le eccezioni motivate sono segnate con `phpcs:ignore`.
- **Zip del tema:** `bin/build-zip.sh` crea `build/wvoce-classic.zip` con dentro la cartella `wvoce-classic`, il nome che il tema ha sul server. Contiene l'ultimo commit; test, documenti e file di sviluppo restano fuori (`.gitattributes`).
- **GitHub Actions** (`.github/workflows/ci.yml`): a ogni push sintassi PHP e test, con PHP 7.4 e 8.3.

## Struttura

```
style.css                 intestazione + CSS aggiuntivo ereditato da Sydney
functions.php             caricamento di CSS e JS, preload dei font, script del tema chiaro/scuro
inc/migrate.php           copia delle impostazioni del Customizer, costruttore header/footer spento
inc/setup.php             filtri su Sydney, traduzioni mancanti, pulizia dei contenuti, credits del footer
inc/customizer.php        pannello VivaVoce (hero, dati dell'associazione)
inc/meta.php              date e luogo degli eventi, "In evidenza", opzioni delle pagine
inc/cards.php             card, griglie, date degli eventi, eventi per periodo
inc/shortcodes.php        [vv_cards], [vv_sottopagine], [vv_elenco], [vv_sostienici], [vv_dati_fiscali]
inc/home.php              hero della home al posto dello slider
inc/archive.php           archivi a card, 12 per pagina
inc/breadcrumbs.php       percorso in alto, dati strutturati, voce di menu della sezione corrente
inc/single.php            articolo con colonna laterale, navigazione, altri soci fondatori
inc/page.php              pagine con la colonna "In questa sezione"
inc/redirects.php         301 degli indirizzi spariti
inc/forminator.php        controllo del codice fiscale nei moduli Forminator
inc/sheets.php            iscrizioni dei soci e dei corsi nei fogli Google
template-parts/           card.php, home-hero.php
page-templates/           page-hub.php, page-elenco.php
post-templates/           post_full.php ("Page builder ready" dei soci fondatori)
assets/css/               tokens, base, layout, header, footer, components, home, pages, plugins, scuro
assets/js/vv.js           testata, switcher del tema, caroselli, menu mobile, pulsanti "Copia"
assets/fonts/             Poppins (self-hosted, licenza OFL)
assets/img/               livelli dell'hero, faccine e scritta del footer, mano del "torna su"
assets/svg/               pennellata dell'hero, blob delle card senza foto
docs/                     script Apps Script dei fogli soci e corsi, bozza di informativa privacy, piani di lavoro
tests/                    test senza WordPress
bin/build-zip.sh          zip del tema
```
