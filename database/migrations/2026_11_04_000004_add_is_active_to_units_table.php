<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-A03, FR-L30: retain quiz history when a unit is no longer offered.
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
