<?php

namespace App\Console\Commands;

use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-transactions {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus seluruh data transaksi kasir dan kembalikan transaksi ke nol (0 transaksi)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Apakah Anda yakin ingin menghapus seluruh data transaksi? Tindakan ini tidak dapat dibatalkan.', true)) {
            $this->comment('Dibatalkan. Data transaksi tidak diubah.');

            return Command::SUCCESS;
        }

        $this->info('Menghapus seluruh data transaksi...');

        DB::transaction(function () {
            Schema::disableForeignKeyConstraints();

            // 1. Delete all transaction items
            TransactionItem::query()->delete();

            // 2. Delete all transactions
            Transaction::query()->delete();

            // 3. Delete sale stock movements related to transactions
            StockMovement::query()
                ->where('type', 'sale')
                ->orWhere('reference_type', 'transaction')
                ->delete();

            // 4. Reset SQLite auto-increment sequences if sqlite
            if (DB::getDriverName() === 'sqlite') {
                DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('transactions', 'transaction_items')");
            }

            Schema::enableForeignKeyConstraints();
        });

        $this->info('Berhasil! Seluruh riwayat transaksi telah dikosongkan.');
        $this->info('Jumlah transaksi saat ini: '.Transaction::count());

        return Command::SUCCESS;
    }
}
