<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite criar processos sem cliente associado.
     * Útil para pre-cadastrar processos que serão vinculados
     * a um cliente no momento do cadastro deste.
     */
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
        });
    }
};
