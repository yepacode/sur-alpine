@props([
    'titulo',
    'imagen',
    'ancho' => 1440,
    'alto' => 307,
    // Cuando el arte ya trae el título escrito —el de «Contáctenos», por
    // ejemplo— el `h1` no se pinta encima: se vería el mismo texto dos veces,
    // uno encima del otro y desalineados.
    'tituloEnLaImagen' => false,
])

{{--
    La franja de cabecera de las páginas internas, medida sobre las suyas:
    280 px de alto, a todo el ancho, la foto en `cover` y centrada.

    Sobre el título hay dos casos, y son los dos que tienen ellos:

      · si el arte NO lo trae (Quiénes somos), va encima en blanco a 68,8 px,
        con un degradado de negro a transparente que lo hace legible sobre
        cualquier foto;
      · si el arte SÍ lo trae (Contáctenos), el `h1` se esconde a la vista pero
        se queda en el HTML. Borrarlo del todo dejaría la página sin titular
        para Google y sin nada que anunciar a un lector de pantalla —el texto
        de una imagen no lo lee nadie—, y eso es peor que el problema que
        resuelve. En ese caso tampoco va el degradado: sin título que proteger,
        oscurecer media foto no aporta nada.

    En móvil la franja baja a 190 px y el título a 2,25rem: 68 px en la
    pantalla de un teléfono se comen la franja entera.
--}}
<section class="relative isolate h-[190px] overflow-hidden sm:h-[240px] md:h-[280px]">
    <img src="{{ $imagen }}-1600.webp"
         srcset="{{ $imagen }}-1024.webp 1024w, {{ $imagen }}-1600.webp 1600w"
         sizes="100vw" width="{{ $ancho }}" height="{{ $alto }}" alt=""
         fetchpriority="high" decoding="async"
         @class([
             'absolute inset-0 -z-10 size-full object-cover',
             // El arte lleva el título a la izquierda: anclando ahí, lo que se
             // recorta en pantallas angostas es la foto de la derecha y no la
             // primera letra del título. En su sitio se corta la «C».
             'object-left' => $tituloEnLaImagen,
             'object-center' => ! $tituloEnLaImagen,
         ])>

    @unless ($tituloEnLaImagen)
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-black to-transparent" aria-hidden="true"></div>
    @endunless

    <div class="contenedor flex h-full items-center px-[3vw]">
        <h1 @class([
            'sr-only' => $tituloEnLaImagen,
            'text-[2.25rem] font-bold leading-none text-white sm:text-[3rem] md:text-[68.8px]' => ! $tituloEnLaImagen,
        ])>{{ $titulo }}</h1>
    </div>
</section>
