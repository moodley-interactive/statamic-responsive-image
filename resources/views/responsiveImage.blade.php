<picture>
	@foreach ($srcsets as $srcset)
		<source sizes="100vw" data-srcset="{{ $srcset["srcset"] }}" type="image/{{ $srcset["type"] }}" @if(!$loop->last) media="(min-width: {{ $srcset["min_width"] }}px)"@endif/>
	@endforeach
	<img
		onload="
			this.onload=null;
			this.style.backgroundColor = 'transparent';
			var imgWidth = this.getBoundingClientRect().width;
			this.parentNode.querySelectorAll('source')
					.forEach(function (source) {
							source.sizes=Math.ceil(imgWidth/window.innerWidth*100)+'vw';
					});
		"
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
