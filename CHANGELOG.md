# Changelog

All notable changes to `Dashed core` will be documented in this file.

## v4.1.0 - 2026-04-24

### Added
- `EmailTemplate`-model is nu vertaalbaar (`subject`, `from_name`, `blocks`) via `spatie/laravel-translatable`.
- `HasEmailTemplate`-trait-methods (`renderFromTemplate`, `templateSubject`, `templateFrom`) accepteren een optionele `?string $locale` parameter.
- Nieuwe `ResolvesEmailLocale`-trait voor Mailables om locale af te leiden uit order/customer-context.
- Filament-Resource heeft nu locale-tabs (via LaraZeus SpatieTranslatable).
- Nieuwe Filament-actions "Kopieer naar locale" en "Vertaal met DeepL" op de edit-page.
- Locale-completeness-badge op de tabel-lijst + waarschuwing op de edit-page bij ontbrekende vertalingen.

### Changed
- `EmailRenderer::render()` en `renderSubject()` accepteren een `?string $locale`; rendering scoped binnen een locale-savepoint om `__()`/`trans()` in block-renderers correct te laten werken.

### Fixed
- `name` en `mailable_key` blijven zichtbaar bij taalwisseling (`Placeholder` in plaats van disabled `TextInput`).
- "Kopieer naar locale" en "Vertaal met DeepL" modals: `from_locale` defaultet op de huidige actieve locale, `to_locales` op alle andere.
- `EmailTemplate::getFallbackLocale()` valt terug op een locale die daadwerkelijk inhoud heeft (niet blindelings op `config('app.fallback_locale')`), zodat renderen nooit naar een lege locale valt.
- Waarschuwingstekst en badge-tooltip tonen locale-codes in hoofdletters (NL, EN).

### Migration
- `2026_04_24_100000_make_email_templates_translatable.php`: converteert `subject` en `from_name` naar `longText` en wikkelt bestaande data onder `config('app.locale')`.
- `2026_04_24_120000_relocate_email_template_locale_key.php`: repair-migratie die op installs met `app.locale != app.fallback_locale` de door de eerste migratie onder `app.fallback_locale` gezette data terugzet onder `app.locale` — alleen wanneer de `app.locale`-key nog leeg is.

## v4.0.137 - 2026-04-22

### Added

- `CustomStructuredData` model plus `HasStructuredData` trait for morph-many
  JSON-LD snippets per subject. Stored in the new `dashed__custom_structured_data` table.
- Frontend head-partial now renders any `seo()->metaData('customStructuredData')`
  array entries as `<script type="application/ld+json">` blocks.

### Changed

- `HasEditableCMSActions` trait now injects `RequestSeoAuditAction` (from dashed-marketing)
  instead of `RequestSeoImprovementAction`. The class_exists guard still keeps the
  trait usable without dashed-marketing.

### Coordinated with

- Requires dashed-marketing v4.12.0 in the same release cycle. Consumers using
  dashed-marketing must upgrade both packages together; the old `RequestSeoImprovementAction`
  is gone from marketing and this trait must point at the new one.

## 1.0.0 - 202X-XX-XX

- initial release
