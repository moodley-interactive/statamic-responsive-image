<?php

namespace Valschr\ImageRenderer\Tags;

use Statamic\Facades\Asset as AssetFacade;
use Statamic\Tags\Tags;
use Statamic\Facades\Image;

class ResponsiveImageTag extends Tags
{
  protected static $handle = 'resp';

  // Following this article for best practices
  // https://medium.com/hceverything/applying-srcset-choosing-the-right-sizes-for-responsive-images-at-different-breakpoints-a0433450a4a3
  protected $sizes = [640, 768, 1024, 1366, 1600, 1920];

  private function getImgixSrcSet($asset_url) {
	$srcset = "";
	foreach ($this->sizes as $index=>$size) {
		if ($index !== 0) $srcset .= ', ';
		$srcset .= $asset_url . '?w=' . $size . '&q=80&format=auto' . ' ' . $size . 'w';
	}
	return $srcset;
  }

  private function getGlideSrcSet($asset) {
	  $srcset = "";
	  foreach ($this->sizes as $index=>$size) {
		if ($index !== 0) $srcset .= ', ';
		$srcset .= config('app.url') . Image::manipulate($asset, [ 'w' => $size ]) . ' ' . $size . 'w';
	}
	return $srcset;
  }

  public function index()
  {
      $this->src = $this->params["src"];
	  if (is_string($this->src)) {
		$asset = AssetFacade::findByUrl($this->src);
	  } else {
		$asset = $this->src->value();
	  }
	  $meta_data = $asset->meta()["data"];
	  $srcset = $this->getGlideSrcSet($asset);
      return view('statamic-image-renderer::responsiveImage', [
		  "blurhash" => isset($meta_data["blurhash"]) ? $meta_data["blurhash"] : '',
		  "dominant_color" => isset($meta_data["dominant_color"]) ? $meta_data["dominant_color"] : '#f1f1f1',
		  "alt" => isset($meta_data["alt"]) ? $meta_data["alt"] : '',
		  "srcset" => $srcset,
	  ]);
  }

  public function wildcard($tag)
  {
      $this->params->put('src', $this->context->get($tag));

      return $this->index();
  }
}
