<?php

namespace App\Providers;

use App\FileEntry;
use App\Services\Admin\GetAnalyticsHeaderData;
use App\Services\Entries\SetPermissionsOnEntry;
use Common\Admin\Analytics\Actions\GetAnalyticsHeaderDataAction;
use Common\Files\FileEntry as CommonFileEntry;
use Common\Workspaces\ActiveWorkspace;
use Illuminate\Support\ServiceProvider;

const WORKSPACED_RESOURCES = [
    \App\FileEntry::class,
];

const WORKSPACE_HOME_ROUTE = '/drive';

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            GetAnalyticsHeaderDataAction::class,
            GetAnalyticsHeaderData::class
        );

        $this->app->bind(CommonFileEntry::class, FileEntry::class);

        $this->app->singleton(SetPermissionsOnEntry::class, function () {
            return new SetPermissionsOnEntry();
        });
    }
}
