<?php

namespace App\Domain\BackupRestore\Services;

use App\Domain\BackupRestore\DatasetRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatasetExporter
{
    public function __construct(
        private readonly DatasetRegistry $registry,
        private readonly SqlValueFormatter $sqlValues,
    ) {}

    public function toCsv(string $datasetKey): string
    {
        $lines = [];

        foreach ($this->registry->tables($datasetKey) as $table) {
            $lines[] = $this->tableToCsvSection($datasetKey, $table);
        }

        return implode("\n", array_filter($lines));
    }

    public function toSql(string $datasetKey): string
    {
        $chunks = ["-- dataset: {$datasetKey}", ''];

        foreach ($this->registry->tables($datasetKey) as $table) {
            $chunks[] = "-- table: {$table}";
            $chunks = array_merge($chunks, $this->tableToInsertStatements($datasetKey, $table));
            $chunks[] = '';
        }

        return implode("\n", $chunks);
    }

    private function tableToCsvSection(string $datasetKey, string $table): string
    {
        $columns = $this->exportColumns($datasetKey, $table);
        $rows = $this->rowsForTable($table);

        $lines = [DatasetRegistry::TABLE_MARKER.$table, implode(',', $columns)];

        foreach ($rows as $row) {
            $line = [];

            foreach ($columns as $column) {
                $value = $row->{$column} ?? null;
                $line[] = $this->escapeCsv($value);
            }

            $lines[] = implode(',', $line);
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function tableToInsertStatements(string $datasetKey, string $table): array
    {
        $columns = $this->exportColumns($datasetKey, $table);
        $statements = [];
        $rows = $this->rowsForTable($table);

        foreach ($rows as $row) {
            $values = array_map(
                fn (string $column) => $this->sqlValues->quote($row->{$column} ?? null),
                $columns,
            );

            $statements[] = sprintf(
                'INSERT INTO %s (%s) VALUES (%s);',
                $this->wrapTable($table),
                implode(', ', array_map(fn ($c) => $this->wrapColumn($c), $columns)),
                implode(', ', $values),
            );
        }

        return $statements;
    }

    /**
     * @return list<string>
     */
    private function exportColumns(string $datasetKey, string $table): array
    {
        $exclude = $this->registry->excludedColumns($datasetKey, $table);

        return collect(Schema::getColumnListing($table))
            ->reject(fn (string $column): bool => in_array($column, $exclude, true))
            ->values()
            ->all();
    }

    private function escapeCsv(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $string = (string) $value;

        if (str_contains($string, ',') || str_contains($string, '"') || str_contains($string, "\n")) {
            return '"'.str_replace('"', '""', $string).'"';
        }

        return $string;
    }

    private function wrapTable(string $table): string
    {
        return '`'.$table.'`';
    }

    private function wrapColumn(string $column): string
    {
        return '`'.$column.'`';
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function rowsForTable(string $table): \Illuminate\Support\Collection
    {
        $query = DB::table($table);

        if (Schema::hasColumn($table, 'id')) {
            $query->orderBy('id');
        }

        return $query->get();
    }
}
