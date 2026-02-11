# Laravel Meta

Easily add and manage meta data for your Laravel models with a clean, intuitive primary-meta table approach — inspired by WordPress's `wp_postmeta` / `wp_usermeta` pattern.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zynfly/laravel-meta.svg?style=flat-square)](https://packagist.org/packages/zynfly/laravel-meta)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/zynfly/laravel-meta/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/zynfly/laravel-meta/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/zynfly/laravel-meta/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/zynfly/laravel-meta/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/zynfly/laravel-meta.svg?style=flat-square)](https://packagist.org/packages/zynfly/laravel-meta)

## Why Laravel Meta?

Sometimes you need to store flexible, schema-less attributes on your Eloquent models without constantly running migrations. Laravel Meta gives each model its own `{table}_meta` table with `key` / `value` rows — just like WordPress does for posts, users, and comments.

```
posts                    posts_meta
┌────┬─────────┐         ┌────┬─────────┬──────────┬────────┐
│ id │ title   │         │ id │ post_id │ key      │ value  │
├────┼─────────┤         ├────┼─────────┼──────────┼────────┤
│  1 │ Hello   │───────▶ │  1 │       1 │ subtitle │ World  │
│    │         │         │  2 │       1 │ color    │ blue   │
└────┴─────────┘         └────┴─────────┴──────────┴────────┘
```

## Installation

```bash
composer require zynfly/laravel-meta
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="meta-config"
```

## Quick Start

### 1. Generate a meta migration

```bash
php artisan make:meta-migration posts
# Creates: database/migrations/xxxx_xx_xx_xxxxxx_create_posts_meta_table.php
```

You can also specify a custom foreign key:

```bash
php artisan make:meta-migration posts --foreign-key=article_id
```

Then run the migration:

```bash
php artisan migrate
```

### 2. Add the trait to your model

```php
use Zynfly\LaravelMeta\Traits\HasMetaTable;

class Post extends Model
{
    use HasMetaTable;

    protected $fillable = ['title', 'content'];
}
```

### 3. Use it

```php
// Create with meta — attributes not in the table are stored as meta automatically
$post = Post::create([
    'title'    => 'Hello World',      // stored in posts table
    'subtitle' => 'A great post',     // stored in posts_meta table
    'color'    => 'blue',             // stored in posts_meta table
]);

// Read meta transparently via attribute access
echo $post->subtitle; // "A great post"

// Update meta via attribute assignment
$post->subtitle = 'An awesome post';
$post->save();
```

## API Reference

### Transparent attribute access

Meta values are accessible as regular model attributes. The trait automatically separates table columns from meta on create, update, and read:

```php
$post->subtitle;          // reads from meta cache (no N+1)
$post->subtitle = 'New';  // marks as dirty meta
$post->save();            // persists to meta table
```

### Explicit meta methods

For more control, use the explicit API:

```php
// Get
$post->getMeta('color');              // "blue"
$post->getMeta('missing', 'default'); // "default"
$post->getAllMeta();                  // ['subtitle' => '...', 'color' => 'blue']
$post->hasMeta('color');              // true

// Set (persists immediately)
$post->setMeta('color', 'red');
$post->setMeta([                      // bulk set
    'color' => 'red',
    'size'  => 'large',
]);

// Remove
$post->removeMeta('color');
$post->removeMeta(['color', 'size']); // bulk remove

// Refresh cache from database
$post->refreshMetaCache();
```

All `setMeta` / `removeMeta` calls are chainable:

```php
$post->setMeta('a', '1')->setMeta('b', '2')->removeMeta('c');
```

### Meta relationship

Access raw meta records via the `meta()` HasMany relationship:

```php
$post->meta;                              // Collection of Meta models
$post->meta()->where('key', 'color')->first()->value; // "blue"
```

### Type casting

The `Meta` model provides a `getTypedValue()` helper:

```php
$meta = $post->meta()->where('key', 'count')->first();
$meta->getTypedValue('integer'); // 42
$meta->getTypedValue('boolean'); // true
$meta->getTypedValue('json');    // ['foo' => 'bar']
```

Supported types: `string`, `int`/`integer`, `float`/`double`, `bool`/`boolean`, `array`/`json`.

### Programmatic table creation

You can also create/drop meta tables in your own migrations using the facade:

```php
use Zynfly\LaravelMeta\Facades\LaravelMeta;

// In a migration
public function up(): void
{
    LaravelMeta::createMetaTableFor('posts');               // posts_meta with post_id FK
    LaravelMeta::createMetaTableFor('posts', 'article_id'); // custom FK name
}

public function down(): void
{
    LaravelMeta::dropMetaTableFor('posts');
}
```

The generated meta table includes:

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint` | Primary key |
| `{singular}_id` | `unsigned bigint` | Indexed foreign key |
| `key` | `varchar(255)` | Meta key |
| `value` | `text` | Meta value (nullable) |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

Plus a composite index on `({foreign_key}, key)` for query performance.

## Configuration

Published config file (`config/meta.php`):

```php
return [
    // Column type for 'value': "text" or "longText"
    'value_column_type' => 'text',

    // Wrap meta insert/update in DB::transaction()
    'use_transactions' => true,
];
```

## How It Works

1. **Boot** — The trait caches table column names via `Schema::getColumnListing()`.
2. **Create** — `getAttributesForInsert()` splits attributes into table columns vs. meta. After the model is inserted, meta rows are batch-inserted via `createMany()`.
3. **Update** — `getDirtyForUpdate()` splits dirty attributes. `performUpdate()` ensures meta is persisted even when only meta attributes changed.
4. **Read** — `getAttribute()` first checks the parent model, then falls back to the meta cache. All meta is loaded in a single query on first access (no N+1).
5. **Delete** — All related meta records are deleted when the model is deleted.

All write operations are wrapped in `DB::transaction()` for data integrity.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [zynfly](https://github.com/zynfly)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
