# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

WordPress child theme of **Sydney 2.71** (`Template: sydney` in `style.css`) for wvoce.it, site of the VivaVoce association. No build step: PHP, plain CSS and one vanilla JS file are served as-is. PHP must stay compatible with **7.4** (CI runs 7.4 and 8.3), WordPress ≥ 6.0.

Everything is written in **Italian**: identifiers (`vv_event_dates`, `vv_hero_active`), comments, test names, CHANGELOG, commit messages, UI strings. Keep it that way. Functions use the `vv_` prefix (the PrefixAllGlobals sniff is disabled because WPCS rejects a 2-letter prefix); text domain is `vivavoce`.

## Commands

```bash
php tests/run.php                 # PHP tests without WordPress (composer test)
composer install && composer lint # phpcs: WordPress-Extra + PHPCompatibilityWP (phpcs.xml.dist)
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l   # syntax check, as CI does
bash bin/build-zip.sh             # build/wvoce-classic.zip from HEAD (composer build)
```

There is no per-test filter: `tests/run.php` is a flat script of `uguale( 'nome', $atteso, $ottenuto )` assertions that prints only failures and exits 1 if any fail. To work on one area, add/edit assertions in the relevant `// Sezione` block. WordPress functions the tested code needs are stubbed at the top of `tests/run.php` (options in a global `$vv_opzioni` array); if new code under test calls another WP function, add a minimal stub there. Only `inc/` files that load without WordPress are required by the test (customizer, meta, cards, contenuti, condivisione, dati-strutturati, email).

`bin/build-zip.sh` uses `git archive HEAD`, so uncommitted changes are not included; dev files are excluded via `export-ignore` in `.gitattributes` (add new dev-only paths there).

## Architecture

- `functions.php` defines `VV_DIR`/`VV_URI`, requires every `inc/*.php` module, and enqueues the CSS files in a fixed chain (`tokens → base → layout → header → footer → components → home → pages → plugins → scuro`), each depending on the previous, versioned by `filemtime`. New CSS files must be added to that list.
- Each `inc/` file is a self-contained feature hooked into WordPress/Sydney via actions and filters; there are no classes. The README has the one-line description of each.
- **Light/dark theme**: an inline script in `wp_head` (priority 0, in `functions.php`) sets `data-vv-tema` / `data-vv-scelta` on `<html>` from `localStorage['vv-tema']`; `assets/css/tokens.css` holds the `--vv-*` design tokens and the dark palette keyed on `data-vv-tema`; `assets/js/vv.js` provides the switcher via `window.vvTema()`. Colors should go through tokens, not literals.
- **Registrations (Forminator rules, "Recupera i tuoi dati", Google Sheets sync)** live in the separate plugin **wvoce-iscrizioni** (repo `eradan/wvoce-iscrizioni`, sibling folder `../wvoce-iscrizioni`, prefix `wvi_`, configured in Strumenti → Iscrizioni, agnostic of the theme). The theme only keeps the `--wvi-*` color variables mapped to theme tokens in `assets/css/plugins.css`, and `inc/email.php`, which brands every `wp_mail()` including the plugin's. The one-shot hand-over of VivaVoce's configuration to the plugin (`inc/iscrizioni.php`, 1.0.0) is done and was removed in 1.0.1. Contact email and the "Diventa socio" page exist both in the Customizer (theme) and in the plugin's settings: they must be kept equal by hand. Don't reintroduce registration logic in the theme; changes to it go in the plugin.
- Content fixes applied at render time (decorative Unicode letters, external emoji images, broken lists, gallery links) live in `inc/contenuti.php`; event dates/ordering and card markup in `inc/cards.php` + `template-parts/card.php`; shortcodes in `inc/shortcodes.php`.
- `post-templates/post_full.php` overrides Sydney's template of the same name — the filename must not change.

## Conventions

- Configurable values are constants or literals in the code, not `vv_*` filters: 0.9.7 removed filters that had no consumers. Don't add new extension hooks unless something uses them.
- Releases: bump `Version:` in `style.css`, add a section at the top of `CHANGELOG.md` (Italian, user-facing, notes any manual step needed on the site), commit message `X.Y.Z: breve descrizione`.
- `docs/` holds the privacy notice draft for the Google Sheets transfer; it is excluded from the theme zip. Operator guides for registrations live in the plugin panel (Strumenti → Iscrizioni → Collegamenti → Guida), not here.
