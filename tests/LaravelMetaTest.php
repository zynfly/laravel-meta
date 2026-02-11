<?php

use Illuminate\Support\Facades\Schema;
use Zynfly\LaravelMeta\Facades\LaravelMeta;

it('creates a meta table with correct structure', function () {
    LaravelMeta::createMetaTableFor('users');

    expect(Schema::hasTable('users_meta'))->toBeTrue();
    expect(Schema::hasColumn('users_meta', 'id'))->toBeTrue();
    expect(Schema::hasColumn('users_meta', 'user_id'))->toBeTrue();
    expect(Schema::hasColumn('users_meta', 'key'))->toBeTrue();
    expect(Schema::hasColumn('users_meta', 'value'))->toBeTrue();
    expect(Schema::hasColumn('users_meta', 'created_at'))->toBeTrue();
    expect(Schema::hasColumn('users_meta', 'updated_at'))->toBeTrue();

    // Cleanup
    LaravelMeta::dropMetaTableFor('users');
});

it('creates a meta table with custom foreign key', function () {
    LaravelMeta::createMetaTableFor('users', 'custom_fk');

    expect(Schema::hasColumn('users_meta', 'custom_fk'))->toBeTrue();

    LaravelMeta::dropMetaTableFor('users');
});

it('drops a meta table', function () {
    LaravelMeta::createMetaTableFor('items');
    expect(Schema::hasTable('items_meta'))->toBeTrue();

    LaravelMeta::dropMetaTableFor('items');
    expect(Schema::hasTable('items_meta'))->toBeFalse();
});

it('drops a non-existent meta table without error', function () {
    // dropIfExists should not throw
    LaravelMeta::dropMetaTableFor('nonexistent');
    expect(true)->toBeTrue();
});
