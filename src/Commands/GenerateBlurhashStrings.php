<?php

namespace Mia\ImageRenderer\Commands;

use Illuminate\Console\Command;
use Mia\ImageRenderer\Traits\BlurHashStringTrait;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Assets\AssetRepository;

class GenerateBlurhashStrings extends Command
{
    use RunsInPlease, BlurHashStringTrait;
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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(AssetRepository $assets)
    {
        return $this->generate($assets, true);
    }
}
