<?php

namespace Zynfly\LaravelMeta;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LaravelMeta
{
    public function createMetaTableFor(string $table, $foreignKey = null)
    {
        $foreignKey = $foreignKey ?? Str::singular($table) . '_id';
        Schema::create("{$table}_meta", function ($table) use ($foreignKey) {
            $table->id();
            $table->bigInteger($foreignKey);
            $table->string('key');
            $table->string('value');
            $table->timestamps();
        });
    }


    public function dropMetaTableFor(string $tableName)
    {
        Schema::dropIfExists("{$tableName}_meta");
    }
}
