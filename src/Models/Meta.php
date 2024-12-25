<?php

namespace Zynfly\LaravelMeta\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Meta extends Model
{
    protected $foreignKey = 'parent_id';

    protected $guarded = [];

    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;
    }
}
