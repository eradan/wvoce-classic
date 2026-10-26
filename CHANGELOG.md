# Note di versione

Storia del tema VivaVoce Classic, dalla versione più recente. La guida d'uso è nel README.

## Versione 1.1.0: agenda degli appuntamenti e rassegna stampa a titoli in home

- **Agenda** (`[vv_cards … layout="agenda" lotto="3"]`): su telefono gli appuntamenti diventano righe compatte, miniatura a sinistra e titolo, data e luogo a destra, invece di un carosello che ne mostrava uno alla volta; da 768px restano le card a tre colonne. Si vedono le prime 3, il pulsante "Mostra altri appuntamenti" apre le successive 3 alla volta e porta il focus sulla prima comparsa; senza JavaScript si vedono tutte (`assets/js/vv.js`).
- **Data a foglietto di calendario** sulla foto degli eventi con le date dell'evento: "13 NOV", "3–5 OTT", "OTT–DIC 2026" per i corsi (`vv_date_chip()` in `inc/cards.php`). Vale in home, in "In programma" e nelle pagine elenco; non nell'archivio per anno.
- **Eventi conclusi riconoscibili:** una fascetta obliqua gialla "Completato" sull'angolo della foto, che resta a colori: d'estate, senza niente in programma, la home non sembra spenta. In home restano in coda, dopo quelli in programma (l'ordine c'era già).
- **Rassegna stampa solo testo** (`variante="citazione"`): testata e data in alto, poi il titolo dell'articolo fra «». La scansione del ritaglio in miniatura non si leggeva; resta intera nella pagina dell'articolo.
- **Una data sola sui ritagli:** anche nella pagina Rassegna stampa via la data dell'articolo, che spesso non coincideva con quella nella riga della testata.
- **Foto nei riquadri di "Sostienici"** (`[vv_sostienici]`): "Diventa socio" e "Fai una donazione" mostrano in cima l'immagine in evidenza delle loro pagine (quelle del Customizer); il 5×1000, che una pagina non ce l'ha, la foto scelta nel nuovo campo Customizer → VivaVoce → Dati dell'associazione → "Immagine del 5×1000". Senza immagine il riquadro resta solo testo.
- **Test:** 57 controlli.

Da fare sul sito, nella pagina home (Pagine → Benvenuto):
1. Appuntamenti: sostituire lo shortcode con `[vv_cards categoria="eventi" periodo="dal-3-mesi" numero="9" layout="agenda" lotto="3"]` e aggiungere sotto un paragrafo con il link "Tutto il programma →" a /in-programma/.
2. Dicono di noi: sostituire lo shortcode con `[vv_cards categoria="rassegna-stampa" variante="citazione" numero="6" layout="carosello"]` (senza `autoplay`) e aggiungere sotto "Tutta la rassegna stampa →" a /category/rassegna-stampa/.
3. Customizer → VivaVoce → Dati dell'associazione: scegliere l'"Immagine del 5×1000".
4. Contenuti: l'estratto di "In viaggio con Marco Polo e Rustichello" è quello di "AI 2226"; "Corsi e laboratori 2026-2027" ripete i tre corsi in agenda (togliergli le date dell'evento se non deve comparire).

## Versione 1.0.1: tolti i pezzi di transizione

- **Tolto il passaggio una tantum al plugin** (`inc/iscrizioni.php`): sul sito ha già passato configurazione e stato delle iscrizioni al plugin wvoce-iscrizioni, e il tema non conosce più l'interno del plugin. L'opzione `vv_iscrizioni_passate` resta nel database, innocua.
- **Tolta l'impostazione "Pagina 'Iscrizione ai corsi'"** dal Customizer: la usava solo il passaggio. La pagina dei corsi è nel plugin (Strumenti → Iscrizioni → Collegamenti).
- **Email di contatto e pagina "Diventa socio"** restano nel Customizer per il sito (piè di pagina e modello delle email, dati strutturati, pulsante della home); il plugin ha le sue per le iscrizioni. Il Customizer lo ricorda: se cambiano, vanno cambiate in tutti e due.
- **Test:** 51 controlli.

Da fare: nessun passo manuale; aggiornare insieme il plugin alla 1.1.0.

## Versione 1.0.0: prima versione stabile, iscrizioni nel plugin wvoce-iscrizioni

Il tema copre ormai tutto il sito di VivaVoce ed è in uso da settimane: da qui i numeri di versione seguono la regola consueta (1.0.x correzioni, 1.x novità compatibili, 2.0 cambiamenti che richiedono interventi sul sito).

**Le iscrizioni passano al plugin [wvoce-iscrizioni](https://github.com/eradan/wvoce-iscrizioni)** (versione 1.0.0), indipendente dal tema e configurabile in Strumenti → Iscrizioni: codice fiscale controllato, socio richiesto per i corsi e unico per anno sociale, passaggio dal modulo dei soci a quello dei corsi, "Recupera i tuoi dati", copia nei fogli Google, compilazione automatica del browser. Dal tema escono `inc/forminator.php`, `inc/sheets.php`, `inc/sheets-admin.php`, `inc/apps-script/`, la parte dei moduli di `assets/js/vv.js` e i loro stili; restano la grafica dei moduli Forminator, il modello delle email (`inc/email.php`, che vale anche per le email del plugin) e i colori del plugin presi dai token del tema (`assets/css/plugins.css`). Il tema funziona anche senza il plugin.

- **Passaggio automatico** (`inc/iscrizioni.php`): al primo caricamento con il plugin attivo, il tema gli passa la configurazione di VivaVoce (moduli 274 e 1617, campi, le stesse colonne e gli stessi tab dei fogli, anno sociale da settembre, testi, pagine) e lo stato dell'invio ai fogli (punto raggiunto, iscrizioni sospese, partenza dei corsi), e spegne il vecchio giro del tema. Niente riparte da capo, i fogli non cambiano.
- **"Recupera i tuoi dati"** (rispetto alla 0.13): attesa fra due email allo stesso codice fiscale ridotta da 15 a 5 minuti; esito vero per gli amministratori loggati; anteprima con `?wvi-socio-prova=` al posto di `?vv-socio-prova=`. I link mandati prima dell'aggiornamento (validi 24 ore) non valgono più: basta chiederne uno nuovo.
- **Email più pulite per i filtri antispam:** tolto il testo d'anteprima nascosto, che SpamAssassin conta come trucco da spam (FONT_INVIS_MSGID, la penalità più pesante nella prova con mail-tester.com); un paragrafo che contiene solo un indirizzo diventa un pulsante giallo "Apri il link" (nella versione in solo testo resta l'indirizzo).
- **Test:** 56 controlli nel tema; quelli sulle iscrizioni (124, più 21 sugli script Apps Script) sono passati al plugin.

