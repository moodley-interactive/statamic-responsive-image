<?php

namespace Mia\ImageRenderer\Traits;

use Bepsvpt\Blurhash\Facades\BlurHash;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use League\Glide\Server;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetRepository;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Imaging\ImageGenerator;

trait BlurHashStringTrait
{
    /**
     * Generates BlurHash strings for all assets
     */
    public function generate(AssetRepository $assets, $output = false)
    {
        $assets = $assets->all()->filter(function (Asset $asset) {
            return $asset->isImage() && $asset->extension() !== 'svg';
        });
        if ($output) {
            $this->info("Generating blurhash & dominant_color strings for {$assets->count()} assets.");
            $this->getOutput()->progressStart($assets->count());
        }
        $assets->each(function (Asset $asset) use ($output) {
            $this->generateBlurHashString($asset);
            if ($output) {
                $this->getOutput()->progressAdvance();
            }
        });

        return 0;
    }

    /**
     * Get Color
     */
    protected function getColor($path)
    {
        // get the filesystems path prefix
        $pathPrefix = $path->getFileSystem()->getAdapter()->getPathPrefix();
        // assemble the full path to the image
        $fullPath = $pathPrefix . $path->getPath();
        // create palette and return the dominant color
        $palette = Palette::fromFileName($fullPath);
        $extractor = new ColorExtractor($palette, Color::fromHexToInt('#f1f1f1'));
        return Color::fromIntToHex($extractor->extract(1)[0]);
    }

    /**
     * Generate BlurHash string for one asset
     */
    public function generateBlurHashString($asset)
    {
        $assetFromFacade = AssetFacade::findById($asset->id());

        $blurhashFromMeta = $assetFromFacade->get("blurhash");
        $dominantColorFromMeta = $assetFromFacade->get("dominant_color");
        $imageGenerator = app(ImageGenerator::class);
        $server = app(Server::class);

        // generate a small version of the image, to make blurhashes life easier and to support files on s3
        $path = $imageGenerator->generateByAsset($asset, [
            'w' => 120,
        ]);

        if (!$blurhashFromMeta) {
            $hash = BlurHash::encode($server->getCache()->read($path));
            $asset->set("blurhash", $hash);
        }

        if (!$dominantColorFromMeta) {
            $color = $this->getColor($server->getCache()->get($path));
            $asset->set("dominant_color", $color);
        }
        $asset->writeMeta($meta = $asset->generateMeta());
    }
}
