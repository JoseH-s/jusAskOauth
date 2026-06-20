<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tenant')->index();
            $table->string('nome');
            $table->string('email');
            $table->string('cpf');
            $table->string('oab');
            $table->string('endereco')->nullable();
            $table->string('numero')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('pais')->nullable();
            $table->string('cep')->nullable();
            $table->timestamps();

            // Unicidade por tenant.
            $table->unique(['tenant', 'email']);
            $table->unique(['tenant', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
