<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mcp_tokens', function (Blueprint $table) {
            $table->string('tenant')->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('mcp_tokens', function (Blueprint $table) {
            $table->dropIndex(['tenant']);
            $table->dropColumn('tenant');
        });
    }
};