Da fare sul sito, nell'ordine:
1. Plugin → Aggiungi nuovo → Carica plugin: `wvoce-iscrizioni.zip`, poi Attiva. Finché il tema è alla 0.13 il plugin resta fermo e lo segnala nel suo pannello.
2. Aspetto → Temi: caricare il tema 1.0.0 (sostituisce quello installato). Al primo caricamento di una pagina il tema passa la configurazione al plugin.
3. Strumenti → Iscrizioni: controllare le schede (collegamenti attivi, campi, colonne) e, in Invio soci / Invio corsi, la prova a secco.
4. Facoltativo: incollare i nuovi script Apps Script (scheda Script) e pubblicare una Nuova versione del deployment. Gli script già pubblicati continuano a funzionare con gli stessi fogli.
5. Mailjet: disattivare il tracciamento di clic e aperture (Impostazioni account → Tracciamento).

## Versione 0.13.2: "Recupera i tuoi dati"

- **"Recupera" al posto di "riprendi"**, in italiano più corretto: link, messaggi, email ("VivaVoce: recupera i dati della tua iscrizione") e avviso sul modulo. L'indirizzo per il pulsante diventa `vv/v1/recupera-socio`.
- **Link più discreto:** "Già socio? Recupera i tuoi dati", allineato a destra sotto il codice fiscale, in minuscolo; su telefono resta sotto il campo. L'esito della richiesta compare sotto la riga, a tutta larghezza.
- **Informativa privacy** (pagina sul sito, aggiornata al 18/10/2026): paragrafo "Recupero dei dati dell'ultima iscrizione" e Mailjet fra i fornitori, per l'invio delle email.

## Versione 0.13.1: "Riprendi i tuoi dati" più discreto

- **Una riga sola** sotto quella di nome, cognome e codice fiscale, con un link al posto del riquadro con pulsante: la colonna del codice fiscale non si allunga più. Messaggio dopo la richiesta più breve.
- **Anteprima per gli amministratori:** da loggati, `…/diventa-socio/?vv-socio-prova=123` apre il modulo compilato con i dati dell'iscrizione 123 (il numero dopo il trattino nella colonna "Rif." del foglio, F274-123), senza mandare email. Serve a provare la ricompilazione e ad aiutare un socio che non riceve l'email.

## Versione 0.13.0: email con il marchio VivaVoce

- **Tutte le email del sito con lo stesso aspetto** (`inc/email.php`): quelle del tema ("Riprendi i miei dati"), le notifiche di Forminator a chi si iscrive e alla segreteria, Contact Form 7 e le email di WordPress. Header giallo con il logo (`assets/img/logo-email.png`, PNG perché Outlook non mostra il WebP), corpo bianco, piè di pagina con ragione sociale, sede, email, sito, social e informativa privacy, presi dal Customizer. Le email scritte come testo semplice diventano paragrafi con i link cliccabili; quelle già in HTML (Forminator) entrano nel modello così come sono; un'email che è già un documento HTML completo resta com'è.
- **Meno probabilità di finire nello spam**, per la parte che dipende dal messaggio: ogni email ha anche la versione in solo testo (multipart/alternative), l'HTML ha lingua, charset e titolo, un testo d'anteprima, solo link al dominio del sito, e il Message-ID usa il dominio wvoce.it invece del nome interno del server.
- **Test:** 192 controlli.

Da fare:
1. DNS di wvoce.it: la posta è su Google Workspace (MX e DKIM di Google), ma il record SPF autorizza solo Mailjet e OVH. Va aggiunto Google: `v=spf1 include:_spf.google.com include:spf.mailjet.com include:mx.ovh.com ~all` (togliendo i servizi che non si usano più).
2. Provare un invio verso l'indirizzo di mail-tester.com da WP Mail SMTP → Strumenti → Email di prova, e controllare il punteggio.

## Versione 0.12.0: rinnovo dell'iscrizione senza ricompilare tutto

- **"Riprendi i miei dati" nel modulo dei soci.** Sotto il codice fiscale c'è un pulsante per chi si è già iscritto in passato. Il sito cerca l'ultima iscrizione con quel codice fiscale negli ultimi tre anni sociali e scrive all'indirizzo email di quell'iscrizione:
  - socio di un anno precedente: un link, valido 24 ore, che riapre il modulo già compilato con i dati anagrafici, di residenza e di contatto. Ricevuta, accettazioni (GDPR, Statuto), autorizzazione alle comunicazioni e scelte vanno rifatte;
  - già iscritto quest'anno: un'email che lo dice, senza link.
- **Nessuno scopre chi è socio.** A video il messaggio è sempre lo stesso, che il codice fiscale sia in archivio o no; solo la casella email dell'interessato riceve la risposta. Al massimo 5 richieste all'ora dalla stessa connessione e un'email ogni 15 minuti per lo stesso codice fiscale. Nel link c'è solo un codice: i dati si rileggono all'apertura, e se l'iscrizione nel frattempo è stata cancellata o messa nello spam il link non vale più.
- **Codice fiscale controllato subito:** un codice sbagliato (formato o carattere di controllo) viene segnalato appena si esce dal campo, non più solo dopo l'invio, nel modulo dei soci e in quello dei corsi. Il controllo "già iscritto quest'anno" resta all'invio.
- **Compilazione automatica del browser:** nome e cognome distinti, telefoni riconosciuti, e il luogo di nascita lasciato fuori (il browser ci metteva la città di residenza). Chi ha i dati salvati nel browser li inserisce con un tocco.
- **Codice:** `inc/forminator.php` (sezione "Riprendi i dati", endpoint `vv/v1/riprendi-socio`, ricompilazione comune ai due moduli), `vv_soci_iscrizioni()` in `inc/sheets.php`, `assets/js/vv.js`. **Test:** 182 controlli.

