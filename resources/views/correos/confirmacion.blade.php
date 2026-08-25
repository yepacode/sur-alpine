<x-mail::message>
# Recibimos tu solicitud

Hola {{ $cotizacion->nombre }}, ya tenemos tu lista de repuestos.
Un asesor te llama al **{{ $cotizacion->telefono }}** para atender tu solicitud.

Tu número de solicitud es **{{ $cotizacion->consecutivo }}**. Tenlo a mano cuando te llamemos.

@foreach ($porVehiculo as $vehiculo => $items)
## {{ $vehiculo }}

@foreach ($items as $item)
- {{ $item->producto_nombre }} ({{ $item->cantidad }})
@endforeach
@endforeach

Si necesitas agregar algo o corregir un dato, respóndenos este correo o llámanos
al **{{ $contacto->pbx() }}**.

Gracias por escribirnos,
**Importadora Sur Alpine**
</x-mail::message>
