/*! © ROAMA. All rights reserved. */
// Google Apps Script for the client's lead sheet (setup/LEADS-SETUP.md). The site POSTs a
// lead as JSON (inc/leads-core.php roama_leads_sheet_payload()); this web app checks the
// shared secret, skips a lead it already has (by id) and adds one row, every value as text.
// Script property ROAMA_SECRET = the «Секретний ключ таблиці» of Settings → «Заявки».

var ROAMA_SHEET = 'Заявки';
var ROAMA_FIXED = ['Дата', 'ID', 'Форма', 'Сторінка'];
var ROAMA_SOURCE = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'referrer', 'landing'];

function doPost(e) {
  var lock = LockService.getScriptLock();
  try {
    var lead = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    var secret = PropertiesService.getScriptProperties().getProperty('ROAMA_SECRET') || '';
    if (secret.length < 16 || lead.secret !== secret) return roamaAnswer({ ok: false, error: 'secret' });
    if (!lead.id || typeof lead.id !== 'string') return roamaAnswer({ ok: false, error: 'id' });

    lock.waitLock(10000);
    var sheet = roamaSheet();
    if (roamaHas(sheet, lead.id)) return roamaAnswer({ ok: true, duplicate: true });

    var values = {};
    values['Дата'] = lead.time || '';
    values['ID'] = lead.id;
    values['Форма'] = lead.form || '';
    values['Сторінка'] = lead.page || '';
    (lead.fields || []).forEach(function (field) {
      if (field && field.label) values[String(field.label)] = String(field.value || '');
    });
    var source = lead.source || {};
    ROAMA_SOURCE.forEach(function (key) {
      if (source[key]) values[key] = String(source[key]);
    });

    var header = roamaHeader(sheet, Object.keys(values));
    var row = header.map(function (name) { return roamaText(values[name]); });
    var at = sheet.getLastRow() + 1;
    var range = sheet.getRange(at, 1, 1, row.length);
    range.setNumberFormat('@');
    range.setValues([row]);
    return roamaAnswer({ ok: true });
  } catch (error) {
    return roamaAnswer({ ok: false, error: String(error && error.message ? error.message : error).slice(0, 200) });
  } finally {
    if (lock.hasLock()) lock.releaseLock();
  }
}

// A value as text: «+380…», «=…», «-…» never become a number or a formula.
function roamaText(value) {
  var text = value === undefined || value === null ? '' : String(value);
  return /^[=+\-@]/.test(text) ? "'" + text : text;
}

function roamaSheet() {
  var book = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = book.getSheetByName(ROAMA_SHEET) || book.insertSheet(ROAMA_SHEET);
  if (sheet.getLastRow() === 0) {
    sheet.getRange(1, 1, 1, ROAMA_FIXED.length).setValues([ROAMA_FIXED]).setFontWeight('bold');
    sheet.setFrozenRows(1);
  }
  return sheet;
}

// The header row, with new columns added for labels it does not have yet.
function roamaHeader(sheet, names) {
  var width = Math.max(sheet.getLastColumn(), 1);
  var header = sheet.getRange(1, 1, 1, width).getValues()[0].map(String).filter(function (name) { return name !== ''; });
  names.forEach(function (name) {
    if (header.indexOf(name) === -1) {
      header.push(name);
      sheet.getRange(1, header.length).setValue(name).setFontWeight('bold');
    }
  });
  return header;
}

function roamaHas(sheet, id) {
  if (sheet.getLastRow() < 2) return false;
  var column = ROAMA_FIXED.indexOf('ID') + 1;
  var found = sheet.getRange(2, column, sheet.getLastRow() - 1, 1).createTextFinder(id).matchEntireCell(true).findNext();
  return found !== null;
}

function roamaAnswer(data) {
  return ContentService.createTextOutput(JSON.stringify(data)).setMimeType(ContentService.MimeType.JSON);
}
