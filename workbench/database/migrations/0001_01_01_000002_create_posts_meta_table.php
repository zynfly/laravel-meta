<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->index();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts_meta');
    }
};
