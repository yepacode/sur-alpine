<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
{{-- `lastmod` es la única de las tres que Google mira de verdad: `priority`
     y `changefreq` los ignora desde hace años. Con 29.272 URLs, es lo que le
     dice qué merece volver a rastrear. --}}
@isset($url['modificado'])
        <lastmod>{{ $url['modificado'] }}</lastmod>
@endisset
        <changefreq>{{ $url['frecuencia'] }}</changefreq>
        <priority>{{ $url['prioridad'] }}</priority>
    </url>
@endforeach
</urlset>
