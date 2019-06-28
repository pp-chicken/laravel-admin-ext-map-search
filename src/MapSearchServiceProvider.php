<?php

namespace l552121229\laravelAdminExtMapSearch;

use Encore\Admin\Form;
use Illuminate\Support\ServiceProvider;

class MapSearchServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        Form::extend('map_search', MapSearch::class);
    }
}
