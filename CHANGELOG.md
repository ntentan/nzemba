CHANGELOG
=========

## Unreleased
### Changed
- Only extract fields present in the input dataset when inserting from an array, avoiding inserting unset model properties as null.

## v0.1.0 - 2026-09-25
### Added
- Initial release of the `ntentan/nzema` repository-based ORM library.
- Model property reflection and schema caching using `ntentan/kaikai`.
- Driver-agnostic SQL query generation with PostgreSQL generator (`ntentan\nzemba\generators\Postgres`).
- `Repository::insert()` method supporting both array and model object inputs.
- `Repository::getService()` configuration helper for dependency injection containers.
- PHPUnit unit test suite.
