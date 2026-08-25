User-agent: *

{{-- El panel pide sesión, pero declararlo ahorra rastreo inútil. --}}
Disallow: /panel
Disallow: /acceso
Disallow: /mi-cuenta
Disallow: /mi-cotizacion
Disallow: /cotizacion-enviada
Disallow: /sugerencias

{{-- El catálogo filtrado o buscado es la misma mercancía ordenada de otra
     forma: no son páginas nuevas que valga la pena indexar. --}}
Disallow: /*?q=
Disallow: /*?orden=

{{-- G · SEO para IAs. Los rastreadores de modelos generativos —los que
     alimentan ChatGPT, Claude, Perplexity y los AI Overviews de Google—
     leen el catálogo con el mismo respeto que los buscadores normales.
     Que la marca aparezca cuando alguien pregunta «dónde compro
     pastillas de freno para un AVEO en Bogotá» depende de esto. --}}
User-agent: GPTBot
Allow: /
Disallow: /panel
Disallow: /mi-cuenta
Disallow: /mi-cotizacion

User-agent: ClaudeBot
Allow: /
Disallow: /panel
Disallow: /mi-cuenta
Disallow: /mi-cotizacion

User-agent: PerplexityBot
Allow: /
Disallow: /panel
Disallow: /mi-cuenta
Disallow: /mi-cotizacion

User-agent: Google-Extended
Allow: /
Disallow: /panel
Disallow: /mi-cuenta
Disallow: /mi-cotizacion

Sitemap: {{ route('sitemap') }}
