<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Zynfly\LaravelMeta\Traits\HasMetaTable;

class Post extends Model
{
    use HasMetaTable;

    protected $fillable = ['title', 'content'];
}
