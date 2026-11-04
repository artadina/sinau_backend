<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-A03: allow Admins to leave the optional unit duration blank.
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->integer('duration')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('units')->whereNull('duration')->update(['duration' => 0]);

        Schema::table('units', function (Blueprint $table): void {
            $table->integer('duration')->nullable(false)->change();
        });
    }
};
