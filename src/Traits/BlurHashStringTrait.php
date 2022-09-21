<?php

namespace Mia\ImageRenderer\Traits;

use Bepsvpt\Blurhash\Facades\BlurHash;
use JonasKohl\ColorExtractor\Color;
use JonasKohl\ColorExtractor\Palette;
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
        // create palette and return the dominant color
        $palette = Palette::fromFileName($path);
        $topFive = $palette->getMostUsedColors(5);
        $most_used_color = array_key_first($topFive);
        $most_used_hex = Color::fromIntToHex($most_used_color);
        $color = new hex($most_used_hex);
        $white = new Hex('#fff');
        $hsl_value_muted = $color->mix($white, config('statamic-image-renderer.background_color_mute_percent'));
        $muted = "#" . implode("", $hsl_value_muted->values());
        return $muted;
    }

	/**
     * Returns the glide manager.
     *
     * @return \Statamic\Imaging\GlideManager | \Statamic\Imaging\GlideServer
     */
    private function getGlideManager()
	{
    	return class_exists("\Statamic\Imaging\GlideManager") ? app("\Statamic\Imaging\GlideManager") : app("\Statamic\Imaging\GlideServer");
	}

    /**
     * Returns the glide server.
     *
     * @return \League\Glide\Server
     */
    private function getGlideServer()
	{
		$manager = $this->getGlideManager();
    	return $manager instanceof \Statamic\Imaging\GlideManager ? $manager->server() : $manager->create();
	}

    /**
     * Returns the glide cache path.
     *
     * @return string
     */
    private function getCachePath($path)
	{
		$manager = $this->getGlideManager();
		$server = $this->getGlideServer();

		// Statamic 3.3+
		if ($manager instanceof \Statamic\Imaging\GlideManager) {
			$storage = $this->getGlideManager()->cacheDisk();
			return $storage->path($path);
		} else {
			// Get the filesystems path prefix
			$pathPrefix = $manager->cachePath();
			// Assemble the full path to the image
			$fullPath = $pathPrefix.'/'.$server->getCache()->get($path)->getPath();
			return $fullPath;
		}
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

        // generate a small version of the image, to make blurhashes life easier and to support files on s3
        $path = $imageGenerator->generateByAsset($asset, [
            'w' => 120,
        ]);

        if (!$blurhashFromMeta) {
            $hash = BlurHash::encode($this->getGlideServer()->getCache()->read($path));
            $asset->set("blurhash", $hash);
        }

        if (!$dominantColorFromMeta) {
            $asset->set("dominant_color", $this->getColor($this->getCachePath($path)));
        }

		$asset->save(); // Save the meta data and clear the cache
    }
}
