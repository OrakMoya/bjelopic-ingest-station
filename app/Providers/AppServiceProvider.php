<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
        $connections = ['sqlite', 'jobs_db'];
        foreach ($connections as $connection) {
            DB::connection($connection)
                ->statement(
                    '
                PRAGMA synchronous = NORMAL;
                PRAGMA mmap_size = 134217728; -- 128 megabytes
                PRAGMA cache_size = 1000000000;
                PRAGMA foreign_keys = true;
                PRAGMA busy_timeout = 5000;
                PRAGMA temp_store = memory;
                '
                );
        }
*/
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
    }
}
