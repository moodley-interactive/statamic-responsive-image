<?php

namespace Mia\ImageRenderer\Listeners;

use Mia\ImageRenderer\Traits\BlurHashStringTrait;
use Statamic\Events\AssetUploaded;

class UploadAssetListener
{
    use BlurHashStringTrait;

    public function handle(AssetUploaded $event)
    {
        $this->generateBlurHashString($event->asset);
    }
}
