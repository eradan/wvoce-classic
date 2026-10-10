# Tema child VivaVoce

Tema child di **Sydney 2.71** per wvoce.it, aspetto "classico" vicino al sito originale. Qui la guida d'uso; la storia delle versioni è in `CHANGELOG.md`.

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

## Messa in produzione

1. **Backup** di file e database dal pannello dell'hosting.
2. **Carica il tema**: *Aspetto → Temi → Aggiungi → Carica* con lo zip (`bin/build-zip.sh`, vedi *Sviluppo*) e scegli *Sostituisci l'attuale con quello caricato*. Lo zip deve avere lo stesso nome della cartella sul server (`wvoce-classic`): con un altro nome si crea un secondo tema senza impostazioni. Il padre è `sydney` (minuscolo: `Template: sydney` in `style.css`).
3. **Attiva il child.** Le impostazioni del Customizer vengono copiate da sole (`inc/migrate.php`). Il "CSS aggiuntivo" di Sydney è già riportato in `style.css`.

**Tornare indietro:** si riattiva il tema precedente, le cui impostazioni restano intatte.

## Cosa preparare

| Dove | Cosa |
|---|---|
| Aspetto → Widget | Blocco contatti in **Footer 1**, social in **Footer 2**, `[vv_dati_fiscali]` in **Footer 3** (Customizer → Footer: 3 colonne). In cima a ogni widget un blocco Titolo (Contatti, Seguici, Dati fiscali). I blocchi `side-contacts` già presenti vanno bene: le icone Font Awesome (telefono, e-mail, Facebook, Instagram…) il tema le mette accanto al testo; in Footer 3 il testo con CF, P.IVA e IBAN va sostituito dallo shortcode, che aggiunge i pulsanti "Copia". |
| Tutte le pagine | **Immagine in evidenza** orizzontale, larga almeno 1600px, col soggetto al centro (viene ritagliata 16:9 e, su telefono, 4:3). Per non mostrarla in testa a una pagina: riquadro "VivaVoce" → *Senza immagine in testa* (resta per la card nella pagina madre). Dalle gallerie in fondo alle pagine togli le foto vecchie. |
| Customizer → VivaVoce | Titolo (per screen reader e motori di ricerca), pulsanti e illustrazione dell'hero della home; CF, P.IVA, IBAN. |
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

Il pannello laterale di Sydney è ridisegnato: una voce per riga, frecce tonde che aprono i sottomenu (e si girano quando sono aperti), il sottomenu della sezione in cui ci si trova già aperto, pagina oscurata dietro. Si chiude con la X, toccando fuori o con Esc. Le regole del menu desktop valgono solo nell'header (`.main-header #mainnav`), quelle del pannello solo in `.sydney-offcanvas-menu`.

## Tipografia, spazi e colori

Tutto in `assets/css/tokens.css`: per cambiare le proporzioni si modificano solo i token, non le singole regole.

