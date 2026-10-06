# Informativa privacy: domande di ammissione e foglio "Albo Soci"

Dalla versione 0.7.1 le domande del modulo "Iscrizione Socio" (Forminator n. 274) arrivano da sole nel foglio Google "VivaVoce / Albo Soci" (`inc/sheets.php`). Il trasferimento a un servizio esterno va dichiarato nell'informativa che accompagna il modulo e nella pagina privacy del sito. Il testo qui sotto è una **bozza da far verificare** da chi cura la privacy dell'associazione, ed eventualmente da un consulente: va adattato ai dati reali del titolare.

## Cosa passa al foglio

Dalla corrispondenza in `vv_sheets_colonne()`:

- **Dati anagrafici e di contatto:** tipologia, nome, cognome, codice fiscale, email, telefono, cellulare, indirizzo di residenza, genere, data, comune e provincia di nascita.
- **Pagamento della quota:** il link al file caricato nel modulo. Il file sta nella cartella `uploads` del sito, e il link vale per chiunque lo conosca.
- **Scelte e consensi:** attività di volontariato, consenso GDPR, presa visione di statuto e regolamento, autorizzazione alle comunicazioni, data di compilazione.
- **Dati tecnici:** il numero dell'iscrizione sul sito (colonna "Rif.").

Il passaggio avviene in HTTPS verso un'app web di Google Apps Script protetta da un codice segreto. Il foglio è accessibile solo agli account Google con cui è condiviso.

## Testo proposto per l'informativa del modulo

> **Dove conserviamo la tua domanda.** I dati che inserisci in questo modulo sono conservati sul sito dell'associazione e copiati in automatico nel registro delle domande di ammissione, un foglio di calcolo Google Sheets (Google Ireland Ltd., Gordon House, Barrow Street, Dublino 4, Irlanda) accessibile solo ai membri del Consiglio direttivo incaricati della gestione dei soci. Google agisce come responsabile del trattamento per conto dell'associazione, secondo i termini sul trattamento dei dati di Google Workspace. Eventuali trasferimenti verso gli Stati Uniti avvengono nel quadro dell'EU-US Data Privacy Framework, a cui Google aderisce, e delle clausole contrattuali tipo della Commissione europea.
>
> **Perché.** Per valutare la domanda di ammissione, tenere il libro dei soci previsto dallo statuto e dal Codice del Terzo settore e attivare la copertura assicurativa. La base giuridica è l'esecuzione del rapporto associativo (art. 6.1.b GDPR) e l'adempimento degli obblighi di legge (art. 6.1.c).
>
> **Per quanto tempo.** Per la durata del rapporto associativo e, dopo la sua fine, per il tempo richiesto dagli obblighi di legge e di tenuta dei libri sociali. La ricevuta del pagamento della quota è conservata per il tempo richiesto dagli obblighi contabili.
>
> **I tuoi diritti.** Puoi chiedere accesso, rettifica, cancellazione o limitazione dei dati e opporti al trattamento scrivendo a [indirizzo email del titolare]. Puoi anche proporre reclamo al Garante per la protezione dei dati personali (garanteprivacy.it).

## Da controllare nella gestione

- **Condivisione del foglio:** solo account nominativi, senza link pubblico ("Chiunque abbia il link").
- **Account di deployment:** lo script gira con l'account che lo pubblica ("Esegui come: me"). Meglio usare l'account dell'associazione, invece di quello personale di un volontario.
- **Registro dei trattamenti:** va aggiunta la voce "Google Sheets / Apps Script", con Google come responsabile.
- **Ricevute di pagamento:** i file caricati con Forminator hanno un indirizzo pubblico. Per tenerle riservate conviene verificare le opzioni sui caricamenti della versione installata di Forminator, oppure proteggere `wp-content/uploads/forminator/` con una regola del server.
- **Fine del collegamento:** basta togliere `VV_SHEETS_URL` da `wp-config.php` e archiviare il deployment dell'app web.
