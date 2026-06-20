<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processo_contatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tenant')->index();
            $table->enum('tipo', ['email', 'telefone']);
            $table->string('valor', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_contatos');
    }
};