Da fare:
1. Provare il pulsante con il proprio codice fiscale e controllare che l'email arrivi (passa da WP Mail SMTP).
2. Informativa privacy: aggiungere che, su richiesta dell'interessato e con conferma via email, i dati dell'ultima iscrizione (al massimo tre anni sociali) possono essere usati per precompilare il modulo di rinnovo.

## Versione 0.11.1: immagine dell'associazione

- **Dati strutturati:** l'associazione ha anche un'immagine, la stessa dell'anteprima della home sui social. Il Test dei risultati multimediali di Google la segnalava come campo facoltativo mancante.

## Versione 0.11.0: dati strutturati per Google

- **Il sito e l'associazione descritti per i motori di ricerca** (`inc/dati-strutturati.php`, schema.org in JSON-LD). In home: il sito con il suo nome e i nomi alternativi (la ragione sociale e "wvoce"), così Google sa come chiamarlo nei risultati; l'associazione con logo, sede, telefono, email, codice fiscale, partita IVA e profili social.
- **Gli eventi negli articoli:** ogni articolo con data e luogo si presenta a Google come evento (date, luogo, immagine, organizzatore), e può comparire nei risultati per eventi. Senza luogo l'articolo non viene segnalato come evento: per gli eventi nuovi conviene compilare sempre il campo Luogo nella forma "Nome del posto, indirizzo, città".
- **Tutto generato dai dati del sito:** nome e motto da Impostazioni → Generali, logo da quello del sito, date e luogo dai campi dell'articolo. I dati dell'associazione sono nel Customizer → VivaVoce → Dati dell'associazione, con i nuovi campi ragione sociale, sede, telefono, email, Facebook e Instagram (già compilati con i dati attuali).
- **Test:** 160 controlli.

Da fare dopo il caricamento: controllare la home e un evento con il Test dei risultati multimediali di Google (search.google.com/test/rich-results).

## Versione 0.10.2: una sola informativa sulla privacy

- **`/privacy-policy/` porta all'informativa vera** (`/informativa-sulla-privacy/`). La vecchia pagina era il modello di WordPress mai compilato, con il "Testo suggerito"; ora è in bozza e il suo indirizzo, se qualcuno lo ha salvato, reindirizza (`inc/redirects.php`).

Da fare sul sito: Impostazioni → Privacy → scegliere "Informativa sulla Privacy" come pagina della privacy policy.

## Versione 0.10.1: sito più leggero

- **Icone senza Font Awesome.** Le icone dei widget del footer e quella del PDF negli articoli sono disegnate dal tema (`assets/css/components.css`): niente più 280 KB di file da use.fontawesome.com a ogni pagina. Il markup resta lo stesso (`<i class="fas fa-phone">`); per un'icona nuova va aggiunta una riga in quel file.
- **Miniature in WebP.** Le dimensioni generate da WordPress per le immagini caricate escono in WebP, più leggere; l'originale resta JPEG o PNG nella libreria dei media.
- **Card:** il browser sceglie la miniatura in base alla larghezza reale della card, non a quella della pagina.
- **Un solo h1 in home:** il titolo della pagina home ("Benvenuto") non si stampa più, l'h1 è quello dell'hero.
- **Widget del footer:** lo stile vale per la classe `side-contacts` e non più per l'id, ripetuto in tre widget.

Da fare sul sito, nell'ordine:
1. Caricare il tema.
2. Nei widget del footer, `id="side-contacts"` diventa `class="side-contacts"`.
3. Disattivare i plugin Font Awesome e Smush (il lazy load lo fa già WordPress).
4. Strumenti → Rigenera miniature, **senza** eliminare le miniature delle dimensioni non più usate: gli articoli puntano ancora a quei file.

## Versione 0.10.0: dal modulo dei soci al corso, senza ricompilare

- **Iscrizione al corso subito dopo quella a socio.** Chi nel modulo dei soci risponde di voler frequentare anche un corso arriva, dopo l'invio, alla pagina dei corsi con il modulo già compilato: nome, cognome, codice fiscale, email, cellulare e le caselle Regolamento, Socio e Privacy, già accettate con l'iscrizione a socio. Restano da scegliere i corsi e da caricare la ricevuta. I due moduli restano distinti: la pagina dei corsi continua a servire a chi è già socio.
- **I dati non passano nell'indirizzo.** Il tema aggiunge al rinvio un codice usa e getta (`?vv=…`) e tiene i dati sul server per due ore (`vv_corso_rinvio()`, `vv_corso_dati()` in `inc/forminator.php`; ricompilazione in `assets/js/vv.js`). Con un codice scaduto o sbagliato il modulo si presenta vuoto. La pagina aperta con il codice non entra nella cache.
- **Nuova impostazione** Customizer → VivaVoce → Dati dell'associazione → *Pagina "Iscrizione ai corsi"*: il codice si aggiunge solo ai rinvii verso questa pagina.
- **Test:** 146 controlli.

Da fare nel pannello di Forminator, modulo "Iscrizione Socio":
1. Un campo a scelta singola "Vuoi iscriverti anche a un corso?" con No (predefinito) e Sì, prima del pulsante di invio.
2. Comportamento dopo l'invio: un secondo comportamento "Reindirizza", nella stessa scheda, verso `/cosa-puoi-fare/iscrizione-corso/`, con la condizione "il campo del punto 1 è Sì". Il messaggio di ringraziamento resta per il No.

## Versione 0.9.8: illustrazione della home senza ovale

