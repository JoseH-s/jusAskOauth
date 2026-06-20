<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dateTime('data_hora_ajuizamento')->nullable()->after('encerrado');
            $table->decimal('valor_acao', 15, 2)->nullable()->after('data_hora_ajuizamento');
            $table->dateTime('data_hora_ultima_distribuicao')->nullable()->after('valor_acao');
            $table->string('assunto', 500)->nullable()->after('data_hora_ultima_distribuicao');
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropColumn([
                'data_hora_ajuizamento',
                'valor_acao',
                'data_hora_ultima_distribuicao',
                'assunto',
            ]);
        });
    }
};
