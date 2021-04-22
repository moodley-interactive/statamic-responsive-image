<script>
	if ("loading" in HTMLImageElement.prototype) {
        var images = document.querySelectorAll('img[loading="lazy"]');
        var sources = document.querySelectorAll("source[data-srcset]");
        sources.forEach(function (source) {
            source.srcset = source.dataset.srcset;
        });
        images.forEach(function (img) {
            img.src = img.dataset.src;
        });
    } else {
        var script = document.createElement("script");
        script.src = "https://cdnjs.cloudflare.com/ajax/libs/jquery.lazyload/1.9.1/jquery.lazyload.min.js";
        document.body.appendChild(script);
    }
</script>
