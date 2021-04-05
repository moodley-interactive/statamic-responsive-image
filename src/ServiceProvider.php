<?php

namespace Valschr\ImageRenderer;

use Statamic\Providers\AddonServiceProvider;
use Valschr\ImageRenderer\Commands\GenerateBlurhashStrings;
use Valschr\ImageRenderer\Tags\ResponsiveImageTag;


class ServiceProvider extends AddonServiceProvider
{
  protected $commands = [
    GenerateBlurhashStrings::class,
  ];
  protected $tags = [
    ResponsiveImageTag::class,
  ];
}
