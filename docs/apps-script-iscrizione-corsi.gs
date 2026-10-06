/**
 * VivaVoce: riceve dal sito le iscrizioni ai corsi (modulo Forminator "Iscrizione ai corsi
 * 2026-2027") e le scrive nel foglio "VivaVoce / Iscrizione Corsi", nel tab del corso scelto.
 *
 * - Ogni valore va nella colonna con la stessa intestazione (riga 1). Le colonne della segreteria
 *   (dovuto, quota associativa, pagamento) restano vuote: lo script scrive solo dove ha un valore.
 * - Un tab del corso ancora vuoto riceve prima le intestazioni del tab modello (TAB_MODELLO).
 * - Un corso senza tab va in "Da smistare", creato con le intestazioni del modello più "Corso".
 *   Lo script non crea altri tab: un nome sconosciuto finisce sempre lì.
 * - La colonna "Rif." (numero dell'iscrizione sul sito, nascondibile) evita i doppioni: una riga
 *   già scritta nel tab non viene ripetuta, anche se il sito la rimanda. Chi sceglie due corsi ha
 *   una riga in ciascun tab, con lo stesso Rif.
 *
 * Installazione (una volta):
 * 1. Nel foglio: Estensioni → Apps Script, incolla questo file al posto del codice che c'è, salva.
 * 2. Scegli la funzione "creaToken" in alto e premi Esegui (la prima volta chiede le autorizzazioni):
 *    nel registro compare il codice segreto da mettere in wp-config.php come VV_CORSI_TOKEN.
 * 3. Esegui il deployment → Nuovo deployment → tipo "App web"; Esegui come: me; Chi ha accesso:
 *    chiunque. L'URL dell'app web (finisce con /exec) va in wp-config.php come VV_CORSI_URL.
 * 4. Lo script scrive con l'account di chi lo pubblica: il foglio può restare condiviso solo con
 *    account nominativi, senza "Chiunque abbia il link".
 * In alternativa lo script può stare nel proprio Drive (script.google.com → Nuovo progetto), con
 * l'ID del foglio in ID_FOGLIO: basta essere editor del foglio, non serve esserne proprietari.
 * Se modifichi questo codice: Esegui il deployment → Gestisci deployment → modifica → Nuova versione
 * (l'URL resta lo stesso).
 */

// Script dentro il foglio (Estensioni → Apps Script): lasciare vuoto. Script a parte, creato da
// script.google.com nel proprio Drive: l'ID del foglio, cioè il pezzo dell'indirizzo fra /d/ e /edit.
var ID_FOGLIO = '';

// Tab da cui copiare le intestazioni. Se manca o è vuoto: il primo tab con la riga 1 compilata.
var TAB_MODELLO = 'lettura-ad-alta-voce-2026';

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
    var fatti = [];
    (dati.righe || []).forEach(function (riga) {
      if (!riga[RIF]) {
        return;
      }
      scriviRiga_(tabCorso_(ss, String(riga._tab || '')), riga);
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

/** Il tab da cui copiare le intestazioni. */
function tabModello_(ss) {
  var tab = TAB_MODELLO ? ss.getSheetByName(TAB_MODELLO) : null;
  if (tab && tab.getLastColumn() > 0) {
    return tab;
  }
  var altri = ss.getSheets().filter(function (s) {
    return s.getName() !== DA_SMISTARE && s.getLastColumn() > 0;
  });
  if (!altri.length) {
    throw new Error('nessun tab con le intestazioni da copiare');
  }
  return altri[0];
}

/**
 * Il tab del corso. Se esiste ma è vuoto riceve le intestazioni del modello; se non esiste la riga
 * va in "Da smistare", creato la prima volta con le intestazioni del modello e la colonna "Corso".
 */
function tabCorso_(ss, nome) {
  var tab = nome && nome !== DA_SMISTARE ? ss.getSheetByName(nome) : null;
  if (tab) {
    if (tab.getLastColumn() === 0) {
      copiaIntestazioni_(tabModello_(ss), tab);
    }
    return tab;
  }
  tab = ss.getSheetByName(DA_SMISTARE);
  if (!tab) {
    tab = ss.insertSheet(DA_SMISTARE, ss.getNumSheets());
  }
  if (tab.getLastColumn() === 0) {
    copiaIntestazioni_(tabModello_(ss), tab);
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

function risposta_(oggetto) {
  return ContentService.createTextOutput(JSON.stringify(oggetto)).setMimeType(ContentService.MimeType.JSON);
}

/** Crea il codice segreto (da eseguire una volta) e lo mostra nel registro di esecuzione. */
function creaToken() {
  var token = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '');
  PropertiesService.getScriptProperties().setProperty('TOKEN', token);
  Logger.log('VV_CORSI_TOKEN: ' + token);
}
