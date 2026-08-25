<x-mail::message>
# Solicitud {{ $cotizacion->consecutivo }}

**{{ $cotizacion->nombre_completo }}** pide cotización de
{{ $cotizacion->items->sum('cantidad') }} {{ Str::plural('repuesto', $cotizacion->items->sum('cantidad')) }}
para {{ $porVehiculo->count() }} {{ Str::plural('vehículo', $porVehiculo->count()) }}.

**Teléfono:** {{ $cotizacion->telefono }}
**Correo:** {{ $cotizacion->email }}
**Recibida:** {{ $cotizacion->created_at->format('d/m/Y \a \l\a\s H:i') }}

@foreach ($porVehiculo as $vehiculo => $items)
## {{ $vehiculo }}

<x-mail::table>
| Repuesto | Cant. |
|:---------|:-----:|
@foreach ($items as $item)
| {{ $item->producto_nombre }} | {{ $item->cantidad }} |
@endforeach
</x-mail::table>
@endforeach

@if ($cotizacion->notas)
## Comentarios del cliente

{{ $cotizacion->notas }}
@endif

<x-mail::button :url="'tel:'.$cotizacion->telefono">
Llamar a {{ $cotizacion->nombre }}
</x-mail::button>

Responde este correo para escribirle directamente.

Sur Alpine
</x-mail::message>
