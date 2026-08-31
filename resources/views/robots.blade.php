User-agent: *

{{-- El panel pide sesión, pero declararlo ahorra rastreo inútil. --}}
Disallow: /panel
Disallow: /acceso
Disallow: /mi-cuenta
Disallow: /mi-cotizacion
Disallow: /cotizacion-enviada
Disallow: /registro
Disallow: /clave-olvidada
Disallow: /clave-nueva
Disallow: /baja-newsletter
Disallow: /sugerencias

{{-- El catálogo filtrado o buscado es la misma mercancía ordenada de otra
     forma: no son páginas nuevas que valga la pena indexar. --}}
{{-- `?q=` NO se bloquea: el schema del sitio anuncia un SearchAction que
     apunta justo ahí, y bloquearlo impedía que Google llegara a ver el
     canonical —que ya devuelve `/repuestos` limpio—. Bloquear por robots.txt
     no desindexa: sólo impide leer la etiqueta que sí lo haría. --}}
Disallow: /*?orden=
Disallow: /*&orden=

{{-- G · SEO para IAs. Los rastreadores de modelos generativos —los que
     alimentan ChatGPT, Claude, Perplexity y los AI Overviews de Google—
     leen el catálogo con el mismo respeto que los buscadores normales.
     Que la marca aparezca cuando alguien pregunta «dónde compro
     pastillas de freno para un AVEO en Bogotá» depende de esto. --}}
{{-- Cada uno repite la lista completa a propósito.

     Por el estándar, un robot que encuentra SU grupo ignora por completo el de
     `*`: no hereda nada. Con sólo tres líneas aquí, para estos cuatro quedaban
     abiertos el acceso, el registro y la cotización enviada. --}}
User-agent: GPTBot
Allow: /
Disallow: /panel
Disallow: /acceso
Disallow: /registro
Disallow: /clave-olvidada
Disallow: /clave-nueva
Disallow: /mi-cuenta
Disallow: /mi-cotizacion
Disallow: /cotizacion-enviada
Disallow: /baja-newsletter
Disallow: /sugerencias
Disallow: /*?orden=
Disallow: /*&orden=

User-agent: ClaudeBot
Allow: /
Disallow: /panel
Disallow: /acceso
Disallow: /registro
Disallow: /clave-olvidada
Disallow: /clave-nueva
Disallow: /mi-cuenta
Disallow: /mi-cotizacion
Disallow: /cotizacion-enviada
Disallow: /baja-newsletter
Disallow: /sugerencias
Disallow: /*?orden=
Disallow: /*&orden=

User-agent: PerplexityBot
Allow: /
Disallow: /panel
Disallow: /acceso
Disallow: /registro
Disallow: /clave-olvidada
Disallow: /clave-nueva
Disallow: /mi-cuenta
Disallow: /mi-cotizacion
Disallow: /cotizacion-enviada
Disallow: /baja-newsletter
Disallow: /sugerencias
Disallow: /*?orden=
Disallow: /*&orden=

User-agent: Google-Extended
Allow: /
Disallow: /panel
Disallow: /acceso
Disallow: /registro
Disallow: /clave-olvidada
Disallow: /clave-nueva
Disallow: /mi-cuenta
Disallow: /mi-cotizacion
Disallow: /cotizacion-enviada
Disallow: /baja-newsletter
Disallow: /sugerencias
Disallow: /*?orden=
Disallow: /*&orden=

Sitemap: {{ route('sitemap') }}
