<?php

namespace Zynfly\LaravelMeta;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Zynfly\LaravelMeta\Commands\LaravelMetaCommand;

class LaravelMetaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-meta')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_meta_table')
            ->hasCommand(LaravelMetaCommand::class);
    }
}
