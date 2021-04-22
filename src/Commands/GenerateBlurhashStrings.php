<?php

namespace Mia\ImageRenderer\Commands;

use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Assets\AssetRepository;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Image;
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

	protected function getColor($asset)
    {
        $palette = Palette::fromFilename($asset->resolvedPath());
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

        $this->info("Generating blurhash strings for {$assets->count()} assets.");

        $this->getOutput()->progressStart($assets->count());
        $assets->each(function (Asset $asset) {
          $hash = BlurHash::encode($asset->resolvedPath());
		  $color =$this->getColor($asset);
          $asset->set("blurhash", $hash);
          $asset->set("dominant_color", $color);
          $asset->writeMeta($meta = $asset->generateMeta());
          $this->getOutput()->progressAdvance();
        });
        return 0;
    }
}
