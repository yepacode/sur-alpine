<x-mail::message>
# Mensaje desde la web

**{{ $mensaje->nombre }}** escribió por el formulario de «Contáctenos».

**Correo:** {{ $mensaje->email }}
**Recibido:** {{ $mensaje->created_at->format('d/m/Y \a \l\a\s H:i') }}

## Lo que dice

{{ $mensaje->mensaje }}

<x-mail::button :url="'mailto:'.$mensaje->email">
Responderle
</x-mail::button>

También puedes responder directamente a este correo: llega a {{ $mensaje->nombre }}.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
