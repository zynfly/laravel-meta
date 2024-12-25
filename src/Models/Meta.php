<?php

namespace Zynfly\LaravelMeta\Models;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $foreignKey = 'parent_id';

    protected $guarded = [];

    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;
    }
}
