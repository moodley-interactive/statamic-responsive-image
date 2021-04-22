<?php

namespace Valschr\ImageRenderer\Tags;

use Statamic\Facades\Asset as AssetFacade;
use Statamic\Tags\Tags;
use Statamic\Facades\Image;
use Statamic\Support\Str;

class ResponsiveImageTag extends Tags
{
  protected static $handle = 'resp';

  // Following this article for best practices
  // https://medium.com/hceverything/applying-srcset-choosing-the-right-sizes-for-responsive-images-at-different-breakpoints-a0433450a4a3
  protected $sizes = [640, 768, 1024, 1366, 1600, 1920];

  private function getImgixSrcSet($asset, $ratio) {
	$srcset = "";
	foreach ($this->sizes as $index=>$size) {
		if ($index !== 0) $srcset .= ', ';
		$srcset .= $asset_url . '?w=' . $size . '&q=90&format=auto' . ' ' . $size . 'w';
	}
	return $srcset;
  }

  private function getGlideSrcSet($asset, $ratio, $type) {
	  $srcset = "";
	  foreach ($this->sizes as $index=>$size) {
		if ($index !== 0) $srcset .= ', ';
		$focal = $asset->data()->get("focus");
		$params = [
			'w' => $size,
			'fit' => 'crop' . ($focal ? '-' . $focal : '')
		];
		// TODO: focal point
		if ($ratio) $params['h'] = ($size / $ratio);
		if ($ratio) $params['fm'] = $type;
		$srcset .= config('app.url') . Image::manipulate($asset, $params) . ' ' . $size . 'w';
	}
	return $srcset;
  }

  public function breakpoints($asset, $ratio, $imageType) {
	$bp = config('statamic.statamic-image-renderer.breakpoints');
	$types = [$imageType, "webp"];
	$srcsets = [];
	foreach ($types as $type) {
		foreach ($bp as $index=>$b) {
			$param = $this->params->get($b . ':ratio');
			$breakpoint_ratio = $this->getRatio($asset, $param, false);
			$srcsets[] = [
				"srcset" => $this->getGlideSrcSet($asset, $breakpoint_ratio ?: $ratio, $type),
				"type" => $type,
				"min_width" => $b,
			];
		}
	}
	return $srcsets;
  }

  public function getPlaceholder() {
	  return 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
  }

  public function getRatio($asset, $param, $fallback = true) {
	$ratio = $fallback ? $asset->width() / $asset->height() : null;
	if (isset($param) && Str::contains($param, '/')) {
		[$width, $height] = explode('/', $param);
		$ratio = $width / $height;
	}
	return $ratio;
  }

  public function index()
  {
      $this->src = $this->params["src"];
	  if (is_string($this->src)) {
		$asset = AssetFacade::findByUrl($this->src);
	  } else {
		$asset = $this->src->value();
	  }
	  if (!$asset) return false;

	  $class = $this->params->get('class');
	  $ratio = $this->getRatio($asset, $this->params->get('ratio'));
	  $meta_data = $asset->meta()["data"];
	  $srcsets = $this->breakpoints($asset, $ratio, $asset->extension());
      return view('statamic-image-renderer::responsiveImage', [
		//   "blurhash" => isset($meta_data["blurhash"]) ? $meta_data["blurhash"] : '',
		  "dominant_color" => isset($meta_data["dominant_color"]) ? $meta_data["dominant_color"] : '#f1f1f1',
		  "alt" => isset($meta_data["alt"]) ? $meta_data["alt"] : '',
		  "srcsets" => $srcsets,
		  "class" => $class,
		  "height" => $asset->height(),
		  "width" => $asset->width(),
		  "placeholder" => $this->getPlaceholder(),
	  ]);
  }

  public function lazyload() {
	  return view('statamic-image-renderer::lazyloadscript');
  }

  public function wildcard($tag)
  {
      $this->params->put('src', $this->context->get($tag));
      return $this->index();
  }
}
