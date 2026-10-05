<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce WIB / Asia/Jakarta timezone
        date_default_timezone_set('Asia/Jakarta');

        // Check and ensure SQLite database file exists
        if (config('database.default') === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if ($dbPath && $dbPath !== ':memory:') {
                $dir = dirname($dbPath);
                if (! file_exists($dir)) {
                    @mkdir($dir, 0755, true);
                }
                if (! file_exists($dbPath)) {
                    @touch($dbPath);
                }
            }
        }
    }
}
