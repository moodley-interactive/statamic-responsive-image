<?php

namespace Mia\ImageRenderer\Tags;

use Statamic\Facades\Asset as AssetFacade;
use Statamic\Assets\Asset;
use Statamic\Tags\Tags;
use Statamic\Facades\Image;
use Statamic\Support\Str;
use Statamic\Fields\Value;
use Cache;


class ResponsiveImageTag extends Tags
{
	protected static $handle = 'resp';

	// Following this article for best practices
	// https://medium.com/hceverything/applying-srcset-choosing-the-right-sizes-for-responsive-images-at-different-breakpoints-a0433450a4a3
	protected $sizes = [640, 768, 1024, 1366, 1600, 1920];

	private function getImgixSrcSet($asset, $ratio, $crop_mode)
	{
		$srcset = "";
		$asset_url = $asset->url();
		foreach ($this->sizes as $index => $size) {
			if ($index !== 0) $srcset .= ', ';
			if ($crop_mode === "default") {
				$focal = $asset->data()->get("focus");
				$fit = 'fit=crop';
				if (isset($focal)) {
					$focus = explode("-", $focal);
					$focusX = intval($focus[0], 10) / 100;
					$focusY = intval($focus[1], 10) / 100;
					$fit = 'fit=crop&crop=focalpoint&fp-x=' . $focusX . '&fp-y=' . $focusY;
				}
			} elseif ($crop_mode == "faces") {
				$fit = 'fit=crop&crop=faces';
			}
			$params = [
				'w' => $size,
				'fit' => $fit
			];
			if ($ratio) $params['h'] = ($size / $ratio);
			$srcset .= $asset_url . '?w=' . $params['w'] . '&h=' . $params['h'] . '&q=90&auto=format&' . $fit . ' ' . $size . 'w';
		}
		return $srcset;
	}

