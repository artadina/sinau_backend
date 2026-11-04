<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR-S01, FR-SA04, FR-SA06
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('Learner')->change();
        });

        DB::table('users')->where('role', 'Learner')->update(['role' => 'learner']);
        DB::table('users')->where('role', 'Admin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'Superadmin')->update(['role' => 'superadmin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['learner', 'admin', 'superadmin'])
                ->default('learner')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('learner')->change();
        });

        DB::table('users')->where('role', 'learner')->update(['role' => 'Learner']);
        DB::table('users')->where('role', 'admin')->update(['role' => 'Admin']);
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'Superadmin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['Learner', 'Admin', 'Superadmin'])
                ->default('Learner')
                ->change();
        });
    }
};
