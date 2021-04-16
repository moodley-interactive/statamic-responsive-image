<?php

namespace Valschr\ImageRenderer\Tags;

use Statamic\Facades\Asset as AssetFacade;
use Statamic\Tags\Tags;

class ResponsiveImageTag extends Tags
{
  protected static $handle = 'resp';

  public function index()
  {
      $this->src = $this->params["src"];
	  if (is_string($this->src)) {
		$asset = AssetFacade::findByUrl($this->src);
	  } else {
		$asset = $this->src->value();
	  }
	  ray($asset);
      return view('responsive-images::responsiveImage', [
		  "blurhash" => $asset->meta()["data"]["blurhash"],
	  ]);
  }

  public function wildcard($tag)
  {
      $this->params->put('src', $this->context->get($tag));

      return $this->index();
  }
}
