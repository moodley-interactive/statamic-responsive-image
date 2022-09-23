![Responsive Image Addon Banner](https://raw.githubusercontent.com/moodley-interactive/statamic-responsive-image/dev/responsive.gif)

# moodley interactive - Responsive image Statamic addon

[Demo](https://responsive-image-demo.moodley.dev/): https://responsive-image-demo.moodley.dev/

## Features

- Works with [Glide](https://glide.thephpleague.com/) and [Imgix](https://www.imgix.com/)
- Generates `picture` elements with the correct `sizes` attribute, based on your pages grid system
- Supports art directed images
- Supports aspect ratios
- Works with your existing assets, no custom fieldtype needed
- Displays a single color placeholder (muted dominant color) of the image
- Supports [lazySizes](https://github.com/aFarkas/lazysizes)

## Motivation

The motivation was, that we needed a solution that works with Glide and Imgix in the first place. And all CPU heavy calculations, like fallback images (e.g.: dominant color placeholder), should not be done in runtime. Further we needed the `sizes` attribute to be calculated based on our grid system, to make this super easy, we used `col_span` as a parameter, just as you are used to from Tailwind.

## Installation

Install via composer:
`composer require mia/statamic-image-renderer`

Publish the config:
`php artisan vendor:publish --tag="statamic-image-renderer-config"`

If you want to use the native browser-level lazy-loading, set `lazy_loading` to `browser` inside the config.

When using [lazysizes](https://github.com/aFarkas/lazysizes) add it to your site like this:
```
npm install lazysizes
```

Then add it to your js file:

```
import lazySizes from 'lazysizes'
```

## Config

The published config can be found in `config/statamic-image-renderer.php` and you can set up the breakpoints, the image provider and the container size for your main content.

## Placeholder

Placeholders are getting generated on upload. If you add the addon to an existing site, you can generate the placeholders for all assets already uploaded with a command. To to so run `php please resp:generate`.

## Tag Usage

In all the examples the assets field is called `image` in the blueprint. Can be called anything, thats up to your fieldsets/blueprint.

All parameters can be mixed, however you have to make sure, that the ratio and col_span attributes have a corresponding breakpoint set up. You can't have `md:ratio="4/3"` and `lg:col_span="4"`. The parameter `col_span` expects a ratio to be set up.

The `col_span` attribute is the most important one, as it tells the browser which size of the image it should load. So the `{{ resp:image }}` tag should never be used without it, or the loaded image size is based on the viewport width.

### Basic Tag

```
{{ resp:image }}
```

### Provided Ratio

Outputs a 16/9 image on mobile and a 4/3 image on breakpoint lg and up.
```
{{ resp:image ratio="16/9" lg:ratio="4/3" }}
```

### Provided Col Span

Sets the correct size on mobile and desktop and takes the page grid in account. Make sure to set up the grid in your config file correctly.

**12 Cols on mobile and 4 cols on desktop:**
```
{{ resp:image col_span="12" lg:col_span="4" }}
```

### Set container to full width

This tells the tag, that this image is not in the page grid and is rather a fullscreen image.

```
{{ resp:image container_full_width="true" }}
```
### Crop to faces (only supported with imgix)

Crop to faces in the picture instead of the focalpoint or center.

```
{{ resp:image crop="faces" }}
```

