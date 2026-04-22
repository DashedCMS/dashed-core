# Changelog

All notable changes to `Dashed core` will be documented in this file.

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
