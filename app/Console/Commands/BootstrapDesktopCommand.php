<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BootstrapDesktopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:desktop-init {--force : Force migration and seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize local SQLite database, run migrations, and seed initial master data for desktop offline execution';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing Kasir Pet Shop Desktop Environment...');

        // 1. Ensure SQLite database directory & file exist
        $dbPath = config('database.connections.sqlite.database');
        $dbDir = dirname($dbPath);

        if (! File::isDirectory($dbDir)) {
            File::makeDirectory($dbDir, 0755, true);
            $this->info("Created database directory: {$dbDir}");
        }

        $isNewDatabase = ! File::exists($dbPath) || File::size($dbPath) === 0;
        if (! File::exists($dbPath)) {
            File::put($dbPath, '');
            $this->info("Created SQLite database file: {$dbPath}");
        }

        // 2. Ensure Storage directories exist
        $storageDirs = [
            storage_path('app/public'),
            storage_path('app/backups'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($storageDirs as $dir) {
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        // 3. Run Migrations
        $this->info('Running database migrations...');
        Artisan::call('migrate', ['--force' => true], $this->output);

        // 4. Seed initial master data if new or empty
        $categoryCount = Category::count();
        $settingCount = Setting::count();

        if ($isNewDatabase || $categoryCount === 0 || $settingCount === 0 || $this->option('force')) {
            $this->info('Seeding initial master categories and store settings...');
            Artisan::call('db:seed', ['--force' => true], $this->output);
        }

        $this->info('Kasir Pet Shop Desktop is ready to operate offline!');

        return Command::SUCCESS;
    }
}
