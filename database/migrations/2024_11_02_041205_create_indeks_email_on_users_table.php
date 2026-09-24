<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS index_email ON users USING HASH (email);');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email', 'index_email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS index_email;');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('index_email');
            });
        }
    }
};
