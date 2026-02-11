<?php

namespace Zynfly\LaravelMeta\Models;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $foreignKey = 'parent_id';

    protected $guarded = [];

    public function setForeignKey(string $foreignKey): void
    {
        $this->foreignKey = $foreignKey;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the value of the meta entry, with optional type casting.
     */
    public function getTypedValue(string $type = 'string'): mixed
    {
        return match ($type) {
            'int', 'integer' => (int) $this->value,
            'float', 'double' => (float) $this->value,
            'bool', 'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }
}
