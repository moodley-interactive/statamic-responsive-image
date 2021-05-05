<picture>
	@foreach ($srcsets as $srcset)
		<source 
			sizes="{{ $srcset["sizes"] }}"
			width="{{ $srcset["width"] }}"
			height="{{ $srcset["height"] }}"
			@if(!$loop->last) media="(min-width: {{ $srcset["min_width"] }}px)"@endif 
			data-srcset="{{ $srcset["srcset"] }}"
		/>
	@endforeach
	<img
		onload="this.style.backgroundColor = 'transparent';"
		loading="lazy"
		height="{{ $height }}"
		width="{{ $width }}"
    src="{{ $placeholder }}"
    data-src="{{ $placeholder }}"
		class="lazyload {{ $class }}"
		style="background-color: {{ $dominant_color }};"
    alt="{{ $alt }}"
    />
</picture>
