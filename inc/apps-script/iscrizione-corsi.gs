/**
 * VivaVoce: riceve dal sito le iscrizioni ai corsi (modulo Forminator "Iscrizione ai corsi
 * 2026-2027") e le scrive nel foglio "VivaVoce / Iscrizione Corsi", nel tab del corso scelto.
 *
 * - Il tab di ogni corso si legge dal tab "configurazioni" del foglio: riga 1 intestazioni (Tab,
 *   Corso), dalla riga 2 in colonna A il nome del tab e in colonna B il nome esteso del corso, cioè
 *   il testo dell'opzione del modulo. Maiuscole, accenti e spazi doppi contano poco. Per un corso
 *   nuovo basta un tab e una riga in "configurazioni": lo script resta com'è.
 * - Ogni valore va nella colonna con la stessa intestazione (riga 1). Le colonne della segreteria
 *   (dovuto, quota associativa, pagamento) restano vuote: lo script scrive solo dove ha un valore.
 * - Un tab del corso ancora vuoto riceve prima le intestazioni del modello: il primo tab elencato in
 *   "configurazioni" con la riga 1 compilata.
 * - Un corso assente da "configurazioni", o con un tab inesistente, va in "Da smistare", creato con
 *   le intestazioni del modello più "Corso". Lo script non crea altri tab.
 * - La colonna "Rif." (numero dell'iscrizione sul sito, nascondibile) evita i doppioni: una riga
 *   già scritta nel tab non viene ripetuta, anche se il sito la rimanda. Chi sceglie due corsi ha
 *   una riga in ciascun tab, con lo stesso Rif.
 *
 * Installazione (una volta), istruzioni complete nel sito: Strumenti → Esporta iscrizioni.
 * 1. In Google Drive: Nuovo → Altro → Google Apps Script (progetto autonomo, con l'ID del foglio in
 *    ID_FOGLIO), oppure dal foglio: Estensioni → Apps Script. Incolla questo file al posto del codice
 *    che c'è, salva.
 * 2. Scegli la funzione "creaToken" in alto e premi Esegui (la prima volta chiede le autorizzazioni):
 *    nel registro compare il codice segreto da mettere nel sito come VV_CORSI_TOKEN.
 * 3. Esegui il deployment → Nuovo deployment → tipo "App web"; Esegui come: me; Chi ha accesso:
 *    chiunque. L'URL dell'app web (finisce con /exec) va nel sito come VV_CORSI_URL.
 * 4. Lo script scrive con l'account di chi lo pubblica: il foglio può restare condiviso solo con
 *    account nominativi, senza "Chiunque abbia il link".
 * Se modifichi questo codice: Esegui il deployment → Gestisci deployment → modifica → Nuova versione
 * (l'URL resta lo stesso).
 */

// Script dentro il foglio (Estensioni → Apps Script): lasciare vuoto. Script a parte, creato da
// script.google.com nel proprio Drive: l'ID del foglio, cioè il pezzo dell'indirizzo fra /d/ e /edit.
var ID_FOGLIO = '';

var CONFIGURAZIONI = 'configurazioni';
var DA_SMISTARE = 'Da smistare';
var CORSO = 'Corso';
var RIF = 'Rif.';

function doPost(e) {
  var dati;
  try {
    dati = JSON.parse(e.postData.contents);
  } catch (err) {
    return risposta_({ ok: false, errore: 'richiesta non leggibile' });
  }
  var token = PropertiesService.getScriptProperties().getProperty('TOKEN');
  if (!token || dati.token !== token) {
    return risposta_({ ok: false, errore: 'codice segreto sbagliato' });
  }
  var lock = LockService.getScriptLock();
  if (!lock.tryLock(30000)) {
    return risposta_({ ok: false, errore: 'foglio occupato, riprovo dopo' });
  }
  try {
    var ss = ID_FOGLIO ? SpreadsheetApp.openById(ID_FOGLIO) : SpreadsheetApp.getActiveSpreadsheet();
    var righe = dati.righe || [];
    var conf = leggiConfigurazioni_(ss, righe.length > 0);
    if (dati.azione === 'mappa') {
      return risposta_({ ok: true, mappa: conf.originale, tab: nomiTab_(ss) });
    }
    var fatti = [];
    righe.forEach(function (riga) {
      if (!riga[RIF]) {
        return;
      }
      scriviRiga_(tabCorso_(ss, conf, String(riga[CORSO] || '')), riga);
      if (fatti.indexOf(String(riga[RIF])) < 0) {
        fatti.push(String(riga[RIF]));
      }
    });
    SpreadsheetApp.flush();
    return risposta_({ ok: true, rif: fatti });
  } catch (err) {
    return risposta_({ ok: false, errore: String(err && err.message || err) });
  } finally {
    lock.releaseLock();
  }
}

