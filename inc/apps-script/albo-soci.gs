/**
 * VivaVoce: riceve dal sito le domande di ammissione dei soci (modulo Forminator "Iscrizione Socio")
 * e le scrive nel foglio "Albo Soci", nel tab dell'anno sociale ("DomandeAmmissione 2026-2027").
 *
 * - Ogni valore va nella colonna con la stessa intestazione (riga 1): le colonne si possono spostare
 *   o aggiungere. "Assicurazione" (colonna A) resta vuota: la decisione è tua.
 * - Se il tab dell'anno non c'è lo crea, con le intestazioni del tab DomandeAmmissione più recente.
 * - La colonna "Rif." (numero dell'iscrizione sul sito) evita i doppioni: una riga già scritta non
 *   viene ripetuta, anche se il sito la rimanda.
 *
 * Installazione (una volta), istruzioni complete nel sito: Strumenti → Esporta iscrizioni.
 * 1. In Google Drive: Nuovo → Altro → Google Apps Script (progetto autonomo), oppure dal foglio:
 *    Estensioni → Apps Script. Incolla questo file così com'è al posto del codice che c'è, salva.
 * 2. Scegli la funzione "creaToken" in alto e premi Esegui (la prima volta chiede le autorizzazioni):
 *    nel registro compare il codice segreto da mettere nel sito come VV_SOCI_TOKEN.
 * 3. Esegui il deployment → Nuovo deployment → tipo "App web"; Esegui come: me; Chi ha accesso:
 *    chiunque. L'URL dell'app web (finisce con /exec) va nel sito come VV_SOCI_URL.
 * 4. L'ID del foglio va nel sito come VV_SOCI_FOGLIO: lo script lo riceve a ogni chiamata. Basta
 *    che l'account che pubblica lo script sia editor del foglio, non serve esserne proprietari.
 * Se modifichi questo codice: Esegui il deployment → Gestisci deployment → modifica → Nuova versione
 * (URL e codice restano gli stessi).
 */

// Lasciare vuoto: l'ID del foglio lo manda il sito a ogni chiamata (VV_SOCI_FOGLIO in Strumenti →
// Esporta iscrizioni → Configurazione). Serve solo se il sito non lo indica; per uno script aperto
// dal foglio (Estensioni → Apps Script) vale comunque il foglio stesso.
var ID_FOGLIO = '';
var PROPRIETA = 'VV_SOCI';

var PREFISSO = 'DomandeAmmissione ';
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
    var ss = foglio_(dati);
    var fatti = [];
    var stati = {};
    (dati.righe || []).forEach(function (riga) {
      if (!riga[RIF]) {
        return;
      }
      scriviRiga_(tabAnno_(ss, riga._tab), riga, stati);
      fatti.push(String(riga[RIF]));
    });
    SpreadsheetApp.flush();
    return risposta_({ ok: true, foglio: ss.getName(), rif: fatti });
  } catch (err) {
    return risposta_({ ok: false, errore: String(err && err.message || err) });
  } finally {
    lock.releaseLock();
  }
}

/** Il tab dell'anno sociale; se manca lo crea accanto al più recente, con le sue intestazioni. */
function tabAnno_(ss, nome) {
  if (!/^DomandeAmmissione \d{4}-\d{4}$/.test(nome || '')) {
    throw new Error('nome del tab non valido: ' + nome);
  }
  var tab = ss.getSheetByName(nome);
  if (tab) {
    return tab;
  }
  var modello = ss.getSheets()
    .filter(function (s) { return s.getName().indexOf(PREFISSO) === 0; })
    .sort(function (a, b) { return a.getName() < b.getName() ? 1 : -1; })[0];
  if (!modello) {
    throw new Error('nessun tab "' + PREFISSO + '…" da cui copiare le intestazioni');
  }
  tab = ss.insertSheet(nome, modello.getIndex() - 1);
  var colonne = modello.getLastColumn();
  modello.getRange(1, 1, 1, colonne).copyTo(tab.getRange(1, 1, 1, colonne));
  tab.setFrozenRows(1);
  return tab;
}

