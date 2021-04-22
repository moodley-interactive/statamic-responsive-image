<picture>
	@foreach ($srcsets as $srcset)
		<source data-srcset="{{ $srcset["srcset"] }}" type="image/{{ $srcset["type"] }}" @if(!$loop->first) media="(min-width:{{ $srcset["min_width"] }}px)" @endif/>
	@endforeach
	<img
		onload="this.style.backgroundColor = 'transparent'"
		loading="lazy"
		height="{{ $height }}"
		width="{{ $width }}"
        src="{{ $placeholder }}"
        data-src="{{ $placeholder }}"
		class="lazyload {{ $class }}"
		style="background-color: {{ $dominant_color }};"
        alt="Image description"
    />
</picture>
