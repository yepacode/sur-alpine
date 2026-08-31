<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
{{-- El pie del correo lleva los datos del negocio: es lo que un
     gestor de correo mira para decidir si esto es legítimo, y de paso le
     da al que lo recibe cómo llamar sin volver a la web. --}}
@php $contacto = app(\App\Services\Contacto::class); @endphp
**{{ config('app.name') }}**
{{ $contacto->direccionCompleta() }}
PBX {{ $contacto->pbx() }}

© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
