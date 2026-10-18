# Tema child VivaVoce

Tema child di **Sydney 2.71** per wvoce.it, aspetto "classico" vicino al sito originale. Qui la guida d'uso; la storia delle versioni è in `CHANGELOG.md`.

Le iscrizioni a socio e ai corsi (controlli sul codice fiscale, "Recupera i tuoi dati", copia nei fogli Google) sono del plugin **[wvoce-iscrizioni](https://github.com/eradan/wvoce-iscrizioni)**, configurato in Strumenti → Iscrizioni. Il tema ne adatta solo i colori (`assets/css/plugins.css`) e funziona anche senza.

## Struttura

```
style.css                 intestazione + CSS aggiuntivo ereditato da Sydney
functions.php             caricamento di CSS e JS, preload dei font, script del tema chiaro/scuro
inc/setup.php             filtri su Sydney, costruttore header/footer spento, traduzioni mancanti, credits del footer, Contact Form 7 dove serve
inc/contenuti.php         correzioni dei contenuti (trattini, lettere decorative, elenchi rotti, emoji esterne, gallerie)
inc/condivisione.php      meta description e anteprime Open Graph e Twitter
inc/dati-strutturati.php  dati strutturati schema.org: sito e associazione in home, eventi negli articoli
inc/email.php             modello HTML con il marchio per tutte le email, versione in solo testo, Message-ID del dominio
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
inc/iscrizioni.php        passaggio al plugin wvoce-iscrizioni della configurazione e dello stato delle iscrizioni (una volta)
template-parts/           card.php, home-hero.php
page-templates/           page-hub.php, page-elenco.php
post-templates/           post_full.php ("Page builder ready" dei soci fondatori)
assets/css/               tokens, base, layout, header, footer, components, home, pages, plugins, scuro
assets/js/vv.js           testata, switcher del tema, caroselli, menu mobile, pulsanti "Copia"
assets/fonts/             Poppins (self-hosted, licenza OFL)
assets/img/               livelli dell'hero, faccine e scritta del footer, mano del "torna su"
CHANGELOG.md              note di versione
docs/                     bozza di informativa privacy, piani di lavoro
tests/                    test senza WordPress (run.php)
bin/build-zip.sh          zip del tema
```
