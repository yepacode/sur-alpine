<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($mapas as $mapa)
    <sitemap><loc>{{ $mapa }}</loc></sitemap>
@endforeach
</sitemapindex>
