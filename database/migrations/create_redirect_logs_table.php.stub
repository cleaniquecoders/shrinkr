<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('redirect_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(config('shrinkr.models.url'));
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('platform')->nullable();
            $table->string('platform_version')->nullable();
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_desktop')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('redirect_logs');
    }
};
