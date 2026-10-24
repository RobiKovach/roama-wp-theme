# Зміни теми ROAMA base / Changelog

## 0.1.1 — 2026-10-24

Доробки після злиття з модулями ROAMA / Follow-ups after the merge with the ROAMA modules.

- Шапка на телефонах і планшетах (до 991 px): меню за кнопкою-бургером (модуль `burger`), ESC закриває
  меню й повертає фокус на кнопку; без JavaScript меню лишається звичайним списком. / Phones and tablets
  (up to 991 px): the menu sits behind a burger button (the `burger` module); ESC closes it and returns
  focus to the button; without JavaScript the menu stays a plain list.
- Тема збирає лише свої модулі — `burger` і `header` (`THEME_MODULES` у `tools/build-theme.mjs`) — в
  окрему папку `.roama-cache/theme-dist`, тож `dist/` шаблону не змінюється; в тему потрапляють лише
  картинки сторінок, які тема відтворює. / The theme bundles only its modules (`burger`, `header`) into
  `.roama-cache/theme-dist`; only the pictures of the pages the theme reproduces are copied.
- Довгі слова в заголовках переносяться за складами там, де браузер має переноси для мови сторінки
  (`hyphens: auto`), посеред слова — лише як останній вихід. / Long words in titles hyphenate where the
  browser can; a mid-word break is only the last resort.
- Одна або дві картки лишаються шириною колонки, а не розтягуються на весь ряд. / One or two cards keep
  the column width instead of filling the row.

## 0.1.0 — 2026-10-24

Перша версія основи / First version of the base.

- Тема з розміткою, стилями й скриптами зібраного демо ROAMA; ресурси копіює `tools/build-theme.mjs`
  (сталі назви, версії за вмістом). Меню `roama_primary` і `roama_footer`, фавікон теми, поки немає іконки
  сайту, SEO з відступом перед SEO-плагіном, захист від прямого доступу до файлів.
- Вміст у Secure Custom Fields (Local JSON `acf-json/`): секції головної й «Про нас» з перемикачами
  «Показувати секцію», жорсткі ліміти символів, сторінка «ROAMA: налаштування», типові значення
  (`inc/defaults.php`), ліміти назв сторінок і пунктів меню (`inc/limits.php`), `docs/SCF-FIELDS.md`
  генерується з `acf-json`.
- Форма Contact Form 7 (маска телефону, пастка для ботів, рядок згоди, Flamingo) і заявки в Telegram та
  Google-таблицю з чергою повторів (`inc/leads*.php`, `setup/`).
- Імпорт першого вмісту `wp roama import` (`--overwrite`) і «Зовнішній вигляд → ROAMA: імпорт вмісту»:
  картинки з alt, сторінки, поля, форма, меню, сторінка політики конфіденційності з `[FILL IN: …]`,
  постійні посилання. Повторний запуск нічого не дублює.
- `screenshot.png`, документи WP-SETUP, CF7-FORMS, LEADS-SETUP; архів теми `tools/zip-theme.mjs`.