- **Via l'ovale dietro testa e mano dell'hero**, in entrambi i temi: tolti `.vv-hero__tela::before` e le variabili `--vv-disco-*` da `home.css` e `--vv-fondo-disco` da `tokens.css`. Nel tema scuro l'illustrazione sta direttamente sul blu.
- **Bordi delle immagini dell'animazione ripuliti** (`assets/img/hero/`: testa, mano, tre trattini). Le sfumature dei bordi erano mescolate al fondo perla dell'illustrazione originale e sul blu del tema scuro lasciavano un alone chiaro: ora i pixel di bordo hanno il colore pieno della forma e la parte chiara è diventata trasparenza, così il bordo resta morbido su qualunque fondo. Le striature chiare del pastello dentro le forme restano. Peso dei file pressoché invariato.

## Versione 0.9.7: codice più snello, stesso sito

Revisione per la manutenzione: circa 460 righe di codice in meno, aspetto e funzioni invariati. Verifica a vista su 13 pagine del sito (desktop e telefono, tema chiaro e scuro, menu aperti): 56 schermate identiche al pixel prima e dopo.

- **Codice di transizione rimosso.**
  - Copia delle impostazioni del Customizer da un altro tema (`inc/migrate.php`), già eseguita sul sito. Del file resta il filtro che tiene spento il costruttore header/footer di Sydney, ora in `inc/setup.php`.
  - Passaggio delle opzioni dai nomi della 0.8 (`vv_sheets_migra_opzioni()`), avviso sulle costanti di `wp-config.php`, rinvio dal vecchio indirizzo `page=vv-sheets`.
  - Controlli su SiteOrigin (`panels_data`) per la colonna laterale e per Contact Form 7, selettori `.so-panel` e `.panel-grid` nei CSS: pagine e articoli del sito sono ormai tutti a blocchi.
