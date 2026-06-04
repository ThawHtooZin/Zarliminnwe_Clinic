<?php

namespace App\Domain\BackupRestore\Services;

use App\Domain\BackupRestore\DatasetRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FullDatabaseBackupService
{
    public function __construct(
        private readonly DatasetRegistry $registry,
        private readonly SqlValueFormatter $sqlValues,
    ) {}

    public function toSql(): string
    {
        $chunks = ['-- full database backup', ''];

        foreach ($this->registry->fullDatabaseTables() as $table) {
            $chunks[] = "-- table: {$table}";
            $chunks = array_merge($chunks, $this->insertStatementsForTable($table));
            $chunks[] = '';
        }

        return implode("\n", $chunks);
    }

    /**
     * @return list<string>
     */
    private function insertStatementsForTable(string $table): array
    {
        $columns = collect(Schema::getColumnListing($table))
            ->reject(fn (string $column): bool => $table === 'users' && in_array($column, ['password', 'remember_token'], true))
            ->values()
            ->all();

        $statements = [];
        $query = DB::table($table);

        if (Schema::hasColumn($table, 'id')) {
            $query->orderBy('id');
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            $values = array_map(
                fn (string $column) => $this->sqlValues->quote($row->{$column} ?? null),
                $columns,
            );

            $statements[] = sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s);',
                $table,
                implode(', ', array_map(fn ($c) => '`'.$c.'`', $columns)),
                implode(', ', $values),
            );
        }

        return $statements;
    }
}
