<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('telefone', 20)->nullable()->after('email');
            $table->string('estado', 2)->nullable()->after('cidade');
            $table->unique(['tenant', 'telefone'], 'clientes_tenant_telefone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_tenant_telefone_unique');
            $table->dropColumn(['telefone', 'estado']);
        });
    }
};
