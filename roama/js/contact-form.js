/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// The phone mask of every form of the theme (input[name="roama-phone"]): digits only, 0XX… and
// 380XX… become +38 (0XX) XXX XX XX, the caret stays after the digit it was after, backspace
// over a separator removes the digit before it. The server checks the same rule
// (roama_phone_digits() in inc/cf7.php).
(() => {
  const PREFIX = '+38 (';

  /** The 0XXXXXXXXX digits of a value (formatted by us, typed or pasted). */
  const localDigits = (value) => {
    const text = String(value);
    if (text.startsWith(PREFIX)) return text.slice(PREFIX.length).replace(/\D/g, '').slice(0, 10);
    let digits = text.replace(/\D/g, '');
    if ('380'.startsWith(digits)) return null; // «3», «38», «380» typed: the start of +380
    if (digits.startsWith('380')) digits = digits.slice(2);
    else if (digits.startsWith('80')) digits = digits.slice(1);
    else if (!digits.startsWith('0')) digits = `0${digits}`;
    return digits.slice(0, 10);
  };

  /** +38 (0XX) XXX XX XX, as far as there are digits. */
  const format = (digits) => {
    if (!digits) return '';
    let out = PREFIX + digits.slice(0, 3);
    if (digits.length > 3) out += `) ${digits.slice(3, 6)}`;
    if (digits.length > 6) out += ` ${digits.slice(6, 8)}`;
    if (digits.length > 8) out += ` ${digits.slice(8, 10)}`;
    return out;
  };

  /** Digits before the caret of a value we formatted (or typed raw). */
  const digitsBefore = (value, caret) => {
    const head = String(value).slice(0, caret);
    if (String(value).startsWith(PREFIX)) return head.length <= PREFIX.length ? 0 : head.slice(PREFIX.length).replace(/\D/g, '').length;
    return (localDigits(head) ?? '').length;
  };

  /**
   * Value and caret after an edit.
   * @param {string} value   The field's value now.
   * @param {number} caret   Caret position in it.
   * @param {string} [prev]  The formatted value before the edit (to see a deleted separator).
   */
  const mask = (value, caret, prev = '') => {
    let digits = localDigits(value);
    if (digits === null) {
      const raw = String(value).replace(/\D/g, '');
      return { value: raw ? `+${raw}` : '', caret: raw ? raw.length + 1 : 0 };
    }
    let keep = Math.min(digitsBefore(value, caret), digits.length);
    const prevDigits = prev ? localDigits(prev) : null;
    if (prevDigits !== null && String(value).length < String(prev).length && prevDigits === digits && keep > 0) {
      // Only a separator was deleted: delete the digit before the caret instead.
      digits = digits.slice(0, keep - 1) + digits.slice(keep);
      keep -= 1;
    }
    const out = format(digits);
    let pos = out.length;
    if (keep < digits.length) {
      let seen = 0;
      pos = keep === 0 ? Math.min(PREFIX.length, out.length) : 0;
      for (let i = PREFIX.length; i < out.length && keep > 0; i += 1) {
        if (/\d/.test(out[i])) seen += 1;
        if (seen === keep) {
          pos = i + 1;
          break;
        }
      }
    }
    return { value: out, caret: pos };
  };

  const attach = (input) => {
    if (input.dataset.roamaMask) return;
    input.dataset.roamaMask = 'on';
    input.setAttribute('inputmode', 'tel');
    let last = input.value;
    input.addEventListener('input', () => {
      const next = mask(input.value, input.selectionStart ?? input.value.length, last);
      last = next.value;
      if (next.value === input.value) return;
      input.value = next.value;
      if (document.activeElement === input) input.setSelectionRange(next.caret, next.caret);
    });
    // CF7 resets the form after a successful send: the next edit starts from empty.
    input.form?.addEventListener('reset', () => { last = ''; });
    if (input.value) last = input.value = mask(input.value, input.value.length).value;
  };

  const init = () => document.querySelectorAll('input[name="roama-phone"]').forEach(attach);
  window.roamaPhoneMask = { mask, format, localDigits };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
