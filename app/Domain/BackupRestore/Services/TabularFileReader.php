<?php

namespace App\Domain\BackupRestore\Services;

use App\Domain\BackupRestore\DatasetRegistry;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TabularFileReader
{
    public function __construct(
        private readonly DatasetRegistry $registry,
    ) {}

    /**
     * @return array<string, list<array<string, string|null>>>
     */
    public function readDatasetTables(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv' => $this->readCsv($file->getRealPath()),
            'xlsx' => $this->readXlsx($file->getRealPath()),
            default => throw new InvalidArgumentException('Import file must be .csv or .xlsx.'),
        };

        return $this->groupByTableMarker($rows);
    }

    /**
     * @return list<list<string|null>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException('Could not read CSV file.');
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(
                fn ($value) => $value === '' ? null : (string) $value,
                $row,
            );
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<string|null>>
     */
    private function readXlsx(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray() as $row) {
            $rows[] = array_map(function ($value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                return is_numeric($value) && ! is_string($value)
                    ? (string) $value
                    : trim((string) $value);
            }, $row);
        }

        return $rows;
    }

    /**
     * @param  list<list<string|null>>  $rows
     * @return array<string, list<array<string, string|null>>>
     */
    private function groupByTableMarker(array $rows): array
    {
        $tables = [];
        $currentTable = null;
        $headers = [];

        foreach ($rows as $row) {
            $first = $row[0] ?? null;

            if (is_string($first) && str_starts_with($first, DatasetRegistry::TABLE_MARKER)) {
                $currentTable = substr($first, strlen(DatasetRegistry::TABLE_MARKER));
                $headers = [];

                continue;
            }

            if ($currentTable === null) {
                continue;
            }

            if ($headers === []) {
                $headers = array_map(fn ($h) => $h ?? '', $row);

                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $record = [];

            foreach ($headers as $index => $column) {
                if ($column === '') {
                    continue;
                }

                $record[$column] = $row[$index] ?? null;
            }

            $tables[$currentTable][] = $record;
        }

        return $tables;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty();
    }
}
