<x-mail::message>
# Hola, {{ $usuario->primer_nombre }}

@if ($vencidos->isNotEmpty())
Estos mantenimientos que anotaste ya se pasaron de fecha:

@foreach ($vencidos as $m)
- **{{ $m->tipo }}** — {{ $m->placa }} · {{ $m->aviso }}
@endforeach
@endif

@if ($porVencer->isNotEmpty())
@if ($vencidos->isNotEmpty())
Y estos están por tocar:
@else
Estos mantenimientos que anotaste están por tocar:
@endif

@foreach ($porVencer as $m)
- **{{ $m->tipo }}** — {{ $m->placa }} · {{ $m->aviso }}
@endforeach
@endif

<x-mail::button :url="route('cuenta.mantenimientos')">
Ver mi historial
</x-mail::button>

@if ($porKilometraje->isNotEmpty())
## Estos van por kilometraje

No sabemos cómo va tu odómetro, así que estos no te los podemos avisar por
fecha. Échales un ojo cuando revises el tablero:

@foreach ($porKilometraje as $m)
- **{{ $m->tipo }}** — {{ $m->placa }} · {{ $m->aviso }}
@endforeach
@endif

¿Necesitas el repuesto? Búscalo por tu carro y arma tu solicitud; un asesor te
contacta para confirmarte disponibilidad. También puedes llamarnos al PBX
{{ $contacto->pbx() }}.

Gracias,<br>
Importadora Sur Alpine

<x-slot:subcopy>
Recibes este correo porque anotaste estos mantenimientos en tu cuenta. Puedes
borrarlos desde [tu historial]({{ route('cuenta.mantenimientos') }}) y dejamos
de avisarte.
</x-slot:subcopy>
</x-mail::message>
