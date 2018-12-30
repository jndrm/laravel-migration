<?php

namespace Drmer\Laravel\Migration;

use Illuminate\Support\ServiceProvider;
use Drmer\Laravel\Migration\Support\StrMigration;
use Drmer\Laravel\Migration\Routing\UrlGeneratorMigration;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Validator;
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
        Validator::extend('uuid', function ($attribute, $value, $parameters, $validator) {
            return Uuid::isValid($value);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        StrMigration::run();
        TestResponseMigration::run();
        UrlGeneratorMigration::run();
    }
}
