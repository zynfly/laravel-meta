<?php

namespace Zynfly\LaravelMeta;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LaravelMeta
{
    /**
     * Create a meta table for the given table.
     *
     * @param  string  $table  The parent table name.
     * @param  string|null  $foreignKey  Custom foreign key name (defaults to singular_table_id).
     */
    public function createMetaTableFor(string $table, ?string $foreignKey = null): void
    {
        $foreignKey = $foreignKey ?? Str::singular($table).'_id';

        $valueColumnType = config('meta.value_column_type', 'text');

        Schema::create("{$table}_meta", function ($table) use ($foreignKey, $valueColumnType) {
            $table->id();
            $table->unsignedBigInteger($foreignKey)->index();
            $table->string('key');
            $table->{$valueColumnType}('value')->nullable();
            $table->timestamps();

            $table->index([$foreignKey, 'key']);
        });
    }

    /**
     * Drop the meta table for the given table.
     */
    public function dropMetaTableFor(string $tableName): void
    {
        Schema::dropIfExists("{$tableName}_meta");
    }
}
