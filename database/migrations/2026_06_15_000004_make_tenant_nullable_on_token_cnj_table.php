<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_cnj', function (Blueprint $table) {
            $table->string('tenant')->nullable()->change();
        });

        DB::table('token_cnj')
            ->where('tenant', 'sem-tenant')
            ->update(['tenant' => null]);
    }

    public function down(): void
    {
        DB::table('token_cnj')
            ->whereNull('tenant')
            ->orWhere('tenant', '')
            ->update(['tenant' => 'sem-tenant']);

        Schema::table('token_cnj', function (Blueprint $table) {
            $table->string('tenant')->nullable(false)->change();
        });
    }
};
