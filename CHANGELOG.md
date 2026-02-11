# Changelog

All notable changes to `laravel-meta` will be documented in this file.

## v2.0.0 - 2026-02-11

### Bug Fixes

- Fixed `getAttribute()` falsy value bug — `if ($attr)` incorrectly treated `""`, `0`, `false` as null; now uses `!== null`
- Fixed update flow — when only meta attributes (no table columns) are dirty, `updated` event was not fired; solved by overriding `performUpdate()`
- Fixed `deleteMeta()` redundant `where` clause — the `meta()` relationship already scopes by foreign key

### Performance

- Added meta caching (`loadMetaCache()`) — loads all meta key-value pairs in a single query per model instance, eliminating N+1 queries on repeated `getAttribute()` calls
- Skip meta lookup for relationship methods and known table columns in `getAttribute()`

### Schema Improvements

- Changed `value` column from `string(255)` to `text` to support larger values
- Changed foreign key column from `bigInteger` to `unsignedBigInteger`
- Added index on foreign key column
- Added composite index on `(foreign_key, key)` for query performance
- Made `value` column nullable
- Added configurable value column type via `meta.value_column_type` config

### New Features

- `getMeta(string $key, mixed $default = null)` — get a specific meta value with optional default
- `setMeta(string|array $key, mixed $value = null)` — set one or multiple meta values at once
- `removeMeta(string|array $keys)` — remove one or multiple meta entries by key
- `hasMeta(string $key)` — check if a meta key exists
- `getAllMeta()` — get all meta as a key-value array
- `refreshMetaCache()` — force refresh the in-memory meta cache from database
- `Meta::getTypedValue(string $type)` — cast meta value to `int`, `float`, `bool`, `array`/`json`
- `make:meta-migration` Artisan command — generate a properly structured meta table migration with indexes
- Configuration file with `value_column_type` and `use_transactions` options
- All meta write operations wrapped in `DB::transaction()` for data integrity
- `initializeHasMetaTable()` for clean per-instance state initialization

### Breaking Changes

- Artisan command signature changed from `laravel-meta` to `make:meta-migration {table}`
- Config file name changed from `laravel-meta` to `meta` (`config/meta.php`)
- Removed unused views registration from service provider
- Removed generic migration stub registration from service provider
- `createMetaTableFor()` now generates `text` value columns (was `string`) and adds indexes

## v1.0.0 - Initial Release

- Basic `HasMetaTable` trait with WordPress-style meta table support
- `LaravelMeta` facade for creating/dropping meta tables
- `Meta` model with dynamic table and foreign key support
