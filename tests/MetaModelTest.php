<?php

use Zynfly\LaravelMeta\Models\Meta;

it('has a default foreign key', function () {
    $meta = new Meta;

    expect($meta->getForeignKey())->toBe('parent_id');
});

it('can set a custom foreign key', function () {
    $meta = new Meta;
    $meta->setForeignKey('post_id');

    expect($meta->getForeignKey())->toBe('post_id');
});

it('can cast value to integer', function () {
    $meta = new Meta;
    $meta->value = '42';

    expect($meta->getTypedValue('integer'))->toBe(42);
    expect($meta->getTypedValue('int'))->toBe(42);
});

it('can cast value to float', function () {
    $meta = new Meta;
    $meta->value = '3.14';

    expect($meta->getTypedValue('float'))->toBe(3.14);
    expect($meta->getTypedValue('double'))->toBe(3.14);
});

it('can cast value to boolean', function () {
    $meta = new Meta;

    $meta->value = '1';
    expect($meta->getTypedValue('bool'))->toBeTrue();

    $meta->value = 'true';
    expect($meta->getTypedValue('boolean'))->toBeTrue();

    $meta->value = '0';
    expect($meta->getTypedValue('bool'))->toBeFalse();

    $meta->value = 'false';
    expect($meta->getTypedValue('boolean'))->toBeFalse();
});

it('can cast value to array from json', function () {
    $meta = new Meta;
    $meta->value = json_encode(['foo' => 'bar', 'baz' => [1, 2, 3]]);

    $result = $meta->getTypedValue('json');

    expect($result)->toBe(['foo' => 'bar', 'baz' => [1, 2, 3]]);
});

it('returns empty array for invalid json', function () {
    $meta = new Meta;
    $meta->value = 'not json';

    expect($meta->getTypedValue('array'))->toBe([]);
});

it('returns string by default', function () {
    $meta = new Meta;
    $meta->value = 'hello';

    expect($meta->getTypedValue())->toBe('hello');
    expect($meta->getTypedValue('string'))->toBe('hello');
});
