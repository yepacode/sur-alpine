User-agent: *

{{-- El panel pide sesión, pero declararlo ahorra rastreo inútil. --}}
Disallow: /panel
Disallow: /acceso
Disallow: /mi-cotizacion
Disallow: /cotizacion-enviada
Disallow: /sugerencias

{{-- El catálogo filtrado o buscado es la misma mercancía ordenada de otra
     forma: no son páginas nuevas que valga la pena indexar. --}}
Disallow: /*?q=
Disallow: /*?orden=

Sitemap: {{ route('sitemap') }}
