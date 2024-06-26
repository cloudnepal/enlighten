<?php

namespace Styde\Enlighten\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Filesystem\Filesystem;
use Styde\Enlighten\Console\Commands\ExportDocumentationCommand;
use Styde\Enlighten\Console\Commands\FreshCommand;
use Styde\Enlighten\Console\Commands\GenerateDocumentationCommand;
use Styde\Enlighten\Console\Commands\InstallCommand;
use Styde\Enlighten\Console\Commands\MigrateCommand;
use Styde\Enlighten\Console\ContentRequest;
use Styde\Enlighten\Console\DocumentationExporter;

trait RegistersConsoleConfiguration
{
    private function registerCommands(): void
    {
        $this->app->singleton(MigrateCommand::class, fn ($app) => new MigrateCommand($app['migrator'], $app['events']));

        $this->app->singleton(ExportDocumentationCommand::class, fn ($app) => new ExportDocumentationCommand(
            new DocumentationExporter(
                $app[Filesystem::class],
                new ContentRequest($app[HttpKernel::class]),
            )
        ));

        $this->commands([
            InstallCommand::class,
            FreshCommand::class,
            MigrateCommand::class,
            GenerateDocumentationCommand::class,
            ExportDocumentationCommand::class
        ]);
    }

    private function registerPublishing(): void
    {
        $this->publishes([
            $this->packageRoot('config') => base_path('config'),
        ], ['enlighten', 'enlighten-config']);

        $this->publishes([
            $this->packageRoot('dist') => public_path('vendor/enlighten'),
            $this->packageRoot('/preview.png') => public_path('vendor/enlighten/img/preview.png'),
        ], ['enlighten', 'enlighten-build']);

        $this->publishes([
            $this->packageRoot('resources/views') => resource_path('views/vendor/enlighten'),
        ], 'enlighten-views');

        $this->publishes([
            $this->packageRoot('resources/lang') => resource_path('lang/vendor/enlighten'),
        ], 'enlighten-translations');
    }
}
