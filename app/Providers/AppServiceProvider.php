<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        Mail::alwaysTo('delivered@resend.dev');
        Relation::morphMap([
            'places' => \App\Models\Places::class,
            'tours'  => \App\Models\Tours::class, // إذا كانت تُخزن tours أيضاً
        ]);
    }
}
