<?php

namespace Zynfly\LaravelMeta\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Zynfly\LaravelMeta\Models\Meta;

trait HasMetaTable
{
    /**
     * Cached table columns per model class.
     */
    protected static array $columns = [];

    /**
     * Meta attributes to be updated (dirty meta during update).
     */
    protected array $dirtyMeta = [];

    /**
     * Meta attributes to be inserted (during create).
     */
    protected array $insertMeta = [];

    /**
     * Cached meta key-value pairs loaded from the database.
     */
    protected ?array $metaCache = null;

    /**
     * Boot the trait: cache columns and register model lifecycle hooks.
     */
    protected static function bootHasMetaTable(): void
    {
        static::$columns = Schema::getColumnListing((new static)->getTable());

        static::created(function ($model) {
            $model->insertMeta();
        });

        static::deleted(function ($model) {
            $model->deleteMeta();
        });
    }

    /**
     * Override performUpdate to ensure meta is always persisted,
     * even when only meta attributes (no table columns) are dirty.
     */
    protected function performUpdate(Builder $query)
    {
        $result = parent::performUpdate($query);

        // Always attempt to update meta after a save(), regardless of
        // whether table columns were dirty (the parent fires 'updated'
        // only when table-level $dirty is non-empty).
        $this->updateMeta();

        return $result;
    }

    /**
     * Initialize the trait on each model instance.
     */
    public function initializeHasMetaTable(): void
    {
        $this->dirtyMeta = [];
        $this->insertMeta = [];
        $this->metaCache = null;
    }

    /**
     * Update changed meta attributes via updateOrCreate.
     */
    public function updateMeta(): void
    {
        if (empty($this->dirtyMeta)) {
            return;
        }

        DB::transaction(function () {
            $foreignKey = $this->getForeignKey();
            $localKey = $this->getKeyName();

            foreach ($this->dirtyMeta as $key => $value) {
                $this->meta()->updateOrCreate(
                    [
                        $foreignKey => $this->$localKey,
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        });

        $this->refreshMetaCache();
        $this->dirtyMeta = [];
    }

    /**
     * Batch insert meta records when a model is created.
     */
    public function insertMeta(): void
    {
        if (empty($this->insertMeta)) {
            return;
        }

        DB::transaction(function () {
            $foreignKey = $this->getForeignKey();
            $localKey = $this->getKeyName();

            $createData = [];
            foreach ($this->insertMeta as $key => $value) {
                $createData[] = [
                    $foreignKey => $this->$localKey,
                    'key' => $key,
                    'value' => $value,
                ];
            }

            $this->meta()->createMany($createData);
        });

        $this->refreshMetaCache();
        $this->insertMeta = [];
    }

    /**
     * Delete all meta records when a model is deleted.
     */
    public function deleteMeta(): void
    {
        $this->meta()->delete();
        $this->metaCache = null;
    }

    /**
     * Get cached table column names for this model.
     */
    public static function getColumns(): array
    {
        return static::$columns;
    }

    /**
     * Load all meta key-value pairs into cache.
     */
    protected function loadMetaCache(): array
    {
        if ($this->metaCache === null) {
            $this->metaCache = [];

            if ($this->exists) {
                $this->metaCache = $this->meta()
                    ->pluck('value', 'key')
                    ->all();
            }
        }

        return $this->metaCache;
    }

    /**
     * Force refresh the meta cache from the database.
     */
    public function refreshMetaCache(): static
    {
        $this->metaCache = null;
        $this->loadMetaCache();

        return $this;
    }

    /**
     * Get all meta as a key-value array.
     */
    public function getAllMeta(): array
    {
        return $this->loadMetaCache();
    }

    /**
     * Get a specific meta value by key with an optional default.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        $meta = $this->loadMetaCache();

        return $meta[$key] ?? $default;
    }

    /**
     * Set one or more meta values.
     *
     * @param  string|array  $key  A meta key string, or an associative array of key-value pairs.
     * @param  mixed  $value  The value when $key is a string.
     */
    public function setMeta(string|array $key, mixed $value = null): static
    {
        $entries = is_array($key) ? $key : [$key => $value];
        $foreignKey = $this->getForeignKey();
        $localKey = $this->getKeyName();

        DB::transaction(function () use ($entries, $foreignKey, $localKey) {
            foreach ($entries as $k => $v) {
                $this->meta()->updateOrCreate(
                    [
                        $foreignKey => $this->$localKey,
                        'key' => $k,
                    ],
                    [
                        'value' => $v,
                    ]
                );
            }
        });

        $this->refreshMetaCache();

        return $this;
    }

    /**
     * Remove one or more meta entries by key.
     *
     * @param  string|array  $keys
     */
    public function removeMeta(string|array $keys): static
    {
        $keys = is_array($keys) ? $keys : [$keys];

        $this->meta()->whereIn('key', $keys)->delete();

        $this->refreshMetaCache();

        return $this;
    }

    /**
     * Check if a meta key exists.
     */
    public function hasMeta(string $key): bool
    {
        $meta = $this->loadMetaCache();

        return array_key_exists($key, $meta);
    }

    /**
     * Separate dirty attributes into database columns vs. meta for update.
     */
    protected function getDirtyForUpdate(): array
    {
        $dirty = $this->getDirty();
        $columns = static::getColumns();

        $this->dirtyMeta = [];
        foreach ($dirty as $key => $value) {
            if (! in_array($key, $columns)) {
                $this->dirtyMeta[$key] = $value;
                unset($dirty[$key]);
            }
        }

        return $dirty;
    }

    /**
     * Separate attributes into database columns vs. meta for insert.
     */
    protected function getAttributesForInsert(): array
    {
        $attributes = $this->getAttributes();
        $columns = static::getColumns();

        $this->insertMeta = [];
        foreach ($attributes as $key => $value) {
            if (! in_array($key, $columns)) {
                $this->insertMeta[$key] = $value;
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    /**
     * Override isFillable: meta keys (non-table columns) are always fillable.
     */
    public function isFillable($key): bool
    {
        $columns = static::getColumns();
        if (in_array($key, $columns)) {
            return parent::isFillable($key);
        }

        return true;
    }

    /**
     * Override fillableFromArray to allow meta attributes through.
     */
    protected function fillableFromArray(array $attributes): array
    {
        if (count($this->getFillable()) > 0 && ! static::$unguarded) {
            $columns = static::getColumns();
            $nonFillableColumns = array_diff($columns, $this->fillable);

            return array_diff_key($attributes, array_flip($nonFillableColumns));
        }

        return $attributes;
    }

    /**
     * Override getAttribute to transparently access meta values.
     * Uses cached meta to avoid N+1 queries.
     */
    public function getAttribute($key)
    {
        $attr = parent::getAttribute($key);
        if ($attr !== null) {
            return $attr;
        }

        // Don't query meta for relationship methods or known table columns
        if (method_exists($this, $key) || in_array($key, static::getColumns())) {
            return $attr;
        }

        $meta = $this->loadMetaCache();
        $value = $meta[$key] ?? null;

        if ($value !== null) {
            $this->attributes[$key] = $value;
        }

        return $value;
    }

    /**
     * Define the HasMany relationship to the meta table.
     */
    public function meta()
    {
        $foreignKey = $this->getForeignKey();
        $localKey = $this->getKeyName();

        $instance = $this->newRelatedInstance(Meta::class);
        $instance->setTable($this->getTable() . '_meta');
        $instance->setForeignKey($foreignKey);

        return $this->newHasMany(
            $instance->newQuery(),
            $this,
            $instance->getTable() . '.' . $foreignKey,
            $localKey
        );
    }
}
