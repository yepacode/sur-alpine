@props(['texto', 'como' => 'h2'])

{{--
    El título que separa las secciones de la portada, calcado del sitio actual:
    centrado, en negrita, azul de marca y con una línea roja de 4 px pegada
    debajo, del ancho exacto del texto.

    El tamaño va en `vw` porque así lo tienen ellos —2,8vw en escritorio, 5,2 en
    tableta y 5,8 en móvil—, y con `nowrap` para que la línea roja nunca quede
    partida en dos renglones.

    Se repite cuatro veces en la portada; por eso vive aquí y no copiado en cada
    sección: cambiar el color o el grosor de la línea es un solo archivo.

    `como` existe porque en la portada estos títulos separan secciones —y ahí
    h2 es lo correcto— pero en «Noticias» este mismo bloque ES el título de la
    página, y esa página se quedaba sin h1. La forma no cambia; el nivel sí.
--}}
<div class="text-center">
    <{{ $como }} {{ $attributes->merge(['class' => 'relative inline-block whitespace-nowrap pb-2 text-[5.8vw] font-bold leading-[1.05] text-marca-600 min-[481px]:text-[5.2vw] md:text-[2.8vw]']) }}>
        {{ $texto }}
        <span class="absolute inset-x-0 bottom-0 h-1 bg-alerta-500"></span>
    </{{ $como }}>
</div>
