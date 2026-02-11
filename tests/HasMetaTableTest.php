<?php

use Workbench\App\Models\Post;

beforeEach(function () {
    // Reset static columns cache between tests
    Post::getColumns();
});

/*
|--------------------------------------------------------------------------
| Create with Meta
|--------------------------------------------------------------------------
*/

it('creates a model with meta attributes', function () {
    $post = Post::create([
        'title' => 'Hello World',
        'content' => 'Post content',
        'subtitle' => 'A subtitle',
        'author_name' => 'John',
    ]);

    expect($post->title)->toBe('Hello World');
    expect($post->content)->toBe('Post content');

    // Meta should be stored in the meta table
    $this->assertDatabaseHas('posts_meta', [
        'post_id' => $post->id,
        'key' => 'subtitle',
        'value' => 'A subtitle',
    ]);
    $this->assertDatabaseHas('posts_meta', [
        'post_id' => $post->id,
        'key' => 'author_name',
        'value' => 'John',
    ]);
});

it('does not create meta records when no meta attributes are provided', function () {
    $post = Post::create([
        'title' => 'Simple Post',
    ]);

    expect($post->meta()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Read Meta
|--------------------------------------------------------------------------
*/

it('reads meta attributes transparently via getAttribute', function () {
    $post = Post::create([
        'title' => 'Hello',
        'subtitle' => 'World',
    ]);

    // Reload from database to clear in-memory state
    $post = Post::find($post->id);

    expect($post->title)->toBe('Hello');
    expect($post->subtitle)->toBe('World');
});

it('returns null for non-existent meta key', function () {
    $post = Post::create(['title' => 'Hello']);
    $post = Post::find($post->id);

    expect($post->nonexistent_key)->toBeNull();
});

it('caches meta to avoid N+1 queries', function () {
    $post = Post::create([
        'title' => 'Hello',
        'meta_key_1' => 'value1',
        'meta_key_2' => 'value2',
    ]);

    $post = Post::find($post->id);

    // First access loads the cache
    $post->meta_key_1;

    // Count queries for second access - should use cache
    $queryCount = 0;
    \Illuminate\Support\Facades\DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $val = $post->meta_key_2;
    expect($val)->toBe('value2');
    expect($queryCount)->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Update Meta
|--------------------------------------------------------------------------
*/

it('updates meta attributes when model is updated', function () {
    $post = Post::create([
        'title' => 'Hello',
        'subtitle' => 'Old Subtitle',
    ]);

    $post->subtitle = 'New Subtitle';
    $post->save();

    $this->assertDatabaseHas('posts_meta', [
        'post_id' => $post->id,
        'key' => 'subtitle',
        'value' => 'New Subtitle',
    ]);

    // Should have only 1 subtitle record, not 2
    expect($post->meta()->where('key', 'subtitle')->count())->toBe(1);
});

it('can update both table columns and meta at the same time', function () {
    $post = Post::create([
        'title' => 'Original',
        'subtitle' => 'Original Sub',
    ]);

    $post->title = 'Updated Title';
    $post->subtitle = 'Updated Sub';
    $post->save();

    $post = Post::find($post->id);
    expect($post->title)->toBe('Updated Title');
    expect($post->subtitle)->toBe('Updated Sub');
});

/*
|--------------------------------------------------------------------------
| Delete Meta
|--------------------------------------------------------------------------
*/

it('deletes all meta when model is deleted', function () {
    $post = Post::create([
        'title' => 'Hello',
        'subtitle' => 'World',
        'author_name' => 'John',
    ]);

    $postId = $post->id;
    $post->delete();

    $this->assertDatabaseMissing('posts_meta', [
        'post_id' => $postId,
    ]);
});

/*
|--------------------------------------------------------------------------
| setMeta / getMeta / removeMeta / hasMeta / getAllMeta
|--------------------------------------------------------------------------
*/

it('can set and get meta using helper methods', function () {
    $post = Post::create(['title' => 'Hello']);

    $post->setMeta('color', 'blue');

    expect($post->getMeta('color'))->toBe('blue');
});

it('can set multiple meta at once', function () {
    $post = Post::create(['title' => 'Hello']);

    $post->setMeta([
        'color' => 'blue',
        'size' => 'large',
    ]);

    expect($post->getMeta('color'))->toBe('blue');
    expect($post->getMeta('size'))->toBe('large');
});

it('returns default value for non-existent meta key via getMeta', function () {
    $post = Post::create(['title' => 'Hello']);

    expect($post->getMeta('missing', 'default_val'))->toBe('default_val');
    expect($post->getMeta('missing'))->toBeNull();
});

it('can remove a meta key', function () {
    $post = Post::create(['title' => 'Hello']);
    $post->setMeta('color', 'blue');

    $post->removeMeta('color');

    expect($post->hasMeta('color'))->toBeFalse();
    $this->assertDatabaseMissing('posts_meta', [
        'post_id' => $post->id,
        'key' => 'color',
    ]);
});

it('can remove multiple meta keys', function () {
    $post = Post::create(['title' => 'Hello']);
    $post->setMeta(['a' => '1', 'b' => '2', 'c' => '3']);

    $post->removeMeta(['a', 'b']);

    expect($post->hasMeta('a'))->toBeFalse();
    expect($post->hasMeta('b'))->toBeFalse();
    expect($post->hasMeta('c'))->toBeTrue();
});

it('can check if a meta key exists', function () {
    $post = Post::create(['title' => 'Hello']);
    $post->setMeta('color', 'blue');

    expect($post->hasMeta('color'))->toBeTrue();
    expect($post->hasMeta('nonexistent'))->toBeFalse();
});

it('can get all meta as key-value array', function () {
    $post = Post::create([
        'title' => 'Hello',
        'color' => 'blue',
        'size' => 'large',
    ]);

    $post = Post::find($post->id);
    $allMeta = $post->getAllMeta();

    expect($allMeta)->toBe([
        'color' => 'blue',
        'size' => 'large',
    ]);
});

/*
|--------------------------------------------------------------------------
| Meta Relationship
|--------------------------------------------------------------------------
*/

it('provides a meta hasMany relationship', function () {
    $post = Post::create([
        'title' => 'Hello',
        'subtitle' => 'World',
    ]);

    expect($post->meta)->toHaveCount(1);
    expect($post->meta->first()->key)->toBe('subtitle');
    expect($post->meta->first()->value)->toBe('World');
});

/*
|--------------------------------------------------------------------------
| getColumns
|--------------------------------------------------------------------------
*/

it('returns table columns', function () {
    $columns = Post::getColumns();

    expect($columns)->toContain('id');
    expect($columns)->toContain('title');
    expect($columns)->toContain('content');
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
});

/*
|--------------------------------------------------------------------------
| Edge Cases
|--------------------------------------------------------------------------
*/

it('handles meta with empty string value', function () {
    $post = Post::create(['title' => 'Hello']);
    $post->setMeta('note', '');

    $this->assertDatabaseHas('posts_meta', [
        'post_id' => $post->id,
        'key' => 'note',
        'value' => '',
    ]);
});

it('handles meta with long text value', function () {
    $longValue = str_repeat('a', 5000);
    $post = Post::create(['title' => 'Hello']);
    $post->setMeta('long_text', $longValue);

    expect($post->getMeta('long_text'))->toBe($longValue);
});

it('setMeta is chainable', function () {
    $post = Post::create(['title' => 'Hello']);

    $result = $post->setMeta('a', '1')->setMeta('b', '2');

    expect($result)->toBeInstanceOf(Post::class);
    expect($post->getMeta('a'))->toBe('1');
    expect($post->getMeta('b'))->toBe('2');
});

it('removeMeta is chainable', function () {
    $post = Post::create(['title' => 'Hello']);
    $post->setMeta(['a' => '1', 'b' => '2']);

    $result = $post->removeMeta('a');

    expect($result)->toBeInstanceOf(Post::class);
});

it('refreshMetaCache reloads from database', function () {
    $post = Post::create(['title' => 'Hello']);
    $post->setMeta('color', 'blue');

    // Manually update the database
    $post->meta()->where('key', 'color')->update(['value' => 'red']);

    // Cache still has old value
    expect($post->getMeta('color'))->toBe('blue');

    // After refresh, should have new value
    $post->refreshMetaCache();
    expect($post->getMeta('color'))->toBe('red');
});

/*
|--------------------------------------------------------------------------
| isFillable
|--------------------------------------------------------------------------
*/

it('allows meta attributes to be mass assigned', function () {
    $post = new Post;
    expect($post->isFillable('some_meta_key'))->toBeTrue();
});

it('respects fillable rules for table columns', function () {
    $post = new Post;
    // 'title' is in $fillable so it should be fillable
    expect($post->isFillable('title'))->toBeTrue();
});
