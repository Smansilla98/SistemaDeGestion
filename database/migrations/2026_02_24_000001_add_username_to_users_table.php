<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Rellenar username a partir del email para usuarios existentes
        $users = DB::table('users')->orderBy('id')->get();
        $used = [];
        foreach ($users as $user) {
            $base = $user->email ? str_replace('.', '_', explode('@', $user->email)[0] ?? 'user') : 'user';
            $username = $base;
            $n = 0;
            while (in_array($username, $used, true)) {
                $n++;
                $username = $base . $n;
            }
            $used[] = $username;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
