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
    public function boot()
    {
        Admin::booting(function () {
            Form::extend('map_search', MapSearch::class);
        });
    }
}
