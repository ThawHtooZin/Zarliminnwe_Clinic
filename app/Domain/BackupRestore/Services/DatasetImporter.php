<?php

namespace App\Domain\BackupRestore\Services;

use App\Domain\BackupRestore\DatasetRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class DatasetImporter
{
    public function __construct(
        private readonly DatasetRegistry $registry,
        private readonly TabularFileReader $tabularReader,
        private readonly SqlRestoreExecutor $sqlRestore,
        private readonly TableTruncator $truncator,
    ) {}

    public function importCsvOrXlsx(string $datasetKey, UploadedFile $file, bool $replace): int
    {
        $allowedTables = $this->registry->tables($datasetKey);
        $tables = $this->tabularReader->readDatasetTables($file);
        $imported = 0;

        foreach (array_keys($tables) as $tableName) {
            $this->assertTableAllowed($tableName, $allowedTables);
        }

        DB::transaction(function () use ($allowedTables, $tables, $replace, $datasetKey, &$imported): void {
            if ($replace) {
                $this->truncateTables($datasetKey, array_reverse($allowedTables));
            }

            foreach ($allowedTables as $table) {
                if (! isset($tables[$table])) {
                    continue;
                }

                $this->assertTableAllowed($table, $allowedTables);
                $imported += $this->importRows($datasetKey, $table, $tables[$table]);
            }
        });

        return $imported;
    }

    public function restoreSql(string $datasetKey, UploadedFile $file, bool $replace): int
    {
        $sql = file_get_contents($file->getRealPath());

        if ($sql === false) {
            throw new InvalidArgumentException('Could not read SQL file.');
        }

        return $this->sqlRestore->execute($datasetKey, $sql, $replace);
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     */
    private function importRows(string $datasetKey, string $table, array $rows): int
    {
        $count = 0;
        $exclude = $this->registry->excludedColumns($datasetKey, $table);
        $columns = collect(Schema::getColumnListing($table))
            ->reject(fn (string $column): bool => in_array($column, $exclude, true))
            ->values()
            ->all();

        foreach ($rows as $row) {
            $payload = [];

            foreach ($columns as $column) {
                if (! array_key_exists($column, $row)) {
                    continue;
                }

                $payload[$column] = $this->castValue($row[$column]);
            }

            if ($payload === []) {
                continue;
            }

            if (isset($payload['id']) && $payload['id'] !== null && $payload['id'] !== '') {
                $id = (int) $payload['id'];
                DB::table($table)->updateOrInsert(['id' => $id], $payload);
            } else {
                unset($payload['id']);
                DB::table($table)->insert($payload);
            }

            $count++;
        }

        return $count;
    }

    /**
     * @param  list<string>  $allowedTables
     */
    private function assertTableAllowed(string $table, array $allowedTables): void
    {
        if (! in_array($table, $allowedTables, true)) {
            throw new InvalidArgumentException("Table [{$table}] is not part of this dataset.");
        }
    }

    /**
     * @param  list<string>  $tables
     */
    private function truncateTables(string $datasetKey, array $tables): void
    {
        if (in_array($datasetKey, ['pharmacy_sales', 'administration'], true)) {
            throw new InvalidArgumentException('Replace mode is not allowed for this dataset.');
        }

        if ($datasetKey === 'inventory' && DB::table('stock_counts')->where('status', 'submitted')->exists()) {
            throw new InvalidArgumentException('Cannot replace inventory while a stock count is submitted.');
        }

        $this->truncator->deleteAll($tables);
    }

    private function castValue(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value === '0' || $value === '1') {
            return $value;
        }

        if (is_numeric($value) && ! str_contains($value, '.')) {
            return (int) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }
}
