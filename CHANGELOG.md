# Changelog

## [1.0.1] - 2026-11-24

### Fixed
- Validate vendor table-prefix configuration before using it in model and migration table names.
- Validate the configured default vendor status and allowed status list.
- Set `approved_at` when a vendor is created with an approved default status.
- Preserve the original approval timestamp when an already-approved vendor is approved again.
- Reject unsaved owner models, empty names/slugs, invalid attribute types, and unknown vendor attributes.

### Tests
- Expand manager tests for invalid configuration, input validation, default approval state, and approval timestamp behavior.

## [1.0.0] - 2026-11-14

- Initial release.
- Added polymorphic vendor ownership and vendor profile fields.
- Added pending, approved, rejected and suspended moderation states.
- Added approved vendor query and slug lookup.
- Added configurable table prefix and JSON metadata.
- Added Laravel 12/13 compatibility workflow and PHPUnit tests.
