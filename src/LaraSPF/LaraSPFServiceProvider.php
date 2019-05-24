<?php

namespace LaraSPF;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LaraSPFServiceProvider extends ServiceProvider
{
    use Operators\Filterable;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/laraspf.php'   => config_path('laraspf.php'),
        ]);
        
        // Macros
        Builder::macro(config('filter.macros.filter'), function ($value = null) {
            return Filterable::filter($value, $this);
        });

        Builder::macro(config('filter.macros.filterAndGet'), function ($value = null) {
            return Filterable::filterAndGet($value, $this);
        });

        Collection::macro(config('filter.macros.filterAndGet'), function ($value = null) {
            return Filterable::filterAndGetCollection($value, $this);
        });
    }

    public function register()
    {
        $this->setupConfig();
    }

    /**
     * Get the Configuration
     */
    private function setupConfig()
    {
        $this->mergeConfigFrom(realpath(__DIR__ . '/../config/laraspf.php'), 'laraspf');
    }
}