- **Sedici filtri `vv_*` in meno.** Punti di aggancio senza consumatori, usati solo dai test: `vv_sicurezza`, `vv_condivisione`, `vv_condivisione_dati`, `vv_cf7_ovunque`, `vv_page_has_aside`, `vv_elenco_grandi`, `vv_generic_tags`, `vv_redirects`, `vv_sheets_collegamenti`, `vv_soci_colonne`, `vv_corsi_colonne`, `vv_corsi_campo`, `vv_forminator_cf_fields`, `vv_forminator_soci_richiesti`, `vv_forminator_soci_unici`, `vv_email_segreteria`. I valori restano nel codice, dove già stavano: per cambiarli si modifica la riga corrispondente.
- **Moduli Forminator in una tabella sola.** `vv_forminator_moduli()` associa a ogni modulo scelto nel pannello la sua regola (`richiesto` per i corsi, `unico` per i soci); il campo del codice fiscale è la costante `VV_CAMPO_CF`. Escono tre funzioni con i rispettivi filtri.
- **Fogli Google con meno stati.** Con il campo del corso fisso (`VV_CORSI_CAMPO`) cade il ramo "requisito" (`vv_corsi_requisito()`, `vv_sheets_manca()`): un collegamento è attivo appena ha indirizzo, codice e modulo, e `vv_sheets_configurato()` prende il posto di `vv_sheets_ready()`. Nel tab di ogni collegamento la tabella delle colonne e l'elenco dei campi del modulo compaiono sempre, anche per i soci.
- **CSS.** Via le regole copiate dal vecchio Customizer e poi annullate in `footer.css` (larghezza della fascia turchese, contatti all'85%, icone social assenti dalla fascia, `.button-slider` dello slider spento, `<span>` nelle voci di menu), il fondo delle card dichiarato due volte, le proprietà inattive del contatore dei caroselli e la variante "Da non perdere" a card piene della home, oggi senza contenuti che la usino: la home presenta solo caroselli.
- **Meno ripetizioni nel PHP.** I sette campi aggiuntivi di articoli e pagine si registrano da una tabella (`vv_register_meta()`); i titoli nella colonna laterale e nella navigazione fra articoli passano una volta sola dalla conversione delle lettere decorative.
- **Documenti.** Via `docs/piano-iscrizione-corsi.md`, già dichiarato documento storico della 0.8.0.
- **Test:** 132 controlli; tolti quelli delle funzioni eliminate.

Dopo il caricamento basta un controllo a vista: header e footer con logo e colori di sempre, Strumenti → Esporta iscrizioni con stato e prova a secco come prima.

## Versione 0.9.6: ordine degli appuntamenti, anteprime social, sito più riservato

- **Ordine degli appuntamenti.** In home e in "In programma" un evento di domani passava dietro ai corsi cominciati da una settimana, perché l'elenco seguiva la sola data d'inizio. Ora vengono prima gli appuntamenti che devono ancora cominciare e quelli brevi in corso (fino a un mese), dal più vicino; poi corsi e rassegne lunghi già avviati; infine gli eventi conclusi. Funzione: `vv_ordina_eventi()` in `inc/cards.php`.
- **Anteprime per motori di ricerca e social** (`inc/condivisione.php`). Ogni pagina riceve `meta description` e i tag Open Graph e Twitter: titolo, riassunto (l'estratto, altrimenti l'inizio del testo), immagine in evidenza in formato grande, anche con *Senza immagine in testa*. In home il titolo dell'hero fa da descrizione, finché manca il motto del sito (Impostazioni → Generali). Con Yoast, Rank Math, AIOSEO o SEOPress attivi il tema lascia fare al plugin; filtri `vv_condivisione` e `vv_condivisione_dati`.
- **Emoji incollate da Facebook e Gmail** come immagini 16×16 dei server di Meta e Google (dodici articoli): diventano testo. Niente più richieste esterne a ogni visita, fuori dal consenso cookie, e niente immagini rotte quando quegli indirizzi scadono (`vv_emoji_esterne()`).
- **Gallerie dell'editor classico.** Le miniature aprono l'immagine, non la pagina dell'allegato, che dava 404 (galleria della *Bicicletta gialla*).
- **Contact Form 7 e reCAPTCHA** solo nelle pagine con un modulo (oggi Contatti), non più su tutte; filtro `vv_cf7_ovunque` per tornare indietro.
- **Nomi utente meno esposti** (`inc/sicurezza.php`): fuori dalla sitemap (`wp-sitemap-users-1.xml`) e dall'API pubblica (`/wp-json/wp/v2/users`, che resta per chi è collegato); XML-RPC spento, versione di WordPress tolta dall'HTML. Filtro `vv_sicurezza` per spegnere tutto.
- **`style.css` con la data del file** come versione: prima Sydney lo caricava sempre con `?ver=20230821` e i browser potevano tenere la copia vecchia.

Da sistemare nel pannello (contenuti, non tema):
- *Da La Nazione, Pisa, 10 settembre 2021*: i due indirizzi email sono linkati senza `mailto:` e portano a una pagina 404.
- *Regolamento*: "Regolamento Soci" e "Codice di Comportamento" sono Titolo 1; vanno portati a Titolo 2 (il Titolo 1 è il nome della pagina).
- Plugin *Font Awesome*: le icone arrivano due volte, dal sito e da `use.fontawesome.com`. Nelle impostazioni del plugin conviene scegliere un solo metodo e il caricamento dal sito (self-hosted).
- Motto del sito (Impostazioni → Generali): vuoto; una frase breve diventa la descrizione della home nei risultati di ricerca.

## Versione 0.9.5: correzioni, codice più snello

- **Controlli dei moduli legati al pannello.** Codice fiscale, requisito di socio per i corsi e iscrizione unica per anno seguono i moduli scelti in Strumenti → Esporta iscrizioni (`vv_forminator_modulo()`), non più i numeri 274 e 1617 scritti nel codice: con il modulo dei corsi dell'anno nuovo basta cambiarlo nel pannello. I filtri `vv_forminator_cf_fields`, `vv_forminator_soci_richiesti` e `vv_forminator_soci_unici` restano.
- **Date degli eventi brevi con i giorni.** Fino a un mese l'etichetta riporta i giorni ("3–5 ottobre 2026", "28 settembre – 3 ottobre 2026"); oltre, i mesi come prima ("ott–dic 2026"). Funzione: `vv_date_range_label()`.
- **Card.** Il badge non mostra più "Nessuna categoria" e lascia passare il clic al link della card; il focus da tastiera disegna il contorno attorno alla card intera. "In breve" e "In questa sezione" tornano sul grigio del riquadro, come il footer: una regola delle card li copriva con il grigio più chiaro.
- **Messaggio "niente in programma"** in un riquadro come le card. Nelle liste "Da non perdere" un articolo senza immagine ha un riquadro grigio al posto della miniatura, così i titoli restano in colonna.
- **Hero.** Altezza in `svh` sui telefoni (i pulsanti restano sopra la barra degli indirizzi); priorità di caricamento solo per testa e mano dell'illustrazione; un'illustrazione diversa presa dalla libreria dei media esce con larghezza, altezza e `srcset`. Occhiello e testo, mai visibili, lasciano il Customizer insieme alla pennellata SVG.
- **Caroselli** con frecce disegnate in SVG; il pulsante "Copia" seleziona il valore se il browser nega gli appunti.
- **`[vv_cards numero="-1" periodo="…"]`** mostra tutti gli eventi del periodo, non più uno solo.
- **Invio delle iscrizioni ai fogli Google: quattro correzioni.**
  - *Modulo cambiato nel pannello* (per esempio quello dei corsi del nuovo anno): l'invio riparte davvero dalla prima iscrizione del modulo nuovo. Prima il collegamento dei corsi fissava come partenza l'iscrizione più recente al momento del cambio, e quelle arrivate nel frattempo non partivano mai.
  - *Bozze completate e iscrizioni tolte dallo spam*: restano annotate (`vv_soci_sospese`, `vv_corsi_sospese`) e partono quando tornano valide, anche se nel frattempo il punto raggiunto le ha superate. Il punto raggiunto non torna mai indietro.
  - *Più corsi nello stesso tab* di "configurazioni": il tab riceve la colonna "Corso" e chi sceglie due di quei corsi ha due righe. Prima la seconda veniva scartata come doppione, pur risultando confermata.
  - *Colonne della segreteria riempite in anticipo* (caselle, formule): le righe nuove vanno sotto l'ultima con dati del sito, non in fondo alle caselle; lo script dei soci scrive solo nelle colonne del sito e lascia intatte le altre, come già quello dei corsi.
  - **Entrambi gli script vanno incollati di nuovo e ripubblicati** (tab *Script Apps Script* → Copia lo script → editor → Gestisci deployment → matita → Nuova versione). URL, codice e foglio restano gli stessi.
  - Test nuovi: `node tests/apps-script.js` fa girare i due script su un foglio finto in memoria (anche in GitHub Actions).
- **Immagine in testa spegnibile, articolo per articolo e pagina per pagina.** La casella *Senza immagine in testa* (riquadro "VivaVoce: date e luogo" degli articoli, riquadro "VivaVoce" delle pagine) toglie l'immagine in evidenza da sopra il testo; card, "Da non perdere" e anteprime dei social continuano a usarla (`vv_senza_immagine`, `vv_senza_immagine_in_testa()`).
- **Codice tolto:** la classe automatica `vv-cta` per la voce "Diventa socio" (mai stilata nel tema classico), `vv_svg()` e `assets/svg/`, la classe `vv-design`, il CSS di `.vv-timeline`. Regole duplicate riunite in `components.css`, `home.css`, `layout.css`, `footer.css`.
- **Struttura:** le correzioni dei contenuti passano in `inc/contenuti.php`, gli schemi di pagina (`vv_two_column_template()`, `vv_page_with_extra()`) in `inc/page.php`; i collegamenti ai fogli elencano solo le voci che non si ricavano dall'id.
- **phpcs in GitHub Actions**, con le regole adattate alle convenzioni del tema (`phpcs.xml.dist`).

## Versione 0.9.4: corsi riservati ai soci, Rif. in prima colonna

- **Iscrizione ai corsi solo per i soci dell'anno.** Il modulo dei corsi (n. 1617) cerca il codice fiscale fra le iscrizioni del modulo dei soci (n. 274) arrivate dal 1° settembre dell'anno sociale in corso (settembre–agosto, `vv_anno_sociale()`, la stessa regola dei tab "DomandeAmmissione"). Se manca, il modulo si ferma con un messaggio accanto al campo e il link al modulo di iscrizione a socio (Customizer → VivaVoce, `vv_url_socio`). Spam e bozze restano esclusi. Moduli soggetti al controllo: filtro `vv_forminator_soci_richiesti` (un array vuoto lo spegne). Funzione di ricerca: `vv_socio_iscritto()`.
- **Rif. in colonna A.** Nei tab nuovi lo script scrive Rif. per prima; in un tab già intestato che ne è privo inserisce una colonna A. Dove Rif. c'è già, lo trova per intestazione in qualunque colonna.
- **Colonne della segreteria fuori dal codice.** Sparisce il tab modello: un tab vuoto riceve solo le intestazioni del sito, e le altre colonne restano alla segreteria, che le organizza come preferisce.
- **Lo script dei corsi va incollato di nuovo e ripubblicato** (Gestisci deployment → matita → Nuova versione). Nei tab già popolati conviene spostare a mano la colonna Rif. in A: valori e protezione dai doppioni la seguono.

## Versione 0.9.3: correzioni e pulizia

- **Script Apps Script più veloci: vanno incollati di nuovo e ripubblicati.** Lo script dei corsi scriveva cella per cella e rileggeva l'intero tab a ogni riga per cercare i doppioni: con blocchi da 20 iscrizioni poteva superare i 30 secondi di attesa del sito, che segnalava errore anche a righe già scritte. Ora legge intestazioni, Rif. e Corso una volta per tab e scrive a tratti di celle contigue; le colonne senza valore (segreteria) restano intatte. Lo script dei soci tiene anch'esso i Rif. già presenti in memoria. Per entrambi: tab *Script Apps Script* → Copia lo script → incollare nell'editor → Gestisci deployment → matita → Nuova versione. URL, codice e foglio restano gli stessi; gli script della 0.9.2 continuano comunque a funzionare.
- **Corsi uniti in un testo.** Se Forminator salva le caselle come testo unico, un'opzione contenuta in un'altra ("Il gioco del teatro" in "Il gioco del teatro avanzato") non viene più trovata due volte: si cercano prima le opzioni più lunghe (`vv_corsi_scelti()`).
- **Confronto dei nomi dei corsi uguale a quello dello script.** `vv_corsi_normalizza()` tratta lo spazio non separabile e le maiuscole non ASCII come `normalizza_()` dello script: l'anteprima del pannello indica lo stesso tab del foglio.
- **Cron dei fogli fuori dalle pagine del sito.** L'evento ogni 15 minuti si controlla nell'amministrazione e dopo il salvataggio, non più a ogni pagina (due query in meno); se tutti i collegamenti si spengono lo toglie il giro stesso.
- **Risposta di Google senza indirizzo di reindirizzamento:** errore chiaro invece di "A valid URL was not provided".
- **Eventi con la fine prima dell'inizio** (errore di battitura): restano in programma fino all'inizio, invece di sparire.
- **Copia delle impostazioni del Customizer** (`inc/migrate.php`): segnata come fatta anche se nessun tema ha un logo; prima rientrava a ogni pagina dell'amministrazione e sovrascriveva le modifiche fatte nel Customizer del child. Finché non è fatta, le impostazioni della fonte si leggono una volta per richiesta.
- **Pulizia del codice.** Articoli e pagine con colonna laterale usano un solo modello (`vv_two_column_template()`), le categorie "vere" di un articolo una sola funzione (`vv_post_categories()`); breadcrumb, traduzioni, SVG e opzioni del modulo dei corsi calcolati una volta per richiesta.

## Versione 0.9.2: consensi di Forminator leggibili nel tema scuro

- **Testo dei consensi.** Nei moduli Forminator il testo accanto alle caselle (Regolamento, Iscrizione a socio, Privacy) sta in un `<p>` che lo stile del plugin colora di nero con un selettore proprio: sul blu del tema scuro risultava illeggibile. Ora segue il colore del testo del tema, come le etichette.
- **Altri neri fissi.** Stessa correzione per prefissi e suffissi dei campi, prefisso telefonico, voce scelta nelle tendine, campi bloccati, nome e peso dei file caricati (`assets/css/plugins.css`).

## Versione 0.9.1: ID del foglio nel pannello

- **`VV_SOCI_FOGLIO` e `VV_CORSI_FOGLIO`.** L'ID del foglio Google passa dallo script al tab Configurazione di Strumenti → Esporta iscrizioni; si incolla l'indirizzo del foglio dalla barra del browser o il solo ID (`vv_sheets_id_foglio()`). Il sito lo manda all'app web a ogni chiamata, accanto al codice segreto: gli script si incollano così com'è, con `ID_FOGLIO` vuoto, e per passare da una copia di prova al foglio vero basta cambiare il campo, senza ripubblicare nulla.
- **Ordine di scelta nello script** (`foglio_()`): ID mandato dal sito, poi `ID_FOGLIO` se compilato, poi il foglio a cui lo script è collegato. Messaggi chiari se l'ID manca o il foglio è irraggiungibile per l'account che ha pubblicato lo script.
- **Verifica con il nome del foglio.** "Salva e verifica il collegamento" riporta il foglio raggiunto ("risponde il foglio «PROVA Iscrizione Corsi»"); con uno script della 0.9.0 la pagina invita a pubblicare la versione nuova.
- **Sicurezza.** Il codice segreto resta l'unica chiave: chi lo conosce può indicare un qualsiasi foglio modificabile dall'account che pubblica lo script. Va custodito come una password.
- **Aggiornamento dalla 0.9.0.** Incollare i due script dal tab *Script Apps Script* (con `ID_FOGLIO` vuoto) e pubblicare per ciascuno Gestisci deployment → matita → Nuova versione; poi scrivere l'indirizzo dei fogli in `VV_SOCI_FOGLIO` e `VV_CORSI_FOGLIO` e premere "Salva e verifica". Finché lo script pubblicato resta quello vecchio, vale il suo `ID_FOGLIO`.

## Versione 0.9.0: Strumenti → Esporta iscrizioni

- **Configurazione dal pannello, senza `wp-config.php`.** Strumenti → Fogli Google diventa **Strumenti → Esporta iscrizioni** (`tools.php?page=vv-iscrizioni`; il vecchio indirizzo `page=vv-sheets` porta lì), con quattro tab: *Configurazione*, *Iscrizioni soci*, *Iscrizioni corsi*, *Script Apps Script*. Indirizzo, codice, modulo e data di partenza di ogni foglio si scrivono nel tab Configurazione (`VV_SOCI_URL`, `VV_SOCI_TOKEN`, `VV_SOCI_FORM`, `VV_SOCI_DAL`, `VV_CORSI_URL`, `VV_CORSI_TOKEN`, `VV_CORSI_FORM`, `VV_CORSI_DAL`) e restano nel database (opzioni `vv_soci_impostazioni`, `vv_corsi_impostazioni`). Il tab contiene la guida alla compilazione e il pulsante **Salva e verifica il collegamento**, che manda al foglio una richiesta vuota e conferma indirizzo e codice senza scrivere nulla. Il codice salvato resta nascosto: un campo vuoto lo conserva.
- **Costanti di `wp-config.php` ignorate.** `VV_SHEETS_*` e `VV_CORSI_*` non contano più; la pagina segnala quelle ancora definite, che si possono togliere. Dopo l'aggiornamento i collegamenti restano spenti finché URL e codice entrano nel pannello.
- **Nomi coerenti: `VV_SHEETS` diventa `VV_SOCI`.** Il prefisso `vv_sheets_` resta al motore comune (invio, stato, cron); ciò che riguarda i soci passa a `vv_soci_` (`vv_soci_colonne()` e filtro omonimo, `vv_soci_tab()`, `vv_soci_riga()`, `vv_soci_righe()`), come i corsi a `vv_corsi_`. Opzioni di stato: `vv_soci_ultimo`, `vv_soci_stato`, `vv_corsi_ultimo`, `vv_corsi_stato`, `vv_corsi_inizio`. I valori della 0.8 (`vv_sheets_ultimo`…) passano ai nomi nuovi una volta sola (`vv_sheets_migra_opzioni()`): il punto raggiunto resta quello, nessun reinvio.
- **Iscrizioni soci e Iscrizioni corsi** in due tab separati: stato, prova a secco, colonne, "Invia adesso", "Ricomincia".
- **Script Apps Script da copiare.** Gli script stanno ora in `inc/apps-script/` (`albo-soci.gs`, `iscrizione-corsi.gs`; prima in `docs/`, che resta fuori dallo zip del tema): il tab *Script Apps Script* ne mostra il testo con il pulsante **Copia lo script**.
- **Corsi: la mappa corso → tab passa al foglio.** Il sito manda una riga per ogni corso scelto, con il testo dell'opzione nella colonna "Corso"; lo script sceglie il tab leggendo il tab **`configurazioni`** del foglio. Spariscono `vv_corsi_tabs()`, `vv_corsi_tab_nomi()`, il confronto approssimato dei nomi e `TAB_MODELLO`. **Lo script dei corsi va incollato di nuovo e pubblicato** (Gestisci deployment → Nuova versione).

### Tab "configurazioni" del foglio dei corsi

Per convenzione il foglio "Iscrizione Corsi" contiene un tab chiamato `configurazioni`:

| | A | B |
|---|---|---|
| 1 | Tab | Corso |
| 2 | `lettura-ad-alta-voce-2026` | Adulti: Laboratorio di lettura ad alta voce corale, ottobre-dicembre 2026 (martedì 18.30-20.30) |
| 3 | `tecnica-attoriale-2026` | Adulti: Laboratorio di tecniche attoriali, ottobre-dicembre 2026 (giovedì 18.30-20.30) |
| 4 | `messa-in-scena-2027` | Adulti: Laboratorio di messa in scena, gennaio-giugno 2027 (giovedì 18.30-20.30) |
| 5 | `gioco-del-teatro-2026` | Bambini (elementari): Il gioco del teatro, ottobre-dicembre 2026 |

- Colonna A: nome del tab; colonna B: nome esteso del corso, cioè il testo dell'opzione del modulo. Maiuscole, accenti e spazi doppi contano poco (`normalizza_()` nello script, `vv_corsi_normalizza()` nel sito); il resto deve coincidere. Più opzioni possono puntare allo stesso tab.
- Dalla 0.9.4 un tab vuoto riceve le intestazioni del sito (Rif. in A, poi i campi del modulo; in "Da smistare" anche "Corso"). Il tab modello è sparito.
- Corso assente da `configurazioni`, o tab inesistente: la riga va in "Da smistare", con il testo nella colonna "Corso". Se `configurazioni` manca, lo script lo crea vuoto al primo invio e tutto va in "Da smistare": nessuna iscrizione persa.
- **Nuovo corso o opzione riscritta:** un tab nel foglio e una riga in `configurazioni`, senza toccare il sito. Il tab *Iscrizioni corsi* elenca le opzioni del modulo con il tab in cui finiscono e segnala in grassetto quelle dirette a "Da smistare"; lo script espone la mappa con l'azione di sola lettura `mappa`, che il sito tiene in copia per 5 minuti (pulsante "Rileggi il tab configurazioni").

### Aggiornamento dalla 0.8

1. Prima di caricare il tema: annotare `VV_SHEETS_URL`, `VV_SHEETS_TOKEN`, `VV_CORSI_URL`, `VV_CORSI_TOKEN` (ed eventuali `_FORM`, `_DAL`) da `wp-config.php`.
2. Nel foglio dei corsi: creare il tab `configurazioni` come nella tabella sopra.
3. Nel progetto Apps Script dei corsi: incollare il testo dal tab *Script Apps Script*, conservando l'`ID_FOGLIO`, salvare, poi Gestisci deployment → matita → Nuova versione. URL e codice restano gli stessi. Lo script dei soci cambia solo nei commenti e nel testo del registro: ripubblicarlo è facoltativo.
4. Caricare il tema; Strumenti → Esporta iscrizioni → Configurazione: inserire i valori annotati (i vecchi `VV_SHEETS_*` vanno nei campi `VV_SOCI_*`) e premere "Salva e verifica il collegamento" per ciascun foglio.
5. Tab *Iscrizioni corsi*: controllare che ogni opzione abbia il suo tab e che la prova a secco torni; poi togliere le costanti da `wp-config.php`.

## Versione 0.8.0: iscrizioni ai corsi nel foglio Google

- **Iscrizioni ai corsi nel foglio "VivaVoce / Iscrizione Corsi"** (`inc/sheets.php`, `docs/apps-script-iscrizione-corsi.gs`): ogni iscrizione del modulo "Iscrizione ai corsi 2026-2027" (n. 1617) arriva nel tab del corso scelto, una riga per corso. Vedi *Foglio dei corsi*.
- **Un solo meccanismo per più fogli.** Le funzioni di `inc/sheets.php` ricevono l'id del collegamento (`soci`, `corsi`; elenco in `vv_sheets_collegamenti()`, filtrabile). Il foglio dei soci funziona come prima: stesse costanti, stesse opzioni (`vv_sheets_ultimo`, `vv_sheets_stato`), nessun reinvio.
- **Strumenti → Fogli Google** (prima *Foglio soci*): una sezione per collegamento, con stato, prova a secco, "Invia adesso" e "Ricomincia". Per i corsi mostra anche i campi del modulo e il tab in cui finisce ogni opzione.
- **Informativa privacy** estesa ai corsi: `docs/informativa-fogli-google.md` (prima `informativa-albo-soci.md`).

### Foglio dei corsi

> Dalla 0.9.0 configurazione, script e tab dei corsi funzionano come descritto in *Versione 0.9.0*; quanto segue vale per la 0.8.

Le iscrizioni del modulo n. 1617 arrivano nel foglio "VivaVoce / Iscrizione Corsi", un tab per corso (`lettura-ad-alta-voce-2026`, `tecnica-attoriale-2026`, `messa-in-scena-2027`, `gioco-del-teatro-2026`). Lo script scrive Nome, Cognome, Codice Fiscale, Email, Cellulare, Ricevuta, Regolamento, Socio, Privacy e Note (A–J); dovuto, quota associativa e pagamento (K–N) restano alla segreteria e lo script li lascia intatti. In O compare "Rif." (`F1617-` + numero dell'iscrizione), che evita i doppioni e si può nascondere.

- **Più corsi in un'iscrizione:** una riga in ciascun tab, con lo stesso Rif.
- **Tab del corso.** Il valore scelto nel modulo trova il suo tab anche scritto in altro modo: "Lettura ad alta voce" → `lettura-ad-alta-voce-2026` (minuscole, senza accenti, trattini al posto degli spazi, anno finale facoltativo; un anno diverso da quello del tab non combacia). I casi che il confronto non risolve vanno in `vv_corsi_tabs()` (valore del modulo => nome del tab). Un tab esistente ma vuoto riceve le intestazioni del tab modello (`TAB_MODELLO` nello script).
- **Corso senza tab:** la riga va in "Da smistare", creato dallo script la prima volta con le intestazioni del modello e una colonna "Corso". L'invio prosegue; lo script non crea altri tab.
- **Si parte dalle iscrizioni nuove.** Alla prima apertura di Strumenti → Fogli Google (o al primo giro di wp-cron) con le costanti in `wp-config.php`, il sito fissa come punto di partenza l'iscrizione più recente già presente (opzione `vv_sheets_corsi_inizio`). "Ricomincia" torna a quel punto. Opzioni: `vv_sheets_corsi_ultimo`, `vv_sheets_corsi_stato`.
- **Corrispondenza colonne → campi:** `vv_corsi_colonne()`; campo del corso: `vv_corsi_campo()`; nomi dei tab: `vv_corsi_tab_nomi()`. Tutti filtrabili. Gli id vengono dal modulo pubblicato su `/iscrizione-corso/`: Nome `name-1`, Cognome `name-2`, Codice Fiscale `text-1`, Email `email-1`, Cellulare `phone-2`, Ricevuta `upload-1`, Regolamento `consent-1`, Socio `consent-2`, Privacy `consent-3`, Note `textarea-1`; corso `checkbox-1` (caselle multiple). Le quattro opzioni del corso, lunghe e con date e orari, sono elencate per intero in `vv_corsi_tabs()`. Se un filtro svuota `vv_corsi_campo()` il collegamento resta fermo; le iscrizioni arrivate nel frattempo partono appena è pronto. Strumenti → Fogli Google → *Campi del modulo n. 1617* resta utile per un controllo dopo una modifica del modulo.
- **Installazione.**
  1. Nel foglio dei corsi: Estensioni → Apps Script, incollare `docs/apps-script-iscrizione-corsi.gs`, eseguire `creaToken`, poi Esegui il deployment → Nuovo deployment → App web, "Esegui come: me", "Chi ha accesso: chiunque".
  2. In `wp-config.php`:

     ```php
     define( 'VV_CORSI_URL', 'https://script.google.com/macros/s/…/exec' );
     define( 'VV_CORSI_TOKEN', '…' );
     // facoltativi: define( 'VV_CORSI_FORM', 1617 ); define( 'VV_CORSI_DAL', '2026-09-01' );
     ```

  3. Strumenti → Fogli Google: controllare i campi del modulo e la prova a secco. Conviene provare prima su una copia del foglio. Procedura completa di collaudo: `docs/attivazione-corsi.md`.
  4. Togliere al foglio il permesso "Chiunque abbia il link": lo script scrive con l'account di chi lo pubblica.
- **Nuovo corso o opzione riscritta:** un tab nuovo nel foglio, il suo nome in `vv_corsi_tab_nomi()` e il testo esatto dell'opzione del modulo in `vv_corsi_tabs()`. Un'opzione modificata nel modulo senza questa voce finisce in "Da smistare", con il testo nella colonna "Corso".

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

> Dalla 0.9.0 indirizzo e codice si scrivono in Strumenti → Esporta iscrizioni (`VV_SOCI_URL`, `VV_SOCI_TOKEN`), non più in `wp-config.php`; lo script è in `inc/apps-script/albo-soci.gs`. Vedi *Versione 0.9.0*.

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
