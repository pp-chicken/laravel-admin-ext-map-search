<?php

namespace l552121229\laravelAdminExtMapSearch;

use Encore\Admin\Admin;
use Encore\Admin\Form;
use Illuminate\Support\ServiceProvider;

class MapSearchServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(MapSearchExtension $extension)
    {
        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'laravel-admin-map-search');
        }

        Admin::booting(function () {
            Form::extend('map_search', MapSearch::class);
        });
    }
}
