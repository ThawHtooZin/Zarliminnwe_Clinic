<?php

namespace App\Domain\BackupRestore;

use InvalidArgumentException;

class DatasetRegistry
{
    public const TABLE_MARKER = '#TABLE:';

    /**
     * @return array<string, array{label: string, tables: list<string>, column_exclude?: array<string, list<string>>}>
     */
    public function all(): array
    {
        return config('backup_datasets.datasets', []);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @return array{label: string, tables: list<string>, column_exclude?: array<string, list<string>>}
     */
    public function get(string $key): array
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown backup dataset [{$key}].");
        }

        return $this->all()[$key];
    }

    /**
     * @return list<string>
     */
    public function tables(string $key): array
    {
        return $this->get($key)['tables'];
    }

    public function label(string $key): string
    {
        return $this->get($key)['label'];
    }

    /**
     * @return list<string>
     */
    public function excludedColumns(string $key, string $table): array
    {
        $exclude = $this->get($key)['column_exclude'][$table] ?? [];

        return $exclude;
    }

    /**
     * @return list<string>
     */
    public function fullDatabaseTables(): array
    {
        $exclude = config('backup_datasets.full_database_exclude', []);

        return collect(\Illuminate\Support\Facades\Schema::getTableListing())
            ->filter(fn (string $table): bool => ! in_array($table, $exclude, true))
            ->values()
            ->all();
    }
}
