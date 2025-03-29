<?php

namespace App\Providers;

use App\Models\WebProperty;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Share WebProperty data with the 'layouts.app' view
        View::composer('layouts.app', function ($view) {
            $webProperty = WebProperty::first(); // Fetch the first WebProperty (or adjust logic)
            $view->with('webProperty', $webProperty);
        });
    }

    public function register()
    {
        //
    }
}
