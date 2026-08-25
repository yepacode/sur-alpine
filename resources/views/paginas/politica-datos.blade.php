@extends('layouts.app')

@section('titulo', 'Política de tratamiento de datos personales')
@section('descripcion', 'Política de tratamiento de datos personales de Importadora Sur Alpine, conforme a la Ley 1581 de 2012 y el Decreto 1377 de 2013 de Colombia.')

@section('contenido')
    @php
        $r = config('habeas.responsable');
        $version = config('habeas.version');
        $vigencia = config('habeas.vigente_desde');
    @endphp

    {{-- Cabecera sobria: es un documento legal, no una promoción. --}}
    <section class="relative overflow-hidden bg-tinta-900">
        <div class="absolute inset-0 bg-gradient-to-br from-marca-900 via-tinta-900 to-noche" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-3xl px-4 py-14 sm:py-20">
            <p class="font-titulo text-xs font-bold uppercase tracking-[0.18em] text-marca-300">Habeas Data</p>
            <h1 class="mt-3 text-[1.85rem] font-extrabold leading-[1.05] text-white text-balance sm:text-[2.75rem]">
                Política de tratamiento<br>
                <span class="text-marca-300">de datos personales</span>
            </h1>
            <p class="mt-5 text-sm text-marca-100">
                Versión {{ $version }} · Vigente desde el {{ \Illuminate\Support\Carbon::parse($vigencia)->translatedFormat('d \d\e F \d\e Y') }}
            </p>
        </div>
    </section>

    <article class="mx-auto max-w-3xl px-4 py-14 prose prose-slate prose-headings:font-titulo prose-headings:text-tinta-900 prose-a:text-marca-700">

        <p class="lead">
            {{ $r['razon_social'] }} (NIT {{ $r['nit'] }}), en cumplimiento de la
            <strong>Ley Estatutaria 1581 de 2012</strong> y el Decreto 1377 de 2013 de la República
            de Colombia, informa a sus clientes, proveedores, aliados y usuarios de este sitio
            web la política que aplica al tratamiento de sus datos personales.
        </p>

        <h2>1. Responsable del tratamiento</h2>
        <p>
            {{ $r['razon_social'] }}, con dirección en {{ $r['direccion'] }}, teléfono
            {{ $r['telefono'] }} y correo electrónico
            <a href="mailto:{{ $r['correo'] }}">{{ $r['correo'] }}</a>, es el responsable
            del tratamiento de los datos que se recojan a través del sitio web
            <em>suralpine.com</em>, sus canales de atención y su punto de venta.
        </p>

        <h2>2. Datos que se recogen</h2>
        <ul>
            <li>Nombre y apellidos.</li>
            <li>Número de teléfono y correo electrónico.</li>
            <li>Datos del vehículo (marca, modelo, cilindraje, año, placa opcional).</li>
            <li>Historial de mantenimientos que el usuario registre en su cuenta.</li>
            <li>Historial de solicitudes de cotización.</li>
            <li>Dirección IP y datos técnicos del navegador cuando se envía una solicitud.</li>
        </ul>

        <h2>3. Finalidad del tratamiento</h2>
        <ul>
            <li>Atender solicitudes de cotización y contacto.</li>
            <li>Enviar por correo electrónico información asociada a esas solicitudes.</li>
            <li>Recordar al cliente los mantenimientos próximos de sus vehículos registrados.</li>
            <li>Gestionar el registro y el acceso a la cuenta del usuario.</li>
            <li>Cumplir con obligaciones legales y contables.</li>
        </ul>
        <p>
            {{ $r['razon_social'] }} <strong>no vende ni cede</strong> datos personales a
            terceros con fines comerciales, y sólo los comparte con proveedores de mensajería
            de correo cuando es indispensable para prestar el servicio.
        </p>

        <h2>4. Derechos del titular</h2>
        <p>
            Como titular de los datos usted tiene derecho a:
        </p>
        <ul>
            <li>Conocer, actualizar y rectificar sus datos.</li>
            <li>Solicitar prueba de la autorización otorgada.</li>
            <li>Ser informado del uso que se ha dado a sus datos.</li>
            <li>Presentar quejas ante la Superintendencia de Industria y Comercio.</li>
            <li>Revocar la autorización y solicitar la supresión del dato, cuando la ley lo permita.</li>
            <li>Acceder de forma gratuita a los datos que hayan sido objeto de tratamiento.</li>
        </ul>

        <h2>5. Cómo ejercer sus derechos</h2>
        <p>
            Toda solicitud debe dirigirse al correo
            <a href="mailto:{{ $r['correo'] }}">{{ $r['correo'] }}</a> con el asunto
            <em>«Habeas Data»</em>, indicando nombre, número de identificación, correo y
            descripción del pedido. Se responderá en un plazo máximo de quince (15) días hábiles,
            conforme al artículo 15 de la Ley 1581 de 2012.
        </p>
        <p>
            Si tiene una cuenta en el sitio, también puede solicitar la baja definitiva
            desde <a href="{{ route('cuenta') }}">Mi cuenta</a>. La baja detiene la sesión
            de inmediato y desactiva la cuenta; los datos históricos de cotizaciones se
            conservarán únicamente por el tiempo que exijan las obligaciones tributarias
            y contables.
        </p>

        <h2>6. Vigencia y cambios</h2>
        <p>
            Esta política rige desde el
            {{ \Illuminate\Support\Carbon::parse($vigencia)->translatedFormat('d \d\e F \d\e Y') }}
            y se conserva hasta que sea modificada. Cuando el texto cambie, se registrará una
            versión nueva y se pedirá a los usuarios activos que la autoricen otra vez al usar
            el sitio.
        </p>

        <hr class="my-10">

        <p class="text-sm text-slate-500">
            Para escribir sobre este documento: {{ $r['razon_social'] }} —
            {{ $r['direccion'] }} — {{ $r['telefono'] }} —
            <a href="mailto:{{ $r['correo'] }}">{{ $r['correo'] }}</a>.
        </p>
    </article>
@endsection
