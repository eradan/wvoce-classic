# Informativa privacy: iscrizioni nei fogli Google

Due moduli del sito copiano le iscrizioni in un foglio Google (plugin wvoce-iscrizioni):

- dalla versione 0.7.1 le domande del modulo "Iscrizione Socio" (Forminator n. 274) nel foglio "VivaVoce / Albo Soci";
- dalla versione 0.8.0 le iscrizioni del modulo "Iscrizione ai corsi 2026-2027" (n. 1617) nel foglio "VivaVoce / Iscrizione Corsi".

Il trasferimento a un servizio esterno va dichiarato nell'informativa che accompagna ciascun modulo e nella pagina privacy del sito. Il testo qui sotto è una **bozza da far verificare** da chi cura la privacy dell'associazione, ed eventualmente da un consulente: va adattato ai dati reali del titolare.

## Cosa passa al foglio "Albo Soci"

Dalla corrispondenza in `vv_soci_colonne()`:

- **Dati anagrafici e di contatto:** tipologia, nome, cognome, codice fiscale, email, telefono, cellulare, indirizzo di residenza, genere, data, comune e provincia di nascita.
- **Pagamento della quota:** il link al file caricato nel modulo. Il file sta nella cartella `uploads` del sito, e il link vale per chiunque lo conosca.
- **Scelte e consensi:** attività di volontariato, consenso GDPR, presa visione di statuto e regolamento, autorizzazione alle comunicazioni, data di compilazione.
- **Dati tecnici:** il numero dell'iscrizione sul sito (colonna "Rif.").

## Cosa passa al foglio "Iscrizione Corsi"

Dalle colonne del foglio dei corsi impostate nel plugin wvoce-iscrizioni (Strumenti → Iscrizioni → Campi e regole):

- **Dati anagrafici e di contatto:** nome, cognome, codice fiscale, email, cellulare.
- **Corso scelto:** il tab in cui finisce la riga; per i corsi senza tab, il nome del corso (colonna "Corso" del tab "Da smistare").
- **Pagamento:** il link alla ricevuta caricata nel modulo, con le stesse cautele della quota dei soci (vedi sotto).
- **Scelte e consensi:** presa visione del regolamento, iscrizione all'associazione (socio o no), consenso privacy, note libere.
- **Dati tecnici:** il numero dell'iscrizione sul sito (colonna "Rif.").

Le colonne aggiunte dalla segreteria si compilano a mano e restano fuori dal collegamento con il sito.

**Da verificare per i corsi con minori** (Gioco del teatro, 3-6 anni): se il modulo raccoglie i dati del bambino e del genitore, quali passano nel foglio e in quali colonne. L'informativa va rivolta a chi esercita la responsabilità genitoriale, e il consenso lo dà il genitore.

## Come avviene il passaggio

Il passaggio avviene in HTTPS verso un'app web di Google Apps Script protetta da un codice segreto. Il foglio è accessibile solo agli account Google con cui è condiviso.

## Testo proposto per l'informativa del modulo

> **Dove conserviamo la tua domanda.** I dati che inserisci in questo modulo sono conservati sul sito dell'associazione e copiati in automatico nel registro delle domande di ammissione, un foglio di calcolo Google Sheets (Google Ireland Ltd., Gordon House, Barrow Street, Dublino 4, Irlanda) accessibile solo ai membri del Consiglio direttivo incaricati della gestione dei soci. Google agisce come responsabile del trattamento per conto dell'associazione, secondo i termini sul trattamento dei dati di Google Workspace. Eventuali trasferimenti verso gli Stati Uniti avvengono nel quadro dell'EU-US Data Privacy Framework, a cui Google aderisce, e delle clausole contrattuali tipo della Commissione europea.
>
> *(Per il modulo dei corsi: "…copiati in automatico nel registro delle iscrizioni ai corsi, un foglio di calcolo Google Sheets (…) accessibile solo ai membri dell'associazione incaricati della segreteria dei corsi.")*
>
> **Perché.** Per valutare la domanda di ammissione, tenere il libro dei soci previsto dallo statuto e dal Codice del Terzo settore e attivare la copertura assicurativa. La base giuridica è l'esecuzione del rapporto associativo (art. 6.1.b GDPR) e l'adempimento degli obblighi di legge (art. 6.1.c). *(Per i corsi: "Per organizzare il corso, gestire le quote e comunicare con i partecipanti. La base giuridica è l'esecuzione dell'iscrizione al corso (art. 6.1.b GDPR).")*
>
> **Per quanto tempo.** *(Corsi: "Per la durata del corso e, dopo, per il tempo richiesto dagli obblighi contabili.")* Per la durata del rapporto associativo e, dopo la sua fine, per il tempo richiesto dagli obblighi di legge e di tenuta dei libri sociali. La ricevuta del pagamento della quota è conservata per il tempo richiesto dagli obblighi contabili.
>
> **I tuoi diritti.** Puoi chiedere accesso, rettifica, cancellazione o limitazione dei dati e opporti al trattamento scrivendo a [indirizzo email del titolare]. Puoi anche proporre reclamo al Garante per la protezione dei dati personali (garanteprivacy.it).

## Da controllare nella gestione

- **Condivisione del foglio:** solo account nominativi, senza link pubblico ("Chiunque abbia il link").
- **Account di deployment:** lo script gira con l'account che lo pubblica ("Esegui come: me"). Meglio usare l'account dell'associazione, invece di quello personale di un volontario.
- **Registro dei trattamenti:** va aggiunta la voce "Google Sheets / Apps Script", con Google come responsabile.
- **Ricevute di pagamento:** i file caricati con Forminator hanno un indirizzo pubblico. Per tenerle riservate conviene verificare le opzioni sui caricamenti della versione installata di Forminator, oppure proteggere `wp-content/uploads/forminator/` con una regola del server.
- **Tab "Da smistare"** (corsi): va svuotato a mano, spostando le righe nel tab giusto; resta un tab del registro come gli altri.
- **Fine del collegamento:** basta svuotare l'indirizzo dell'app web (soci o corsi) in Strumenti → Iscrizioni → Collegamenti e archiviare il deployment dell'app web.