/**
 * Intestazioni e Rif. già scritti di un tab, letti una volta per chiamata e tenuti in "stati" (per
 * id del tab). Se manca la colonna "Rif." la aggiunge.
 */
function statoTab_(tab, stati) {
  var id = tab.getSheetId();
  if (stati[id]) {
    return stati[id];
  }
  var colonne = Math.max(tab.getLastColumn(), 1);
  var intestazioni = tab.getRange(1, 1, 1, colonne).getValues()[0].map(function (h) {
    return String(h).trim();
  });
  var colRif = indice_(intestazioni, RIF);
  if (colRif < 0) {
    colRif = intestazioni.length;
    tab.getRange(1, colRif + 1).setValue(RIF);
    intestazioni.push(RIF);
  }
  var presenti = {};
  var ultima = tab.getLastRow();
  if (ultima > 1) {
    tab.getRange(2, colRif + 1, ultima - 1, 1).getValues().forEach(function (r) {
      presenti[String(r[0])] = true;
    });
  }
  stati[id] = { intestazioni: intestazioni, presenti: presenti };
  return stati[id];
}

/** Scrive una riga sotto le altre, valore per valore nella colonna con la stessa intestazione. */
function scriviRiga_(tab, riga, stati) {
  var stato = statoTab_(tab, stati);
  var rif = String(riga[RIF]);
  if (stato.presenti[rif]) {
    return;
  }
  var valori = stato.intestazioni.map(function (h) {
    var chiave = Object.keys(riga).filter(function (k) { return k !== '_tab' && uguali_(k, h); })[0];
    return chiave === undefined ? '' : testo_(riga[chiave]);
  });
  // Come testo: codici fiscali, telefoni e CAP restano come sono scritti (niente zeri persi).
  tab.getRange(tab.getLastRow() + 1, 1, 1, valori.length).setNumberFormat('@').setValues([valori]);
  stato.presenti[rif] = true;
}

/**
 * Valore come testo puro. I dati arrivano da un modulo pubblico: un nome che comincia con "="
 * diventerebbe una formula (che può leggere altri tab o chiamare indirizzi esterni con IMPORTXML).
 * L'apostrofo davanti, che il foglio non mostra, lo tiene testo anche se il formato della cella
 * venisse cambiato. Gli altri segni (+, -, @) nel foglio Google non avviano formule: i numeri di
 * telefono "+39…" restano come sono.
 */
function testo_(valore) {
  var s = String(valore);
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
 * Il foglio su cui scrivere: l'ID mandato dal sito, altrimenti ID_FOGLIO, altrimenti il foglio a cui
 * lo script è collegato. Il codice segreto resta l'unica chiave: chi lo conosce può indicare un
 * qualsiasi foglio modificabile dall'account che ha pubblicato lo script, quindi va custodito.
 */
function foglio_(dati) {
  var id = String(dati.foglio || ID_FOGLIO || '').trim();
  if (!id) {
    var attivo = SpreadsheetApp.getActiveSpreadsheet();
    if (attivo) {
      return attivo;
    }
    throw new Error('manca l\'ID del foglio: ' + PROPRIETA + '_FOGLIO in Strumenti → Esporta iscrizioni → Configurazione');
  }
  try {
    return SpreadsheetApp.openById(id);
  } catch (err) {
    throw new Error('foglio non raggiungibile (' + id + '): controlla ' + PROPRIETA
      + '_FOGLIO e che l\'account che ha pubblicato lo script possa modificarlo');
  }
}

function risposta_(oggetto) {
  return ContentService.createTextOutput(JSON.stringify(oggetto)).setMimeType(ContentService.MimeType.JSON);
}

/** Crea il codice segreto (da eseguire una volta) e lo mostra nel registro di esecuzione. */
function creaToken() {
  var token = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '');
  PropertiesService.getScriptProperties().setProperty('TOKEN', token);
  Logger.log(PROPRIETA + '_TOKEN: ' + token);
}
