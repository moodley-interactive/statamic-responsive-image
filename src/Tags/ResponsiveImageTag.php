<?php

namespace Mia\ImageRenderer\Tags;

use Statamic\Facades\Asset as AssetFacade;
use Statamic\Assets\Asset;
use Statamic\Tags\Tags;
use Statamic\Facades\Image;
use Statamic\Support\Str;
use Statamic\Fields\Value;

class ResponsiveImageTag extends Tags
{
  protected static $handle = 'resp';

  // Following this article for best practices
  // https://medium.com/hceverything/applying-srcset-choosing-the-right-sizes-for-responsive-images-at-different-breakpoints-a0433450a4a3
  protected $sizes = [640, 768, 1024, 1366, 1600, 1920];

  private function getImgixSrcSet($asset, $ratio) {
	$srcset = "";
	$asset_url = $asset->url();
	foreach ($this->sizes as $index=>$size) {
		if ($index !== 0) $srcset .= ', ';
		$focal = $asset->data()->get("focus");
		$fit = 'fit=crop';
		if (isset($focal)) {
			$focus = explode("-", $focal);
			$focusX = intval($focus[0], 10) / 100;
			$focusY = intval($focus[1], 10) / 100;
			$fit = 'fit=crop&crop=focalpoint&fp-x=' . $focusX . '&fp-y=' . $focusY;
		}
		$params = [
			'w' => $size,
			'fit' => $fit
		];
		if ($ratio) $params['h'] = ($size / $ratio);
		$srcset .= $asset_url . '?w=' . $params['w'] . '&h=' . $params['h'] . '&q=90&format=auto&'. $fit . ' ' . $size . 'w';
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
		if ($ratio) $params['h'] = ($size / $ratio);
		if ($type) $params['fm'] = $type;
		$srcset .= config('app.url') . Image::manipulate($asset, $params) . ' ' . $size . 'w';
	}
	return $srcset;
  }

  public function breakpoints($asset, $ratio, $imageType) {
	$bp = config('statamic.statamic-image-renderer.breakpoints');

	$provider = config('statamic.statamic-image-renderer.provider');
	if ($provider === "imgix") {
		$types = ['jpg'];
	} else {
		$types = [$imageType, "webp"];
	}

	$srcsets = [];
	foreach ($types as $type) {
		foreach ($bp as $key => $b) {
			$params = $this->params->all();
			$ratio = ($this->params->get("ratio") && $b === reset($bp)) ? $this->params->get("ratio") : false;
			$param = isset($params[$key . ":ratio"]) ? $params[$key . ":ratio"] : $ratio;
			if (!$param) {
				if ($b === reset($bp)) {
					$breakpoint_ratio = $this->getRatio($asset, $param, true);
					$srcset = null;
					if ($provider === "imgix") {
						$srcset = $this->getImgixSrcSet($asset, $breakpoint_ratio ?: $ratio, $type);
					} else {
						$srcset = $this->getGlideSrcSet($asset, $breakpoint_ratio ?: $ratio, $type);
					}
				}
			} else {
				$breakpoint_ratio = $this->getRatio($asset, $param, false);
				$srcset = null;
				if ($provider === "imgix") {
					$srcset = $this->getImgixSrcSet($asset, $breakpoint_ratio ?: $ratio, $type);
				} else {
					$srcset = $this->getGlideSrcSet($asset, $breakpoint_ratio ?: $ratio, $type);
				}
			}

			$srcsets[] = [
				"srcset" => $srcset,
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
		} else if (is_array($this->src)) {
			// ray($this->src);
			$asset = $this->src["src"]->value();
			ray($asset);
		} else if ($this->src instanceof Asset) {
			$asset = $this->src;
		} else if ($this->src instanceof Value) {
			$asset = $this->src->value();
			if (is_array($asset)) {
				$asset = $asset["src"]->value();
			}
		} else {
			$asset = $this->src;
		}
		if (!isset($asset) || !$asset) return false;

		$class = $this->params->get('class');
		$ratio = $this->getRatio($asset, $this->params->get('ratio'));
		$meta_data = $asset->meta()["data"];
		$srcsets = $this->breakpoints($asset, $ratio, $asset->extension());
		return view('statamic-image-renderer::responsiveImage', [
			//   "blurhash" => isset($meta_data["blurhash"]) ? $meta_data["blurhash"] : '',
			"dominant_color" => isset($meta_data["dominant_color"]) ? $meta_data["dominant_color"] : '#f1f1f1',
			"alt" => isset($meta_data["alt"]) ? $meta_data["alt"] : '',
			"srcsets" => array_reverse($srcsets),
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
