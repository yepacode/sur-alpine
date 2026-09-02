{{--
    El molde de las páginas de error.

    Antes no existía ninguna: un 404 salía como la página blanca de Symfony con
    «Not Found» en inglés, sin cabecera, sin pie y sin una sola salida. Eso pega
    en las 29.272 URLs que el sitio publica en su sitemap y en cada enlace que
    un asesor pasa por WhatsApp —justo donde más falta hace que se vea la marca
    de verdad, porque el problema del cliente es que lo suplantan—.

    Va con el layout completo a propósito: la cabecera, el teléfono y el pie son
    lo que distingue el sitio real del que lo copia.
--}}
{{-- Dentro del panel, con la cara del panel.
     Un 404 en `/panel/categorias/5` le enseñaba al dueño la página pública de
     su propia tienda: «Buscar mi repuesto» y «¿Necesitas una pieza? Llámanos
     al (601) 366 0066». Leyendo «llámanos» en su propio panel de
     administración. --}}
@extends(request()->is('panel*') ? 'errors.layout-panel' : 'layouts.app')

@section('robots', 'noindex, nofollow')

@section('contenido')
    <section class="contenedor flex flex-1 flex-col items-center justify-center py-16 text-center sm:py-24">
        <p class="text-sm font-bold uppercase tracking-[0.2em] text-alerta-500">Error @yield('codigo')</p>

        <h1 class="mt-3 max-w-2xl text-3xl font-black leading-tight tracking-tight text-tinta-900 sm:text-4xl">
            @yield('encabezado')
        </h1>

        <div class="mt-4 max-w-xl text-base leading-relaxed text-tinta-600">
            @yield('explicacion')
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            @yield('salidas')
        </div>

        {{-- El teléfono sale del mismo sitio que la barra azul y el pie: si el
             cliente lo cambia en el panel, cambia también aquí. --}}
        @inject('contacto', 'App\Services\Contacto')
        <p class="mt-10 text-sm text-tinta-500">
            ¿Necesitas una pieza y no la encuentras? Llámanos al
            <a href="tel:{{ $contacto->pbxTel() }}"
               class="font-semibold text-marca-700 underline-offset-2 hover:underline">{{ $contacto->pbx() }}</a>
            y te la conseguimos.
        </p>
    </section>
@endsection
