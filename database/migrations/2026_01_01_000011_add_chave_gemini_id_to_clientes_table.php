<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('chave_gemini_id')
                ->nullable()
                ->after('cep')
                ->constrained('chaves_gemini')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['chave_gemini_id']);
            $table->dropColumn('chave_gemini_id');
        });
    }
};
