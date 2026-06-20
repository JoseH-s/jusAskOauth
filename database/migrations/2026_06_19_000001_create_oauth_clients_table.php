<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_id')->unique();
            $table->string('client_secret')->nullable(); // null = public client (PKCE)
            $table->text('redirect_uris');              // JSON array
            $table->timestamps();
        });

        Schema::create('oauth_auth_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 128)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tenant');
            $table->foreignId('client_id')->constrained('oauth_clients')->cascadeOnDelete();
            $table->string('redirect_uri');
            $table->string('code_challenge', 128)->nullable(); // PKCE
            $table->string('code_challenge_method', 10)->nullable();
            $table->string('scope')->default('mcp');
            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 128)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tenant');
            $table->foreignId('client_id')->constrained('oauth_clients')->cascadeOnDelete();
            $table->string('scope')->default('mcp');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_auth_codes');
        Schema::dropIfExists('oauth_clients');
    }
};
