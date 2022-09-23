@php
$use_lazysizes = config("statamic-image-renderer.lazy_loading", "lazysizes") === "lazysizes";
@endphp

<picture>
	@foreach ($srcsets as $srcset)
		<source
			sizes="{{ $srcset["sizes"] }}"
			@if (!$loop->last) media="(min-width: {{ $srcset["min_width"] }}px)"@endif
			@if ($use_lazysizes)
			data-srcset="{{ $srcset["srcset"] }}"
			@else
			srcset="{{ $srcset["srcset"] }}"
			@endif
		/>
	@endforeach
	<img
		onload="this.style.backgroundColor='transparent';@if(!$use_lazysizes)this.classList.remove('lazyload');this.classList.add('lazyloaded')@endif"
		loading="lazy"
		height="{{ $height }}"
		width="{{ $width }}"
    	src="{{ $placeholder }}"
		@if ($use_lazysizes)
    	data-src="{{ $placeholder }}"
		@endif
		class="lazyload {{ $class }}"
		style="background-color: {{ $dominant_color }};"
    	alt="{{ $alt }}"
    />
</picture>
