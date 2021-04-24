# moodley interactive responsive image statamic addon

## Installation

Install via composer:
`composer require mia/statamic-image-renderer`

## Config

Run and select "XXX"
```
php artisan vendor:publish
```
## Tag Usage

In all the examples the assets field is called `image` in the blueprint. Can be called anything, thats up to you.

All parameters can be mixed, however you have to make sure, that the ratio and col_span attributes have a corresponding breakpoint set up. You can't have `md:ratio="4/3"` and `lg:col_span="4"`. The parameter `col_span` expects a ratio to be set up.

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
{{ resp:image container_full_width="true }}
```

