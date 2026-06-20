<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tenant')->index();
            $table->string('papel')->default('advogado'); // dono | advogado | assistente
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'empresa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membros');
    }
};
