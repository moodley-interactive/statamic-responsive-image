<?php

namespace Mia\ImageRenderer\Listeners;

use Statamic\Events\AssetSaved;
use Illuminate\Support\Facades\Cache;

class SaveAssetListener
{
    public function handle(AssetSaved $event)
    {
		Cache::forget('image_' . $event->asset->path());
    }
}
