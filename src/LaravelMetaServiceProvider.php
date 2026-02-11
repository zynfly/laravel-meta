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
            ->name('meta')
            ->hasConfigFile()
            ->hasCommand(LaravelMetaCommand::class);
    }
}
