<?php

namespace Zynfly\LaravelMeta\Traits;

use Illuminate\Support\Facades\Schema;
use Zynfly\LaravelMeta\Models\Meta;

trait HasMetaTable
{
    protected static $columns = [];

    protected $dirtyMeta = [];
    protected $insertMeta = [];

    /**
     * boot the trait
     *
     * @return void
     */
    static protected function bootHasMetaTable()
    {
        static::$columns = Schema::getColumnListing((new static)->getTable());

        static::updated(function ($model) {
            $model->updateMeta();
        });

        static::created(function ($model) {
            $model->insertMeta();
        });

        static::deleted(function ($model) {
            $model->deleteMeta();
        });
    }


    public function updateMeta()
    {
        $foreignKey = $this->getForeignKey();
        $localKey = $this->getKeyName();

        foreach ($this->dirtyMeta as $key => $value) {
            $this->meta()->updateOrCreate(
                [
                    $foreignKey => $this->$localKey,
                    'key' => $key
                ],
                [
                    'value' => $value
                ]
            );
        }
    }

    public function insertMeta()
    {
        $foreignKey = $this->getForeignKey();
        $localKey = $this->getKeyName();

        $createData = [];

        foreach ($this->insertMeta as $key => $value) {
            $createData[] = [
                $foreignKey => $this->$localKey,
                'key' => $key,
                'value' => $value
            ];
        }

        $this->meta()->createMany($createData);
    }

    public function deleteMeta()
    {
        $foreignKey = $this->getForeignKey();
        $localKey = $this->getKeyName();

        $this->meta()->where($foreignKey, $this->$localKey)->delete();
    }


    static public function getColumns()
    {
        return static::$columns;
    }

    /**
     * Get the attributes that have been changed since the last sync for an update operation.
     *
     * @return array
     */
    protected function getDirtyForUpdate()
    {
        $dirty = $this->getDirty();
        $columns = static::getColumns();
        // move the dirty attributes to the meta array
        $this->dirtyMeta = [];
        foreach ($dirty as $key => $value) {
            if (!in_array($key, $columns)) {
                $this->dirtyMeta[$key] = $value;
                unset($dirty[$key]);
            }
        }

        return $dirty;
    }

    protected function getAttributesForInsert()
    {
        $attributes = $this->getAttributes();
        $columns = static::getColumns();
        foreach ($attributes as $key => $value) {
            if (!in_array($key, $columns)) {
                $this->insertMeta[$key] = $value;
                unset($attributes[$key]);
            }
        }
        return $attributes;
    }

    public function isFillable($key)
    {
        $columns = static::getColumns();
        if (in_array($key, $columns)) {
            return parent::isFillable($key);
        }
        return true;
    }

    protected function fillableFromArray(array $attributes)
    {
        if (count($this->getFillable()) > 0 && !static::$unguarded) {
            $columns = static::getColumns();
            $nonFillableColumns = array_diff($columns, $this->fillable);
            return array_diff_key($attributes, array_flip($nonFillableColumns));
        }

        return $attributes;
    }

    public function getAttribute($key)
    {
        $attr = parent::getAttribute($key);
        if ($attr !== null) {
            return $attr;
        }
        $attr = $this->meta()->where('key', $key)->first()?->value;
        if ($attr) {
            $this->fill([$key => $attr]);
        }
        return $attr;
    }

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
