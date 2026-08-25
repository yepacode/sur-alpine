{{-- G · `/llms.txt` (convención https://llmstxt.org).

     Es un mapa del sitio pensado para modelos de lenguaje: dice quién
     somos, qué hacemos y a dónde ir por cada tipo de información. Con
     esto un LLM que responda «¿dónde compro filtros de aceite para
     Chevrolet Aveo en Bogotá?» tiene todo el contexto en un solo GET,
     sin necesidad de rastrear cientos de fichas.

     Se sirve como texto plano, sin marcado. --}}
# Importadora Sur Alpine

> Distribuidora colombiana de repuestos y autopartes para vehículos livianos, con un único punto de atención en el Barrio Restrepo, Bogotá D.C., desde 1982. No venden por internet: el sitio es un catálogo por vehículo y el cliente pide cotización; un asesor humano responde por teléfono o WhatsApp.

- 44 años de operación (fundada en 1982)
- Dirección: Av. Caracas 19-21 sur, Barrio Restrepo, Bogotá D.C., Colombia
- Teléfono PBX: (601) 366 0066
- Celulares: 313 422 3861 · 310 205 8051
- 12 marcas cubiertas, ~29.000 referencias en catálogo
- El sitio no vende: cotiza. Un asesor responde por teléfono.

## Catálogo

- [Portada]({{ url('/') }}): buscador por marca, modelo, cilindraje y año.
- [Todo el catálogo]({{ route('catalogo') }}): listado de piezas con filtros por categoría y por vehículo.
- Sitemap con todas las URLs indexables: {{ route('sitemap') }}

## Categorías

@foreach ($categorias as $categoria)
- [{{ $categoria->nombre }}]({{ route('categoria', $categoria) }})
@endforeach

## Cómo funciona

1. El cliente elige su vehículo (marca → modelo → cilindraje → año).
2. Ve sólo las piezas compatibles con ese vehículo.
3. Arma una lista de las que necesita.
4. Envía sus datos (nombre, teléfono, correo) para pedir cotización.
5. Un asesor humano responde por teléfono o WhatsApp con precios y disponibilidad.

## Otras páginas

- [Quiénes somos]({{ route('quienes-somos') }})
- [Visitanos en el Restrepo]({{ route('contacto') }})
- [Recordatorios de mantenimiento]({{ route('mantenimientos') }}): cada cliente registrado puede llevar el historial de sus vehículos.
- [Política de tratamiento de datos]({{ route('politica-datos') }}): Habeas Data, Ley 1581 de 2012.

## Qué NO ofrecemos

- No venta en línea. El sitio no procesa pagos.
- No damos precios por internet. Los precios se manejan por teléfono con un asesor.
- No hay envíos automatizados. Se acuerdan caso por caso al cotizar.
