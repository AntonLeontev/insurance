<?php

namespace App\Providers;

use App\Services\Atol\Enums\ApiVersion;
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
        Http::macro('atol', function (ApiVersion $version) {
            return Http::baseUrl(config('services.atol.base_url').$version->value)
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