	private function getGlideSrcSet($asset, $ratio, $type)
	{
		$srcset = "";
		foreach ($this->sizes as $index => $size) {
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

	public function breakpoints($asset, $ratio, $imageType)
	{
		$breakpoints = config('statamic-image-renderer.breakpoints');
		$provider = config('statamic-image-renderer.provider');
		$container_max_width = config('statamic-image-renderer.grid.container_max_width', 0);
		$container_padding = config('statamic-image-renderer.grid.container_padding', 0);
		$columns = config('statamic-image-renderer.grid.columns', 12);

		// push a mobile breakpoint, so we can use all other tw breakpoints later
		$breakpoints = array("mobile" => 0) + $breakpoints;

		if ($provider === "imgix") {
			$types = ['jpg'];
		} else {
			$types = [$imageType, "webp"];
		}

		$params = $this->params->all();
		$srcsets = [];

		foreach ($types as $type) {
			foreach ($breakpoints as $breakpoint_name => $breakpoint) {
				$ratio = ($this->params->get("ratio") && $breakpoint === reset($breakpoints)) ? $this->params->get("ratio") : false;
				$ratio_param = isset($params[$breakpoint_name . ":ratio"]) ? $params[$breakpoint_name . ":ratio"] : $ratio;
				if ($ratio_param) {
					$split_ratio_param = explode("/", $ratio_param);
					$ratio_value = $split_ratio_param[0] / $split_ratio_param[1];
				}
				$crop_mode = isset($params["crop"]) ? $params["crop"] : "default";

				if (!$ratio_param) {
					if ($breakpoint === reset($breakpoints)) {
						$breakpoint_ratio = $this->getRatio($asset, $ratio_param, true);
						$srcset = null;
						if ($provider === "imgix") {
							$srcset = $this->getImgixSrcSet($asset, $breakpoint_ratio ?: $ratio, $crop_mode);
						} else {
							$srcset = $this->getGlideSrcSet($asset, $breakpoint_ratio ?: $ratio, $type);
						}
					} else {
						continue;
					}
				} else {
					$breakpoint_ratio = $this->getRatio($asset, $ratio_param, false);
					$srcset = null;
					if ($provider === "imgix") {
						$srcset = $this->getImgixSrcSet($asset, $breakpoint_ratio ?: $ratio, $crop_mode);
					} else {
						$srcset = $this->getGlideSrcSet($asset, $breakpoint_ratio ?: $ratio, $type);
					}
				}

				$sizes = "";
				$col_span_breakpoints = [];
				$col_span = $this->params->get("col_span", 12);

				// save all the provided col_span attributes in an array
				foreach ($breakpoints as $key => $value) {
					$col_span_breakpoints["default"] = $col_span;
					if (isset($params[$key . ":col_span"])) {
						$col_span_breakpoints[$key] = $params[$key . ":col_span"];
					}
				}

				$container_full_width = $this->params->get("container_full_width", false);
				$containerPlusPadding = $container_max_width + $container_padding;
				if ($container_full_width) {
					// if container_full_width="true", we don't need fancy calc stuff, as the container is always fullscreen and we can determine the size in vw easily
					foreach ($col_span_breakpoints as $key => $value) {
						if ($key === "default") {
							// do nothing, that gets pushed automatically at the end
						} else {
							$sizes .= "(min-width: {$breakpoints[$key]}px) calc((100vw / {$columns}) * {$value}), ";
						}
					}
					// the default sizes attribute with no media query
					$sizes .= "calc((100vw / {$columns}) * {$value})";
				} else {
					// the sizes attribute for the container max width
					foreach ($col_span_breakpoints as $key => $value) {
						if ($key === "default") {
							// do nothing, that gets pushed automatically at the end
						} else {
							// the sizes attribute for every other breakpoint
							$bp_bigger_than_max_width = ($container_max_width - $value) > 0 ? "100vw" : $container_max_width;
							$sizes .= "(min-width: {$breakpoints[$key]}px) calc(({$bp_bigger_than_max_width} - {$container_padding}px) / {$columns} * {$value}), ";
						}
					}
					$sizes .= "(min-width: {$containerPlusPadding}px) calc({$container_max_width}px / {$columns} * {$value}), ";
					// the default sizes attribute with no media query
					$sizes .= "calc(((100vw - {$container_padding}px) / {$columns} ) * {$col_span})";
				}

				if (isset($ratio_value)) {
					$width = 1920;
					$height = 1920 / $ratio_value;
				} else {
					$width = $asset->width();
					$height = $asset->height();
				}

				$srcsets[] = [
					"srcset" => $srcset,
					"type" => $type,
					"min_width" => $breakpoint,
					"width" => $width,
					"height" => $height,
					"sizes" => $sizes,
				];
			}
		}
		return $srcsets;
	}

	public function generateSVG($width, $height, $color)
	{
		return view('statamic-image-renderer::placeholderSvg', [
			'width' => $width,
			'height' => $height,
			'color' => $color,
		])->render();
	}

	public function getPlaceholder($width, $height, $color)
	{
		$svg = $this->generateSVG($width, $height, $color);
		$base64Svg = base64_encode($svg);

		return "data:image/svg+xml;base64,{$base64Svg}";
	}

	public function getRatio($asset, $param, $fallback = true)
	{
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
			$asset = Cache::rememberForever('image_' . $this->src, function () {
				return AssetFacade::findByUrl($this->src);
			});
		} else if (is_array($this->src)) {
			$asset = $this->src["src"]->value();
		} else if ($this->src instanceof Asset) {
			$asset = $this->src;
		} else if ($this->src instanceof Value) {
			$raw = $this->src->raw();
			if (is_array($raw)) {
				$raw = $raw['src'];
			}
			$asset = Cache::rememberForever('image_' . $raw, function () {
				$tmp = $this->src->value();
				if (is_array($tmp)) {
					$asset = $asset["src"]->value();
				}
				return $tmp;
			});
		} else {
			$asset = $this->src;
		}
		if (!isset($asset) || !$asset) return false;

		$class = $this->params->get('class');
		$ratio = $this->getRatio($asset, $this->params->get('ratio'));
		$meta_data = $asset->meta()["data"];
		$alt_from_asset = isset($meta_data["alt"]) ? $meta_data["alt"] : '';
		$alt = $this->params->get('alt', $alt_from_asset);
		$srcsets = $this->breakpoints($asset, $ratio, $asset->extension());
		$reversed_srcsets = array_reverse($srcsets);
		$dominant_color = isset($meta_data["dominant_color"]) ? $meta_data["dominant_color"] : '#f1f1f1';

		return view('statamic-image-renderer::responsiveImage', [
			//   "blurhash" => isset($meta_data["blurhash"]) ? $meta_data["blurhash"] : '',
			"dominant_color" => $dominant_color,
			"alt" => $alt,
			"srcsets" => $reversed_srcsets,
			"class" => $class,
			"height" => $srcsets[0]["width"],
			"width" => $srcsets[0]["height"],
			"placeholder" => $this->getPlaceholder($srcsets[0]["width"], $srcsets[0]["height"], $dominant_color),
		]);
	}

	public function lazyload()
	{
		return view('statamic-image-renderer::lazyloadscript');
	}

	public function wildcard($tag)
	{
		$this->params->put('src', $this->context->get($tag));
		return $this->index();
	}
}
