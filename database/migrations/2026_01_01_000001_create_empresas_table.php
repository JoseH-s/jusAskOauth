<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cnpj')->nullable()->unique();
            $table->string('oab')->nullable();
            // Identificador canonico do tenant: CNPJ se houver, senao a OAB.
            $table->string('tenant')->unique();
            $table->boolean('is_pessoa_fisica')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
