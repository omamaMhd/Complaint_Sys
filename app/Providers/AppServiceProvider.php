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
      $this->app->bind(
          \App\Repositories\Contracts\CitizenRepositoryInterface::class,
          \App\Repositories\Eloquent\CitizenRepository::class
      );

      // bind service too (optional, auto resolves)
      $this->app->singleton(\App\Services\CitizenService::class, function($app) {
          return new \App\Services\CitizenService($app->make(\App\Repositories\Contracts\CitizenRepositoryInterface::class));
      });  
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
