<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->renameColumn('encerrado', 'ativo');
        });

        // Inverte os valores booleanos: encerrado=false(aberto) → ativo=true; encerrado=true(fechado) → ativo=false
        DB::statement('UPDATE processos SET ativo = NOT ativo');
    }

    public function down(): void
    {
        DB::statement('UPDATE processos SET ativo = NOT ativo');

        Schema::table('processos', function (Blueprint $table) {
            $table->renameColumn('ativo', 'encerrado');
        });
    }
};
