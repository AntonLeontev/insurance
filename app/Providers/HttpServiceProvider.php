<?php

namespace App\Providers;

use App\Enums\Ffd;
use App\Services\Atol\Exceptions\AtolException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Http::macro('atol', function () {
            if (empty(auth()->user()->agency->ffd)) {
                abort(500, 'Не указан ФФД в настройках Атол');
            }

            $version = auth()->user()->agency->ffd === Ffd::FFD1_05 ? 'v4' : 'v5';

            return Http::baseUrl(config('services.atol.base_url').$version)
                ->asJson()
                ->throw(function (Response $response) {
                    throw new AtolException($response);
                });
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
