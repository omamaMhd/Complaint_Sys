<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\CitizenRepositoryInterface;
use App\Repositories\Eloquent\CitizenRepository;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Eloquent\EmployeeRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
{
    $this->app->bind(
        CitizenRepositoryInterface::class,
        CitizenRepository::class
    );

    $this->app->bind(
        EmployeeRepositoryInterface::class,
        EmployeeRepository::class
    );

    // ❌ احذف CitizenService binding
}

    /*
    public function register(): void
    {
      $this->app->bind(
          CitizenRepositoryInterface::class,
          CitizenRepository::class
      );

      // bind service too (optional, auto resolves)
      $this->app->singleton(\App\Services\CitizenService::class, function($app) {
          return new \App\Services\CitizenService($app->make(\App\Repositories\Contracts\CitizenRepositoryInterface::class));
      });  
       $this->app->bind(
        EmployeeRepositoryInterface::class,
        EmployeeRepository::class
    );

    }

    /**
     * Bootstrap any application services.
     */
    
    public function boot(): void
    {
        //
    }
}
