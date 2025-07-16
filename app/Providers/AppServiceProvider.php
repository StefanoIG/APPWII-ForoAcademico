<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\VoteCasted;
use App\Events\BestAnswerMarked;
use App\Listeners\UpdateReputation;
use App\Listeners\UpdateReputationForBestAnswer;

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
        // Registrar event listeners
        Event::listen(
            VoteCasted::class,
            UpdateReputation::class,
        );

        Event::listen(
            BestAnswerMarked::class,
            UpdateReputationForBestAnswer::class,
        );
    }
}
