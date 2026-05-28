<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = DB::table('users')->whereNull('role_id')->get(['id', 'role']);

        foreach ($users as $user) {
            $roleId = DB::table('roles')->where('slug', $user->role)->value('id');

            if ($roleId === null) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->update(['role_id' => null]);
    }
};
