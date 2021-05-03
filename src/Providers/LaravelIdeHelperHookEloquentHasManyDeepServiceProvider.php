<?php

namespace DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Providers;

use DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Hooks\EloquentHasManyDeepHook;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\ServiceProvider;

class LaravelIdeHelperHookEloquentHasManyDeepServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->isProduction()) {
            return;
        }

        /** @var Config $config */
        $config = $this->app->get('config');

        $config->set('ide-helper.model_hooks', array_merge([
            EloquentHasManyDeepHook::class,
        ], $config->get('ide-helper.model_hooks', [])));
    }
}
