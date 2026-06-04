<?php

namespace App\Domain\BackupRestore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableTruncator
{
    /**
     * @param  list<string>  $tables
     */
    public function deleteAll(array $tables): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                DB::table($table)->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
