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
        // Override database options to fix PHP 8.5+ deprecation warning
        // This ensures our config completely overrides the vendor config
        config([
            'database.connections.mysql.options' => extension_loaded('pdo_mysql') && env('MYSQL_ATTR_SSL_CA') ? [
                1009 => env('MYSQL_ATTR_SSL_CA'), // Use numeric value instead of deprecated PDO::MYSQL_ATTR_SSL_CA
            ] : [],
            'database.connections.mariadb.options' => extension_loaded('pdo_mysql') && env('MYSQL_ATTR_SSL_CA') ? [
                1009 => env('MYSQL_ATTR_SSL_CA'), // Use numeric value instead of deprecated PDO::MYSQL_ATTR_SSL_CA
            ] : [],
        ]);
    }
}
