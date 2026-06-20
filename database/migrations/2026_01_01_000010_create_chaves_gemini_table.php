<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chaves_gemini', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();

            $table->string('tenant')->index();

            $table->string('apelido');       // nome amigável, ex: "Principal", "Projeto X"
            $table->string('chave');         // API key do Google Gemini

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chaves_gemini');
    }
};
