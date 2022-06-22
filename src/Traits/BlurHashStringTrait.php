<?php

namespace Mia\ImageRenderer\Traits;

use Bepsvpt\Blurhash\Facades\BlurHash;
use JonasKohl\ColorExtractor\Color;
use JonasKohl\ColorExtractor\Palette;
use League\Glide\Server;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetRepository;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Imaging\ImageGenerator;
use OzdemirBurak\Iris\Color\Hex;

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
        $topFive = $palette->getMostUsedColors(5);
        $most_used_color = array_key_first($topFive);
        $most_used_hex = Color::fromIntToHex($most_used_color);
        $color = new hex($most_used_hex);
        $white = new Hex('#fff');
        $hsl_value_muted = $color->mix($white, 66);
        $muted = "#" . implode("", $hsl_value_muted->values());
        return $muted;
    }

    /**
     * Generate BlurHash string for one asset
     */
    public function generateBlurHashString($asset)
    {
        $assetFromFacade = AssetFacade::findById($asset->id());
		if (!$assetFromFacade) return;
        if (!$assetFromFacade->isImage() || $assetFromFacade->extension() == 'svg') return;
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
