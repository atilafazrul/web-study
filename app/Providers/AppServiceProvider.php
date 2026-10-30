<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('users')) {
                    $user = User::firstOrCreate(
                        ['id' => 1],
                        [
                            'name' => 'Student',
                            'email' => 'student@university.edu',
                            'password' => bcrypt('password123'),
                        ]
                    );
                    $view->with('currentUser', $user);
                }
            } catch (\Throwable $e) {
                // Fallback if db is not ready
            }
        });
    }
}
