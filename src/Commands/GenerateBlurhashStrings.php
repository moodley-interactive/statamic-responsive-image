<?php

namespace Mia\ImageRenderer\Commands;

use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetRepository;
use Statamic\Facades\Asset as AssetFacade;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Image;
use League\Glide\Server;
use Statamic\Imaging\ImageGenerator;
use Bepsvpt\Blurhash\Facades\BlurHash;
use League\ColorExtractor\Palette;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Color;

class GenerateBlurhashStrings extends Command
{
    use RunsInPlease;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resp:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate blurhash strings';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle(AssetRepository $assets)
    {
        $assets = $assets->all()->filter(function (Asset $asset) {
            return $asset->isImage() && $asset->extension() !== 'svg';
        });

        $this->info("Generating blurhash & dominant_color strings for {$assets->count()} assets.");

        $this->getOutput()->progressStart($assets->count());
        $assets->each(function (Asset $asset) {
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
            $this->getOutput()->progressAdvance();
        });
        return 0;
    }
}
