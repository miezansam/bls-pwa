<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Sms\EnvoyeurSms;
use App\Services\Sms\EnvoyeurSmsJournal;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

        $this->app->bind(EnvoyeurSms::class, fn () => match (config('bls.sms.driver')) {
            'journal' => new EnvoyeurSmsJournal(),
            default => throw new RuntimeException('Fournisseur SMS inconnu : ' . config('bls.sms.driver')),
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
