<?php

namespace App\Domain\BackupRestore\Services;

use App\Domain\BackupRestore\DatasetRegistry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SqlRestoreExecutor
{
    public function __construct(
        private readonly DatasetRegistry $registry,
        private readonly TableTruncator $truncator,
    ) {}

    public function execute(string $datasetKey, string $sql, bool $replace): int
    {
        $allowedTables = $datasetKey === 'full'
            ? $this->registry->fullDatabaseTables()
            : $this->registry->tables($datasetKey);

        $statements = $this->parseStatements($sql);
        $executed = 0;

        DB::transaction(function () use ($statements, $allowedTables, $replace, $datasetKey, &$executed): void {
            if ($replace) {
                $this->truncateForReplace($datasetKey, $allowedTables);
            }

            foreach ($statements as $statement) {
                $table = $this->tableFromStatement($statement);

                if ($table !== null && ! in_array($table, $allowedTables, true)) {
                    throw new InvalidArgumentException("SQL references disallowed table [{$table}].");
                }

                if ($this->isDangerous($statement)) {
                    throw new InvalidArgumentException('SQL contains disallowed statements.');
                }

                DB::unprepared($statement);
                $executed++;
            }
        });

        return $executed;
    }

    /**
     * @return list<string>
     */
    private function parseStatements(string $sql): array
    {
        $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;

        return collect(explode(';', $sql))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => $part !== '')
            ->values()
            ->all();
    }

    private function tableFromStatement(string $statement): ?string
    {
        if (preg_match('/^\s*INSERT\s+INTO\s+`?([a-z0-9_]+)`?/i', $statement, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^\s*DELETE\s+FROM\s+`?([a-z0-9_]+)`?/i', $statement, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function isDangerous(string $statement): bool
    {
        return (bool) preg_match(
            '/\b(DROP|ALTER|CREATE|TRUNCATE|ATTACH|DETACH|GRANT|REVOKE)\b/i',
            $statement,
        );
    }

    /**
     * @param  list<string>  $tables
     */
    private function truncateForReplace(string $datasetKey, array $tables): void
    {
        if (in_array($datasetKey, ['pharmacy_sales', 'administration'], true)) {
            throw new InvalidArgumentException('Replace mode is not allowed for this dataset.');
        }

        if ($datasetKey === 'inventory' && DB::table('stock_counts')->where('status', 'submitted')->exists()) {
            throw new InvalidArgumentException('Cannot replace inventory while a stock count is submitted.');
        }

        $this->truncator->deleteAll(array_reverse($tables));
    }
}
