<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_cnj', function (Blueprint $table) {
            $table->string('cpf_responsavel', 11)->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('token_cnj', function (Blueprint $table) {
            $table->dropColumn('cpf_responsavel');
        });
    }
};
