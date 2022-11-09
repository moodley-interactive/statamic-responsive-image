@php
$use_lazysizes = config("statamic-image-renderer.lazy_loading", "lazysizes") === "lazysizes";
@endphp

<picture>
	@foreach ($srcsets as $srcset)
		<source
			sizes="{{ $srcset["sizes"] }}"
			@if (!$loop->last) media="(min-width: {{ $srcset["min_width"] }}px)"@endif
			@if ($use_lazysizes && $lazyload == 'lazy')
			data-srcset="{{ $srcset["srcset"] }}"
			@else
			srcset="{{ $srcset["srcset"] }}"
			@endif
		/>
	@endforeach
	<img
		onload="this.style.backgroundColor='transparent';@if(!$use_lazysizes && $lazyload == 'lazy')this.classList.remove('lazyload');this.classList.add('lazyloaded')@endif"
		loading="{{$lazyload}}"
		height="{{ $height }}"
		width="{{ $width }}"
    	src="{{ $placeholder }}"
		@if ($use_lazysizes && $lazyload == 'lazy')
    	data-src="{{ $placeholder }}"
		@endif
		class="{{$lazyload == 'lazy' ? 'lazyload' : ''}} {{ $class }}"
		style="background-color: {{ $dominant_color }};"
    	alt="{{ $alt }}"
    />
</picture>
