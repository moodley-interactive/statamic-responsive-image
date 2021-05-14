<?php

namespace Mia\ImageRenderer;

use Mia\ImageRenderer\Commands\GenerateBlurhashStrings;
use Mia\ImageRenderer\Listeners\UploadAssetListener;
use Mia\ImageRenderer\Tags\ResponsiveImageTag;
use Statamic\Events\AssetUploaded;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function boot()
    {
        parent::boot();

        $this->handleConfig();
    }

    protected function handleConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/statamic-image-renderer.php', 'statamic-image-renderer');

        $this->publishes([
            __DIR__ . '/../config/statamic-image-renderer.php' => config_path('statamic-image-renderer.php'),
        ], 'statamic-image-renderer-config');
    }

    protected $commands = [
        GenerateBlurhashStrings::class,
    ];
    protected $tags = [
        ResponsiveImageTag::class,
    ];
    protected $listen = [
        AssetUploaded::class => [
            UploadAssetListener::class,
        ],
    ];
}
