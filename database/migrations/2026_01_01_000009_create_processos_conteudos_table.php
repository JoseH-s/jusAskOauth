<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela separada de conteudo_processos (anotações manuais).
     * Esta armazena os dados oficiais retornados pela API PDPJ.
     */
    public function up(): void
    {
        Schema::create('processos_conteudos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('processo_id')
                ->constrained('processos')
                ->cascadeOnDelete();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();

            $table->string('tenant')->index();

            $table->string('numero_processo')->nullable();
            $table->dateTime('data_hora_ajuizamento')->nullable();
            $table->decimal('valor_acao', 15, 2)->nullable();
            $table->dateTime('data_hora_ultima_distribuicao')->nullable();
            $table->string('assunto', 500)->nullable();
            $table->longText('conteudo_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processos_conteudos');
    }
};
