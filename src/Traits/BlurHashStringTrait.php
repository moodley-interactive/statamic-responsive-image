<?php

namespace Mia\ImageRenderer\Traits;

use kornrunner\Blurhash\Blurhash;
use League\ColorExtractor\Color;
use League\ColorExtractor\Palette;
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
        $count = 0;
        $assets = $assets->all()->filter(function (Asset $asset) {
            return $asset->isImage() && $asset->extension() !== 'svg';
        });
        if ($output) {
            $this->info("Generating blurhash & dominant_color strings for {$assets->count()} assets.");
            $this->getOutput()->progressStart($assets->count());
        }
        $assets->each(function (Asset $asset) use ($output, &$count) {
            $this->generateBlurHashString($asset);
            if ($output) {
                $this->getOutput()->progressAdvance();
            }
            $count++;
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

        $blurhashFromMeta = $assetFromFacade->get("blurhash");
        $dominantColorFromMeta = $assetFromFacade->get("dominant_color");
        $imageGenerator = app(ImageGenerator::class);
        $server = app(Server::class);

        // generate a small version of the image, to make blurhashes life easier and to support files on s3
        $path = $imageGenerator->generateByAsset($asset, [
            'w' => 40,
        ]);

        $image = imagecreatefromstring($server->getCache()->read($path));
        if ($image) {

            $width = imagesx($image);
            $height = imagesy($image);

            $pixels = [];
            for ($y = 0; $y < $height; ++$y) {
                $row = [];
                for ($x = 0; $x < $width; ++$x) {
                    $index = imagecolorat($image, $x, $y);
                    $colors = imagecolorsforindex($image, $index);

                    $row[] = [$colors['red'], $colors['green'], $colors['blue']];
                }
                $pixels[] = $row;
            }

            $components_x = 4;
            $components_y = 3;
            $blurhash = Blurhash::encode($pixels, $components_x, $components_y);

            $pixels = Blurhash::decode($blurhash, $width, $height);

            $image  = imagecreatetruecolor($width, $height);
            for ($y = 0; $y < $height; ++$y) {
                for ($x = 0; $x < $width; ++$x) {
                    [$r, $g, $b] = $pixels[$y][$x];
                    imagesetpixel($image, $x, $y, imagecolorallocate($image, $r, $g, $b));
                }
            }
            ob_start();
            imagejpeg($image);
            $image_data = ob_get_contents();
            ob_end_clean();
            $image_data_base64 = base64_encode($image_data);

            $asset->set("blurhash", $blurhash);
            $asset->set("blurhash_base64", $image_data_base64);
        }
        if (!$blurhashFromMeta) {
        }

        if (!$dominantColorFromMeta) {
            $color = $this->getColor($server->getCache()->get($path));
            $asset->set("dominant_color", $color);
        }
        $asset->writeMeta($meta = $asset->generateMeta());
    }
}
