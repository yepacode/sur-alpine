@props([
    // La clase de botón la manda quien lo usa, para que todos los botones de
    // la página de acceso sean exactamente el mismo botón.
    'clase' => 'flex h-[52px] w-full items-center justify-center gap-3 rounded-lg text-base font-bold transition',
])

@php
    /*
     * «Ingresar con Facebook» / «Ingresar con Google».
     *
     * Se pintan siempre, como en su página. Si al proveedor todavía le faltan
     * las llaves, el enlace devuelve al formulario con un aviso —ver
     * `AccesoSocialController::sinConfigurar()`—: la pantalla se ve completa y
     * nadie se queda sin saber qué pasó.
     *
     * Sobre el color: van en azul de marca, no en el azul de Facebook ni en el
     * blanco de Google —igual que en su página, donde los botones son todos
     * azules—. El logo sí es el de verdad, dibujado tal cual, porque es lo que
     * se reconoce de un vistazo; la G va sobre un chip blanco porque en azul
     * sus cuatro colores no se leen.
     */
    $listos = \App\Http\Controllers\AccesoSocialController::disponibles();
@endphp

<div class="mt-3 space-y-3">
    @foreach (\App\Http\Controllers\AccesoSocialController::PROVEEDORES as $proveedor)
        @php $activo = in_array($proveedor, $listos, true); @endphp

        <a href="{{ route('acceso.social', $proveedor) }}"
           class="{{ $clase }} bg-marca-700 text-white hover:bg-marca-800"
           @unless ($activo) title="Lo estamos activando; por ahora entra con tu correo." @endunless>
            @if ($proveedor === 'facebook')
                <svg viewBox="0 0 24 24" fill="currentColor" class="size-[18px] shrink-0" aria-hidden="true">
                    <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07Z"/>
                </svg>
            @else
                <span class="grid size-[22px] shrink-0 place-items-center rounded-full bg-white">
                    <svg viewBox="0 0 24 24" class="size-3.5" aria-hidden="true">
                        <path fill="#4285F4" d="M23.5 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.87c2.27-2.09 3.56-5.17 3.56-8.87Z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.94-2.91l-3.87-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.28v3.09A12 12 0 0 0 12 24Z"/>
                        <path fill="#FBBC05" d="M5.27 14.29a7.2 7.2 0 0 1 0-4.58V6.62H1.28a12 12 0 0 0 0 10.76l3.99-3.09Z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.28 6.62l3.99 3.09C6.22 6.86 8.87 4.75 12 4.75Z"/>
                    </svg>
                </span>
            @endif
            Ingresar con {{ \App\Http\Controllers\AccesoSocialController::NOMBRES[$proveedor] }}
        </a>
    @endforeach
</div>
