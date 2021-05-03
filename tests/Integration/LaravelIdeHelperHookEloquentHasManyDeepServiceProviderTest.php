<?php

declare(strict_types=1);

namespace DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Tests\Integration;

use DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Hooks\EloquentHasManyDeepHook;
use DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Providers\LaravelIdeHelperHookEloquentHasManyDeepServiceProvider;
use Orchestra\Testbench\TestCase;

class LaravelIdeHelperHookEloquentHasManyDeepServiceProviderTest extends TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelIdeHelperHookEloquentHasManyDeepServiceProvider::class,
        ];
    }

    /**
     * @test
     */
    public function it_adds_the_paperclip_hook_to_the_config(): void
    {
        static::assertContains(EloquentHasManyDeepHook::class, config('ide-helper.model_hooks'));
    }
}