- **Caratteri** (`--vv-fs-*`), fluidi fra 360px e 1200px di larghezza (telefono → desktop): meta, breadcrumb e date 14 · testi secondari e footer 15 · corpo del testo 17 → 18 (interlinea 1,65) · titoli card 19 → 21 (archivio 16–17) · h4 18 → 20 · h3 20 → 24 · h2 24 → 30 · titoli di sezione in maiuscolo 24 → 32 · titolo di pagina 30 → 44. Prima erano i valori del Customizer di Sydney (corpo 16, h1 52, h2 42), che sovrascriviamo.
- **Spazi** (`--vv-s-1…5`): 8 · 16 · 24 · 40 · 40 → 64. Breadcrumb → titolo 16, titolo → immagine o testo 24, immagine → testo 40, fra le sezioni 40 → 64, fra le card 30 (20 su telefono). Un titolo nel testo ha 1,8em sopra e 0,6em sotto, così sta vicino al paragrafo che introduce.
- **Colori**: due livelli. *Marchio* (`--vv-giallo`, `--vv-turchese`, `--vv-rosso`, `--vv-inchiostro`, `--vv-bianco`, `--vv-perla`), uguali in ogni tema. *Ruoli* (`--vv-fondo`, `--vv-fondo-card`, `--vv-riquadro`, `--vv-testo`, `--vv-testo-2`, `--vv-link`, `--vv-fondo-attivo`, `--vv-header-testo`, `--vv-bordo`, `--vv-ombra`…), gli unici che un tema ridefinisce (il tema scuro: vedi *Versione 0.7* in `CHANGELOG.md`). Nei fogli del tema e in `style.css` non ci sono colori scritti a mano. Testo sempre ad almeno 4,5:1: `--vv-link` (#007a87, 5,1:1) per link e titoli delle card, `--vv-testo-riquadro` (#384a5e, 6,8:1) per il testo secondario sul grigio. Il turchese #00b2c3 e il giallo restano come fondi: sopra ci va il testo inchiostro.

## Breadcrumb

Automatiche su tutte le pagine tranne la home ("Home › Chi siamo › Statuto e organigramma"). Seguono il menu principale; per gli articoli usano la pagina collegata alla loro categoria (riquadro VivaVoce della pagina → "Categorie collegate") oppure la categoria del menu (es. Rassegna stampa). Gli articoli della categoria **Soci** portano a Chi siamo anche senza impostare nulla. Includono i dati strutturati BreadcrumbList per i motori di ricerca.

## Articoli e pagine dei soci

- Articolo: immagine in evidenza in testa (spegnibile articolo per articolo: riquadro "VivaVoce: date e luogo" → *Senza immagine in testa*), testo a sinistra, a destra la scheda **In breve** (quando, dove, categorie) e **Da non perdere** (articoli in evidenza); in fondo precedente/successivo come due card.
- Soci fondatori (modello "Page builder ready"): il child aggiunge breadcrumb e titolo e, in fondo, **Gli altri soci fondatori**.

## Shortcode

- `[vv_cards categoria="slug,slug" numero="6" variante="evento|persona|stampa|pagina" colonne="3" ordine="data|titolo" ids="1,2"]`: non ripete articoli già mostrati più su nella stessa pagina (`doppioni="si"` per permetterlo).
  - `in_evidenza="si"`: solo gli articoli con la spunta **In evidenza** (lo stesso campo del vecchio plugin Featured Post Creative, quindi le spunte già messe restano valide), nel riquadro "VivaVoce: date e luogo"; nell'elenco articoli c'è la colonna ★. Si somma alle altre condizioni, `periodo` compreso.
  - `periodo="futuro"` o `periodo="dal-3-mesi"`: eventi dalle loro date (vedi *Versione 0.6* in `CHANGELOG.md`).
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

- Righe di trattini bassi `________` → riga sottile di separazione.
- Lettere "decorative" incollate dai social (𝕍𝕠𝕔𝕖, 𝐁𝐨𝐥𝐝…) → testo normale in grassetto: nei font del sito comparivano come □.
- Font e corpi incollati da Word o e-mail → quelli del sito.
- Titoli colorati a mano (per esempio giallo su bianco) → inchiostro.

- Voci di elenco rimaste fuori da un `</ul>` (HTML rotto, per esempio in Formazione e laboratori) → riavvolte in un elenco. Il contenuto salvato non cambia; correggerlo nell'editor resta comunque una buona idea.

## Sviluppo

- **Test:** `php tests/run.php` controlla senza WordPress le funzioni pure: codice fiscale, righe e tab dei fogli dei soci e dei corsi, impostazioni e chiamate all'app web, date, slug, `meta_query`, lettere decorative. Senza PHP sulla macchina: `docker run --rm -v "$PWD":/app -w /app php:8.3-cli php tests/run.php`. Gli script Apps Script si provano con `node tests/apps-script.js`, su un foglio Google finto in memoria.
- **Regole di scrittura:** `composer install`, poi `composer lint` (phpcs con WordPress-Extra e compatibilità PHP 7.4, regole in `phpcs.xml.dist`; il prefisso `vv_` è più corto dei tre caratteri che WPCS richiede, quindi quella regola è spenta). Nel codice le eccezioni motivate sono segnate con `phpcs:ignore`.
- **Zip del tema:** `bin/build-zip.sh` crea `build/wvoce-classic.zip` con dentro la cartella `wvoce-classic`, il nome che il tema ha sul server. Contiene l'ultimo commit; test, documenti e file di sviluppo restano fuori (`.gitattributes`).
- **GitHub Actions** (`.github/workflows/ci.yml`): a ogni push sintassi PHP e test, con PHP 7.4 e 8.3, test degli script Apps Script e phpcs.

## Struttura

```
style.css                 intestazione + CSS aggiuntivo ereditato da Sydney
functions.php             caricamento di CSS e JS, preload dei font, script del tema chiaro/scuro
inc/migrate.php           copia delle impostazioni del Customizer, costruttore header/footer spento
inc/setup.php             filtri su Sydney, traduzioni mancanti, credits del footer, Contact Form 7 dove serve
inc/contenuti.php         correzioni dei contenuti (trattini, lettere decorative, elenchi rotti, emoji esterne, gallerie)
inc/condivisione.php      meta description e anteprime Open Graph e Twitter
inc/sicurezza.php         nomi utente fuori da sitemap e API pubblica, XML-RPC spento
inc/customizer.php        pannello VivaVoce (hero, dati dell'associazione)
inc/meta.php              date e luogo degli eventi, "In evidenza", opzioni delle pagine
inc/cards.php             card, griglie, date degli eventi, eventi per periodo
inc/shortcodes.php        [vv_cards], [vv_sottopagine], [vv_elenco], [vv_sostienici], [vv_dati_fiscali]
inc/home.php              hero della home al posto dello slider
inc/archive.php           archivi a card, 12 per pagina
inc/breadcrumbs.php       percorso in alto, dati strutturati, voce di menu della sezione corrente
inc/single.php            articolo con colonna laterale, navigazione, altri soci fondatori
inc/page.php              pagine con la colonna "In questa sezione", schemi a due colonne e con blocco finale
inc/redirects.php         301 degli indirizzi spariti
inc/forminator.php        codice fiscale e iscrizione a socio nei moduli Forminator
inc/sheets.php            iscrizioni dei soci e dei corsi nei fogli Google
inc/sheets-admin.php      Strumenti → Esporta iscrizioni (configurazione, stato, prova a secco, script)
inc/apps-script/          script Apps Script dei fogli soci e corsi, da incollare nell'editor
template-parts/           card.php, home-hero.php
page-templates/           page-hub.php, page-elenco.php
post-templates/           post_full.php ("Page builder ready" dei soci fondatori)
assets/css/               tokens, base, layout, header, footer, components, home, pages, plugins, scuro
assets/js/vv.js           testata, switcher del tema, caroselli, menu mobile, pulsanti "Copia"
assets/fonts/             Poppins (self-hosted, licenza OFL)
assets/img/               livelli dell'hero, faccine e scritta del footer, mano del "torna su"
CHANGELOG.md              note di versione
docs/                     bozza di informativa privacy, piani di lavoro
tests/                    test senza WordPress (run.php) e senza Google (apps-script.js)
bin/build-zip.sh          zip del tema
```
