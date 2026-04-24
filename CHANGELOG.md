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

### Migration
- `2026_04_24_100000_make_email_templates_translatable.php`: converteert `subject` en `from_name` naar `longText` en wikkelt bestaande data onder `config('app.fallback_locale')`.

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
