<?php

namespace App\Domain\Export\Services;

use App\Domain\Export\ListExportRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExcelExportService
{
    public function __construct(
        private readonly ListExportRegistry $registry,
    ) {}

    /**
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public function download(string $exportKey, iterable $rows): StreamedResponse
    {
        $headers = $this->registry->headers($exportKey);
        $filename = $this->registry->filenamePrefix($exportKey).'-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($headers as $columnIndex => $header) {
                $sheet->setCellValue([$columnIndex + 1, 1], $header);
            }

            $rowIndex = 2;

            foreach ($rows as $row) {
                foreach (array_values($row) as $columnIndex => $value) {
                    $sheet->setCellValue([$columnIndex + 1, $rowIndex], $value ?? '');
                }

                $rowIndex++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
