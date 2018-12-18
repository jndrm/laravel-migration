<?php

namespace Drmer\Laravel\Migration;

use Illuminate\Support\ServiceProvider;
use Drmer\Laravel\Migration\Support\StrMigration;
use Drmer\Laravel\Migration\Foundation\Testing\TestResponseMigration;

class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        StrMigration::migrate();
        TestResponseMigration::migrate();
    }
}
