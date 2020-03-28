<?php

namespace Qichamao;

use Illuminate\Support\ServiceProvider;

class QichamaoServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config.php' => config_path('qichamao.php'),
            ], 'qichamao');
        }

        $this->mergeConfigFrom(__DIR__ . '/config.php', 'qichamao');

        app()->singleton('qichamao', function () {
            return new Qichamao(config('qichamao.appkey'), config('qichamao.seckey'));
        });
    }
}