/**
 * Il tab "configurazioni": corso (normalizzato) → tab, più l'elenco dei tab nell'ordine in cui
 * compaiono. Se manca e ci sono righe da scrivere lo crea con le intestazioni: finché resta vuoto
 * le righe vanno in "Da smistare".
 */
function leggiConfigurazioni_(ss, crea) {
  var conf = { mappa: {}, originale: {}, ordine: [] };
  var tab = ss.getSheetByName(CONFIGURAZIONI);
  if (!tab && !crea) {
    return conf;
  }
  if (!tab) {
    tab = ss.insertSheet(CONFIGURAZIONI, ss.getNumSheets());
    tab.getRange(1, 1, 1, 2).setValues([['Tab', 'Corso']]).setFontWeight('bold');
    tab.setFrozenRows(1);
  }
  var ultima = tab.getLastRow();
  if (ultima < 2) {
    return conf;
  }
  tab.getRange(2, 1, ultima - 1, 2).getValues().forEach(function (r) {
    var nome = String(r[0]).trim();
    var corso = String(r[1]).trim();
    if (!nome || !corso) {
      return;
    }
    conf.mappa[normalizza_(corso)] = nome;
    conf.originale[corso] = nome;
    if (conf.ordine.indexOf(nome) < 0) {
      conf.ordine.push(nome);
    }
  });
  return conf;
}

/** Nomi dei tab del foglio. */
function nomiTab_(ss) {
  return ss.getSheets().map(function (s) {
    return s.getName();
  });
}

/** Il tab da cui copiare le intestazioni: il primo di "configurazioni" con la riga 1 compilata. */
function tabModello_(ss, conf) {
  for (var i = 0; i < conf.ordine.length; i++) {
    var tab = ss.getSheetByName(conf.ordine[i]);
    if (tab && tab.getLastColumn() > 0) {
      return tab;
    }
  }
  var altri = ss.getSheets().filter(function (s) {
    return s.getName() !== DA_SMISTARE && s.getName() !== CONFIGURAZIONI && s.getLastColumn() > 0;
  });
  if (!altri.length) {
    throw new Error('nessun tab con le intestazioni da copiare');
  }
  return altri[0];
}

/**
 * Il tab del corso secondo "configurazioni". Se esiste ma è vuoto riceve le intestazioni del
 * modello; se il corso manca o il tab non esiste la riga va in "Da smistare", creato la prima volta
 * con le intestazioni del modello e la colonna "Corso".
 */
function tabCorso_(ss, conf, corso) {
  var nome = conf.mappa[normalizza_(corso)];
  var tab = nome && nome !== DA_SMISTARE && nome !== CONFIGURAZIONI ? ss.getSheetByName(nome) : null;
  if (tab) {
    if (tab.getLastColumn() === 0) {
      copiaIntestazioni_(tabModello_(ss, conf), tab);
    }
    return tab;
  }
  tab = ss.getSheetByName(DA_SMISTARE);
  if (!tab) {
    tab = ss.insertSheet(DA_SMISTARE, ss.getNumSheets());
  }
  if (tab.getLastColumn() === 0) {
    copiaIntestazioni_(tabModello_(ss, conf), tab);
  }
  var intestazioni = leggiIntestazioni_(tab);
  if (indice_(intestazioni, CORSO) < 0) {
    tab.getRange(1, primaLibera_(intestazioni) + 1).setValue(CORSO);
  }
  return tab;
}

