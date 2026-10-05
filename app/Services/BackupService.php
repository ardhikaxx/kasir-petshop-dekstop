<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PDO;

class BackupService
{
    /**
     * Get path of active SQLite database.
     */
    public function getDatabasePath(): string
    {
        return config('database.connections.sqlite.database');
    }

    /**
     * Get directory where backups are stored.
     */
    public function getBackupDirectory(): string
    {
        $dir = storage_path('app/backups');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Create a timestamped copy of the active SQLite database.
     */
    public function createDatabaseBackup(): string
    {
        $dbPath = $this->getDatabasePath();
        if (! File::exists($dbPath)) {
            throw new Exception("File database tidak ditemukan di {$dbPath}");
        }

        $backupDir = $this->getBackupDirectory();
        $timestamp = Carbon::now('Asia/Jakarta')->format('Ymd_His');
        $backupFilename = "petshop_backup_{$timestamp}.sqlite";
        $targetPath = $backupDir.DIRECTORY_SEPARATOR.$backupFilename;

        File::copy($dbPath, $targetPath);

        return $targetPath;
    }

    /**
     * List all available SQLite backup files.
     *
     * @return array<int, array{filename: string, path: string, size: string, date: string, timestamp: int}>
     */
    public function getAvailableBackups(): array
    {
        $dir = $this->getBackupDirectory();
        $files = File::glob("{$dir}/*.sqlite");

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => $this->formatBytes(File::size($file)),
                'date' => Carbon::createFromTimestamp(File::lastModified($file))
                    ->timezone('Asia/Jakarta')
                    ->format('d/m/Y H:i:s'),
                'timestamp' => File::lastModified($file),
            ];
        }

        usort($backups, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Restore database from an uploaded or specified SQLite file safely.
     *
     * @return array{success: bool, counts: array<string, int>, safety_backup: string}
     */
    public function restoreDatabase(string $sourcePath): array
    {
        if (! File::exists($sourcePath)) {
            throw new InvalidArgumentException('File backup tidak ditemukan.');
        }

        // 1. Verify SQLite Header (Must start with "SQLite format 3\000")
        $handle = fopen($sourcePath, 'rb');
        $header = fread($handle, 16);
        fclose($handle);

        if ($header !== "SQLite format 3\000") {
            throw new InvalidArgumentException('File yang diunggah bukan file SQLite yang valid atau rusak.');
        }

        // 2. Test open in PDO to ensure tables exist
        try {
            $testPdo = new PDO("sqlite:{$sourcePath}");
            $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $requiredTables = ['products', 'categories', 'transactions', 'settings'];
            $existingTables = [];
            $res = $testPdo->query("SELECT name FROM sqlite_master WHERE type='table';");
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $existingTables[] = $row['name'];
            }

            foreach ($requiredTables as $reqTable) {
                if (! in_array($reqTable, $existingTables, true)) {
                    throw new InvalidArgumentException("File backup tidak memiliki tabel penting: [{$reqTable}].");
                }
            }
        } catch (Exception $e) {
            throw new InvalidArgumentException('Gagal memverifikasi isi database backup: '.$e->getMessage());
        }

        // 3. Make safety backup of current active database first
        $safetyBackupPath = $this->createDatabaseBackup();

        // 4. Disconnect active PDO to release lock
        DB::disconnect('sqlite');

        // 5. Replace current database file with validated backup
        $activeDbPath = $this->getDatabasePath();
        File::copy($sourcePath, $activeDbPath);

        // Reconnect
        DB::reconnect('sqlite');

        // 6. Gather counts of restored data
        $counts = [
            'categories' => Category::count(),
            'products' => Product::count(),
            'services' => Service::count(),
            'transactions' => Transaction::count(),
            'stock_movements' => StockMovement::count(),
        ];

        return [
            'success' => true,
            'counts' => $counts,
            'safety_backup' => basename($safetyBackupPath),
        ];
    }

    /**
     * Export complete application state to JSON string.
     */
    public function exportJson(): string
    {
        $payload = [
            'app' => 'Kasir Pet Shop Desktop',
            'version' => '1.0.0',
            'exported_at' => Carbon::now('Asia/Jakarta')->toIso8601String(),
            'settings' => Setting::all()->toArray(),
            'categories' => Category::all()->toArray(),
            'products' => Product::withTrashed()->get()->toArray(),
            'services' => Service::withTrashed()->get()->toArray(),
            'stock_movements' => StockMovement::all()->toArray(),
            'transactions' => Transaction::all()->toArray(),
            'transaction_items' => TransactionItem::all()->toArray(),
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export specific data to CSV format.
     */
    public function exportCsv(string $type): string
    {
        $output = fopen('php://memory', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

        if ($type === 'products') {
            fputcsv($output, ['ID', 'Kategori', 'SKU', 'Barcode', 'Nama Produk', 'Jenis', 'Satuan', 'HPP', 'Harga Jual', 'Stok', 'Stok Min', 'Status']);
            $products = Product::with('category')->get();
            foreach ($products as $p) {
                fputcsv($output, [
                    $p->id,
                    $p->category?->name ?? '-',
                    $p->sku,
                    $p->barcode ?? '-',
                    $p->name,
                    $p->type,
                    $p->unit,
                    $p->cost_price,
                    $p->selling_price,
                    $p->stock,
                    $p->min_stock,
                    $p->is_active ? 'Aktif' : 'Nonaktif',
                ]);
            }
        } elseif ($type === 'transactions') {
            fputcsv($output, ['No Transaksi', 'Tanggal', 'Pelanggan', 'Hewan', 'Subtotal', 'Diskon', 'Pajak', 'Grand Total', 'Metode Bayar', 'Bayar', 'Kembalian', 'Status']);
            $transactions = Transaction::latest('transaction_date')->get();
            foreach ($transactions as $t) {
                fputcsv($output, [
                    $t->transaction_number,
                    $t->transaction_date->format('Y-m-d H:i:s'),
                    $t->customer_name ?? '-',
                    $t->pet_name ? "{$t->pet_name} ({$t->pet_type})" : '-',
                    $t->subtotal,
                    $t->discount_amount,
                    $t->tax_amount,
                    $t->grand_total,
                    $t->payment_method_label,
                    $t->payment_amount,
                    $t->change_amount,
                    $t->status,
                ]);
            }
        } elseif ($type === 'stock_movements') {
            fputcsv($output, ['Tanggal', 'Produk', 'SKU', 'Tipe', 'Qty', 'Stok Sebelum', 'Stok Sesudah', 'No Ref', 'Catatan']);
            $movements = StockMovement::with('product')->latest()->get();
            foreach ($movements as $m) {
                fputcsv($output, [
                    $m->created_at->format('Y-m-d H:i:s'),
                    $m->product?->name ?? '-',
                    $m->product?->sku ?? '-',
                    $m->type_label,
                    $m->quantity,
                    $m->before_stock,
                    $m->after_stock,
                    $m->reference_number ?? '-',
                    $m->notes ?? '-',
                ]);
            }
        } else {
            throw new InvalidArgumentException("Tipe ekspor CSV [{$type}] tidak didukung.");
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent ?: '';
    }

    /**
     * Format bytes to readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
