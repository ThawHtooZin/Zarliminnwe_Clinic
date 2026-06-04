<?php

namespace App\Http\Controllers\BackupRestore;

use App\Domain\BackupRestore\DatasetRegistry;
use App\Domain\BackupRestore\Services\BackupRestoreLogger;
use App\Domain\BackupRestore\Services\DatasetExporter;
use App\Domain\BackupRestore\Services\DatasetImporter;
use App\Domain\BackupRestore\Services\FullDatabaseBackupService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupRestoreController extends Controller
{
    public function __construct(
        private readonly DatasetRegistry $registry,
        private readonly DatasetExporter $exporter,
        private readonly DatasetImporter $importer,
        private readonly FullDatabaseBackupService $fullBackup,
        private readonly BackupRestoreLogger $logger,
    ) {}

    public function index(): View
    {
        return view('backup-restore.index', [
            'datasets' => $this->registry->all(),
            'restorePhrase' => config('backup_datasets.restore_confirmation_phrase'),
        ]);
    }

    public function exportCsv(string $dataset): StreamedResponse
    {
        $this->assertDataset($dataset);
        $filename = $dataset.'-'.now()->format('Y-m-d-His').'.csv';

        $this->logger->log('backup.dataset.exported', [
            'dataset' => $dataset,
            'format' => 'csv',
            'filename' => $filename,
        ]);

        return response()->streamDownload(
            fn () => print ($this->exporter->toCsv($dataset)),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function exportSql(string $dataset): StreamedResponse
    {
        $this->assertDataset($dataset);
        $filename = $dataset.'-'.now()->format('Y-m-d-His').'.sql';

        $this->logger->log('backup.dataset.exported', [
            'dataset' => $dataset,
            'format' => 'sql',
            'filename' => $filename,
        ]);

        return response()->streamDownload(
            fn () => print ($this->exporter->toSql($dataset)),
            $filename,
            ['Content-Type' => 'application/sql'],
        );
    }

    public function import(Request $request, string $dataset): RedirectResponse
    {
        $this->assertDataset($dataset);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:51200'],
            'replace' => ['sometimes', 'boolean'],
        ]);

        try {
            $count = $this->importer->importCsvOrXlsx(
                $dataset,
                $request->file('file'),
                $request->boolean('replace'),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->redirectWithError($exception->getMessage());
        }

        $this->logger->log('backup.dataset.imported', [
            'dataset' => $dataset,
            'rows' => $count,
            'replace' => $request->boolean('replace'),
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);

        return $this->redirectWithStatus("Imported {$count} row(s) into {$this->registry->label($dataset)}.");
    }

    public function restoreSql(Request $request, string $dataset): RedirectResponse
    {
        $this->assertDataset($dataset);

        $request->validate([
            'file' => ['required', 'file', 'mimes:sql,txt', 'max:51200'],
            'replace' => ['sometimes', 'boolean'],
        ]);

        try {
            $count = $this->importer->restoreSql(
                $dataset,
                $request->file('file'),
                $request->boolean('replace'),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->redirectWithError($exception->getMessage());
        }

        $this->logger->log('backup.dataset.restored', [
            'dataset' => $dataset,
            'statements' => $count,
            'replace' => $request->boolean('replace'),
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);

        return $this->redirectWithStatus("Ran {$count} SQL statement(s) for {$this->registry->label($dataset)}.");
    }

    public function exportDatabase(): StreamedResponse
    {
        $filename = 'database-'.now()->format('Y-m-d-His').'.sql';

        $this->logger->log('backup.database.exported', ['filename' => $filename]);

        return response()->streamDownload(
            fn () => print ($this->fullBackup->toSql()),
            $filename,
            ['Content-Type' => 'application/sql'],
        );
    }

    public function restoreDatabase(Request $request): RedirectResponse
    {
        if (! config('backup_datasets.allow_database_restore', true)) {
            return $this->redirectWithError('Full database restore is disabled on this server.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:sql,txt', 'max:102400'],
            'confirmation' => ['required', 'string'],
            'replace' => ['accepted'],
        ]);

        $phrase = config('backup_datasets.restore_confirmation_phrase');

        if ($request->input('confirmation') !== $phrase) {
            return $this->redirectWithError("Type {$phrase} to confirm full database restore.");
        }

        $sql = file_get_contents($request->file('file')->getRealPath());

        if ($sql === false) {
            return $this->redirectWithError('Could not read SQL file.');
        }

        try {
            $count = app(\App\Domain\BackupRestore\Services\SqlRestoreExecutor::class)
                ->execute('full', $sql, true);
        } catch (InvalidArgumentException $exception) {
            return $this->redirectWithError($exception->getMessage());
        }

        $this->logger->log('backup.database.restored', [
            'statements' => $count,
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);

        return $this->redirectWithStatus("Full database restore completed ({$count} statements).");
    }

    private function redirectWithStatus(string $message): RedirectResponse
    {
        return redirect()->route('backup-restore.index')->with('status', $message);
    }

    private function redirectWithError(string $message): RedirectResponse
    {
        return redirect()->route('backup-restore.index')->with('error', $message);
    }

    private function assertDataset(string $dataset): void
    {
        if (! $this->registry->has($dataset)) {
            abort(404);
        }
    }
}