/** Copia la riga delle intestazioni del modello, senza "Rif." (lo script la aggiunge da sé). */
function copiaIntestazioni_(modello, tab) {
  var intestazioni = leggiIntestazioni_(modello);
  var fino = indice_(intestazioni, RIF) >= 0 ? indice_(intestazioni, RIF) : primaLibera_(intestazioni);
  if (fino > 0) {
    modello.getRange(1, 1, 1, fino).copyTo(tab.getRange(1, 1, 1, fino));
  }
  tab.setFrozenRows(1);
}

/**
 * Scrive una riga sotto le altre, valore per valore nella colonna con la stessa intestazione. Le
 * colonne senza un valore dal sito non vengono toccate. In un tab con la colonna "Corso" il doppione
 * è la stessa coppia Rif. + Corso, altrove lo stesso Rif.
 */
function scriviRiga_(tab, riga) {
  var intestazioni = leggiIntestazioni_(tab);
  var colRif = indice_(intestazioni, RIF);
  if (colRif < 0) {
    colRif = primaLibera_(intestazioni);
    tab.getRange(1, colRif + 1).setValue(RIF);
    intestazioni[colRif] = RIF;
  }
  var colCorso = indice_(intestazioni, CORSO);
  var ultima = tab.getLastRow();
  if (ultima > 1) {
    var presenti = tab.getRange(2, 1, ultima - 1, intestazioni.length).getValues();
    for (var i = 0; i < presenti.length; i++) {
      if (String(presenti[i][colRif]) === String(riga[RIF])
        && (colCorso < 0 || String(presenti[i][colCorso]) === String(riga[CORSO] || ''))) {
        return;
      }
    }
  }
  var nuova = Math.max(ultima, 1) + 1;
  Object.keys(riga).forEach(function (chiave) {
    if (chiave === '_tab') {
      return;
    }
    var col = indice_(intestazioni, chiave);
    var valore = testo_(riga[chiave]);
    if (col < 0 || valore === '') {
      return;
    }
    // Come testo: codici fiscali e telefoni restano come sono scritti (niente zeri persi).
    tab.getRange(nuova, col + 1).setNumberFormat('@').setValue(valore);
  });
}

/** Intestazioni della riga 1, ripulite dagli spazi. */
function leggiIntestazioni_(tab) {
  var colonne = Math.max(tab.getLastColumn(), 1);
  return tab.getRange(1, 1, 1, colonne).getValues()[0].map(function (h) {
    return String(h).trim();
  });
}

/** Prima colonna libera dopo l'ultima intestazione compilata (da 0). */
function primaLibera_(intestazioni) {
  for (var i = intestazioni.length - 1; i >= 0; i--) {
    if (intestazioni[i] !== '') {
      return i + 1;
    }
  }
  return 0;
}

/**
 * Valore come testo puro. I dati arrivano da un modulo pubblico: un nome che comincia con "="
 * diventerebbe una formula. L'apostrofo davanti, che il foglio non mostra, lo tiene testo.
 */
function testo_(valore) {
  var s = valore === null || valore === undefined ? '' : String(valore);
  return /^\s*=/.test(s) ? "'" + s : s;
}

function indice_(intestazioni, nome) {
  for (var i = 0; i < intestazioni.length; i++) {
    if (uguali_(intestazioni[i], nome)) {
      return i;
    }
  }
  return -1;
}

/** Intestazioni uguali senza badare a maiuscole e spazi ("CODICE FISCALE" = "Codice Fiscale"). */
function uguali_(a, b) {
  return String(a).trim().toLowerCase() === String(b).trim().toLowerCase();
}

/**
 * Testo di un corso ridotto per il confronto: senza accenti, minuscole, spazi compressi. Il sito fa
 * lo stesso (vv_corsi_normalizza).
 */
function normalizza_(testo) {
  return String(testo || '').normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase().replace(/\s+/g, ' ').trim();
}

function risposta_(oggetto) {
  return ContentService.createTextOutput(JSON.stringify(oggetto)).setMimeType(ContentService.MimeType.JSON);
}

/** Crea il codice segreto (da eseguire una volta) e lo mostra nel registro di esecuzione. */
function creaToken() {
  var token = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '');
  PropertiesService.getScriptProperties().setProperty('TOKEN', token);
  Logger.log('VV_CORSI_TOKEN: ' + token);
}
