<?php

namespace Mia\ImageRenderer;

use Statamic\Providers\AddonServiceProvider;
use Mia\ImageRenderer\Commands\GenerateBlurhashStrings;
use Mia\ImageRenderer\Tags\ResponsiveImageTag;


class ServiceProvider extends AddonServiceProvider
{
	public function boot()
	{
		parent::boot();
	}

	protected $commands = [
		GenerateBlurhashStrings::class,
	];
	protected $tags = [
		ResponsiveImageTag::class,
	];
}
