<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Display backup and restore center.
     */
    public function index(): View
    {
        $backups = $this->backupService->getAvailableBackups();
        $dbPath = $this->backupService->getDatabasePath();

        return view('backup.index', compact('backups', 'dbPath'));
    }

    /**
     * Create an immediate SQLite backup.
     */
    public function create(): RedirectResponse
    {
        try {
            $backupPath = $this->backupService->createDatabaseBackup();
            $filename = basename($backupPath);

            return redirect()->route('backup.index')
                ->with('success', "Backup database lokal berhasil dibuat: {$filename}");
        } catch (Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Gagal membuat backup: '.$e->getMessage());
        }
    }

    /**
     * Download a specific SQLite backup file.
     */
    public function download(string $filename): BinaryFileResponse|RedirectResponse
    {
        $safeFilename = basename($filename);
        $fullPath = $this->backupService->getBackupDirectory().DIRECTORY_SEPARATOR.$safeFilename;

        if (! file_exists($fullPath)) {
            return redirect()->route('backup.index')
                ->with('error', 'File backup tidak ditemukan.');
        }

        return response()->download($fullPath, $safeFilename);
    }

    /**
     * Restore database from uploaded SQLite file.
     */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:102400'], // max 100MB
        ]);

        try {
            $file = $request->file('backup_file');
            $tempPath = $file->getRealPath();

            $result = $this->backupService->restoreDatabase($tempPath);

            $msg = 'Database berhasil dipulihkan! '.
                "Data dipulihkan: {$result['counts']['products']} produk, ".
                "{$result['counts']['categories']} kategori, ".
                "{$result['counts']['services']} layanan, ".
                "{$result['counts']['transactions']} transaksi. ".
                "(Backup pengaman otomatis dibuat: {$result['safety_backup']})";

            return redirect()->route('backup.index')->with('success', $msg);
        } catch (Exception $e) {
            return redirect()->route('backup.index')->with('error', 'Pemulihan gagal: '.$e->getMessage());
        }
    }

    /**
     * Export all data as JSON.
     */
    public function exportJson(): StreamedResponse
    {
        $json = $this->backupService->exportJson();
        $dateStr = Carbon::now('Asia/Jakarta')->format('Ymd_His');
        $filename = "kasir_petshop_data_{$dateStr}.json";

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Export specific data as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse|RedirectResponse
    {
        $type = $request->input('type', 'products'); // 'products', 'transactions', 'stock_movements'

        try {
            $csv = $this->backupService->exportCsv($type);
            $dateStr = Carbon::now('Asia/Jakarta')->format('Ymd_His');
            $filename = "kasir_petshop_{$type}_{$dateStr}.csv";

            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        } catch (Exception $e) {
            return redirect()->route('backup.index')->with('error', $e->getMessage());
        }
    }
}
