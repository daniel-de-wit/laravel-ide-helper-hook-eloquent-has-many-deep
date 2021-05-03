<?php

declare(strict_types=1);

namespace DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Tests\Unit\Providers;

use DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Hooks\EloquentHasManyDeepHook;
use DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Providers\LaravelIdeHelperHookEloquentHasManyDeepServiceProvider;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class LaravelIdeHelperHookEloquentHasManyDeepServiceProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LaravelIdeHelperHookEloquentHasManyDeepServiceProvider
     */
    protected $provider;

    /**
     * @var Application|MockInterface
     */
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app      = Mockery::mock(Application::class);
        $this->provider = new LaravelIdeHelperHookEloquentHasManyDeepServiceProvider($this->app);
    }

    /**
     * @test
     */
    public function it_adds_the_eloquent_has_many_deep_hook_to_the_config(): void
    {
        /** @var Config|MockInterface $config */
        $config = Mockery::mock(Config::class)
            ->shouldReceive('get')
            ->with('ide-helper.model_hooks', [])
            ->andReturn([])
            ->getMock()
            ->shouldReceive('set')
            ->with('ide-helper.model_hooks', [EloquentHasManyDeepHook::class])
            ->getMock();

        $this->app->shouldReceive('isProduction')->andReturnFalse();
        $this->app->shouldReceive('get')->with('config')->andReturn($config);

        $this->provider->register();
    }

    /**
     * @test
     */
    public function it_does_not_add_the_eloquent_has_many_deep_hook_to_the_config_when_in_production(): void
    {
        $this->app->shouldReceive('isProduction')->andReturnTrue();
        $this->app->shouldNotReceive('get')->with('config');

        $this->provider->register();
    }
}
