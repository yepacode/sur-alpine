@extends('layouts.app')

@section('titulo', 'Términos y condiciones de uso')
@section('descripcion', 'Términos y condiciones de uso del sitio web de Importadora Sur Alpine S.A.')

{{--
    Términos y condiciones.

    El texto es, palabra por palabra, el que la empresa tiene publicado hoy en
    /condiciones-de-uso/. No se tocó ni una coma: es un documento legal y
    reescribirlo no nos corresponde.

    Sí quedan tres cosas señaladas para que las revise su abogado, porque saltan
    a la vista y ninguna es nuestra:

      · el documento se abre a nombre de «INDUSTRIA COLOMBIANA DE AUTOPARTES
        S.A.», que no es la razón social de Importadora Sur Alpine S.A.;
      · el punto 1.1 (d) habla de «instrumentos musicales de la gama YAMAHA»;
      · el punto 1.5 somete las disputas a los tribunales de Medellín, y la
        empresa opera en Bogotá.

    Parecen restos de una plantilla de otra compañía. Se dejan tal cual para no
    inventar, y se avisa para que se corrijan en la fuente.
--}}
@push('cabeza')
    <x-pagina-schema tipo="WebPage" nombre="Términos y condiciones de uso" :miga="['Términos y condiciones' => route('terminos')]" />
@endpush

@section('contenido')
    @php
        // La fecha sale del panel. Antes el campo existía en «Configuración de
        // página» y NO se pintaba en ninguna parte: se guardaba y no cambiaba
        // nada, que es peor que no tenerlo.
        $vigencia = contenido('terminos.vigencia', (string) config('habeas.vigente_desde'));
    @endphp

    <section class="relative overflow-hidden bg-marca-800">
        <div class="contenedor px-[3vw] py-14 sm:py-16">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-marca-200">Legales</p>
            <h1 class="mt-3 max-w-3xl text-[1.85rem] font-extrabold leading-[1.1] text-white sm:text-[2.5rem]">
                Términos y condiciones de uso
            </h1>
        </div>
    </section>

    <div class="contenedor px-[3vw] py-12">
        {{-- El documento que el cliente escriba en el panel MANDA sobre el de
             fábrica. Es un texto que redacta un abogado y que cambia sin
             avisar: tenerlo clavado en el código obligaba a llamarnos para
             cambiarle una coma. Vacío, se sigue mostrando el de abajo. --}}
        @php $cuerpoDelPanel = trim(contenido('terminos.cuerpo', '')); @endphp

        <div class="mx-auto max-w-3xl space-y-5 text-base leading-relaxed text-tinta-700">
            @if ($cuerpoDelPanel !== '')
                {!! documento_legal($cuerpoDelPanel) !!}
            @else
            <p>INDUSTRIA COLOMBIANA DE AUTOPARTES S.</p>
            <p>A. (En adelante “SURALPINE”) ha abierto este sitio www.suralpine.com en Internet (En adelante el “Sitio”) para el acceso y la utilización de diversos servicios y contenidos (En adelante el “contenido”) puestos a disposición de los usuarios del Sitio (En adelante “Usuarios”) por SURALPINE o por terceros usuarios del Sitio y/o terceros proveedores de servicios y contenidos (en adelante, los “Servicios”).</p>
            <p>El derecho para utilizar este Sitio y su contenido está garantizado si usted acepta cumplir los Términos y Condiciones de Uso (en adelante, los “Términos y Condiciones”) e igualmente todos los avisos, reglamentos de uso e instrucciones puestos en conocimiento del Usuario por el Sitio, que completan lo previsto en estos Términos y Condiciones en cuanto no se opongan a ellas.</p>
            <h2 class="pt-6 text-xl font-extrabold text-tinta-900 sm:text-2xl">1. Términos y Condiciones</h2>
            <h3 class="pt-4 text-lg font-bold text-marca-700">1.1. Generalidades</h3>
            <p>El uso de este Sitio y/o registrarse como usuario implica la aceptación de los Términos y Condiciones publicados en el mismo momento en que el Usuario acceda al Sitio como todos los avisos, reglamentos de uso e instrucciones puestos en conocimiento del Usuario por el Sitio que lo complementen.</p>
            <p>SURALPINE se reserva el derecho a modificar unilateralmente o suprimir, en cualquier momento y sin aviso previo, la presentación, configuración y contenido del Sitio, así como los Términos y Condiciones requeridos para utilizar el Sitio.</p>
            <p>El presente sitio es un producto cuyo autor es SURALPINE de acuerdo con la Ley de Propiedad Intelectual, por ello:</p>
            <ul class="space-y-3 pl-1">
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(a) Todos los elementos de la web sin limitaciones, incluyendo, el diseño gráfico, logotipos y contenido están protegidos por las leyes de la Propiedad Intelectual y todos los tratados internacionales referidos al Derecho de Autor.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(b) Todos los derechos del contenido incluido en este sito, así como las imágenes, fotografías, gráficos, dibujos, nombres, textos, logos, eslóganes, diseños, ilustraciones y programas informáticos, videos o secuencias animadas, sonoras o no, y todas las obras integradas en el sitio son propiedad de SURALPINE o de terceros que han autorizado a SURALPINE a utilizarlos.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(c) Los modelos de los vehículos, repuestos, accesorios, instrumentos musicales, programas académicos, culturales o educativos representados en el sitio están protegidos por los derechos de autor y la Ley de la Propiedad Intelectual.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(d) La denominación SURALPINE, el nombre de los vehículos e instrumentos musicales de la gama YAMAHA y los productos y servicios a ellos asociados, son marcas registradas por SURALPINE. Otras marcas que aparecen en este sitio son utilizadas por SURALPINE, bien con la autorización de su titular, bien como simple identificación de productos o servicios propuestos por SURALPINE.</span></li>
            </ul>
            <p>La ausencia de una mención expresa de la protección que otorga la regulación sobre propiedad intelectual no exonera bajo ninguna circunstancia al Usuario de esta responsabilidad.</p>
            <h3 class="pt-4 text-lg font-bold text-marca-700">1.2. Utilización del Sitio</h3>
            <p>La prestación del servicio del Sitio por parte de SURALPINE tiene carácter gratuito para los Usuarios. Ello no obstante, la utilización de algunos Servicios sólo puede hacerse mediante suscripción o registro del Usuario y/o pago de un precio, de la forma en que se indica expresamente en sus correspondientes secciones.</p>
            <p>El Usuario se obliga a usar los Contenidos de forma diligente, correcta y lícita y, en particular, se compromete a no realizar las conductas descritas a continuación sin haber obtenido la autorización previa de SURALPINE, o de las sanciones establecidas, a saber se abstiene de:</p>
            <ul class="space-y-3 pl-1">
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(a) Utilizar los Contenidos de forma, con fines o efectos contrarios a la ley, a la moral y a las buenas costumbres generalmente aceptadas o al orden público.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(b) Reproducir, copiar, representar, utilizar, distribuir, transformar o modificar los Contenidos, por cualquier procedimiento o sobre cualquier soporte, total o parcial del sitio o permitir el acceso del público a través de cualquier modalidad de comunicación pública, distintos de los que, según los casos, se hayan puesto a su disposición a este efecto o se hayan indicado a este efecto en las páginas web donde se encuentren los Contenidos o, en general, de los que se empleen habitualmente en Internet a este efecto siempre que no entrañen un riesgo de daño o inutilización del Sitio, de los Servicios y/o de los Contenidos.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(c) Suprimir, eludir o manipular el “copyright” y demás datos identificativos de los derechos de SURALPINE o de sus titulares incorporados a los Contenidos, así como los dispositivos técnicos de protección, las huellas digitales o cualquier mecanismo de información que pudieren contener los Contenidos.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(d) Emplear los Contenidos y, en particular, la información de cualquier clase obtenida a través del Sitio o de los Servicios para distribuir, transmitir, remitir, modificar, rehusar o reportar la publicidad o los contenidos del Sitio con fines de venta directa o con cualquier otra clase de finalidad comercial, mensajes no solicitados dirigidos a una pluralidad de personas con independencia de su finalidad, así como a abstenerse de comercializar o divulgar de cualquier modo dicha información. En consecuencia el Usuario podrá descargar el material que tenemos aquí se publica, en soporte papel o informático, sólo para fines personales y con la citación de la fuente.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(e) Utilizar el Sitio y los Servicios con fines o efectos ilícitos, contrarios a lo establecido en estos Términos y Condiciones, lesivos de los derechos e intereses de terceros, o que de cualquier forma puedan dañar, inutilizar, sobrecargar o deteriorar el Sitio y los Servicios o impedir la normal utilización o disfrute del Sitio y de los Servicios por parte de los Usuarios.</span></li>
            </ul>
            <p>La puesta en marcha de un vínculo hipertexto en el sitio www.suralpine.com necesita una autorización previa escrita de SURALPINE y sólo podrá establecerse con la página primera del portal (home-page). Si usted desea poner en marcha un vínculo hipertexto en su sitio, debe consecuentemente tomar contacto con el Responsable del sitio www.suralpine.com por correo a la siguiente dirección:</p>
            <ul class="space-y-3 pl-1">
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>info@suralpine.com</span></li>
            </ul>
            <p>El Usuario responderá de los daños y perjuicios de toda naturaleza que SURALPINE pueda sufrir, directa o indirectamente, como consecuencia de incumplimiento de cualquiera de las obligaciones derivadas de los Términos y Condiciones o de la ley en relación con la utilización del Sitio.</p>
            <h3 class="pt-4 text-lg font-bold text-marca-700">1.3. Política de Privacidad</h3>
            <p>SURALPINE respeta plenamente el derecho a la privacidad de sus Usuarios y aplica las medidas que se indican a continuación con relación a cualquier información suministrada u obtenida acerca de los Usuarios de este Sitio.</p>
            <p>Para utilizar algunos Contenidos o Servicios, los Usuarios deben proporcionarán previamente a SURALPINE ciertos datos de carácter personal. Los datos que habitualmente se solicitan pueden incluir, sin limitación, su nombre, dirección, número de teléfono, e-mail y tarjeta débito y/o crédito. Al facilitar esta información a SURALPINE, el Usuario acuerda permitirnos utilizar esta información para completar todas las operaciones que solicite a través de este Sitio y a divulgar dicha información y los detalles de todas las operaciones a cualquier procesador de pago necesario y operador de transporte. Debido a la naturaleza de Internet, dichos datos pueden cruzar cualquier país.</p>
            <h3 class="pt-4 text-lg font-bold text-marca-700">1.4. Exclusiones de responsabilidad y garantía</h3>
            <p>El Usuario emplea el sitio bajo su entera responsabilidad y acepta que SURALPINE se exima de las siguientes responsabilidades y garantías, dándoles el mayor alcance que la ley le permita:</p>
            <ul class="space-y-3 pl-1">
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(a) SURALPINE no garantiza la disponibilidad y continuidad del funcionamiento del Sitio y de los Servicios. Cuando ello sea razonablemente posible, SURALPINE advertirá previamente las interrupciones en el funcionamiento del Sitio y de los Servicios. SURALPINE tampoco garantiza la utilidad del Sitio y de los Servicios para la realización de ninguna actividad en particular, ni su infalibilidad y, en particular, aunque no de modo exclusivo, que los Usuarios puedan efectivamente utilizar el Sitio y los Servicios, acceder a las distintas páginas web que forman el Sitio o a aquellas desde las que se prestan los Servicios.SURALPINE se exime de cualquier responsabilidad por los daños y perjuicios de toda naturaleza que puedan deberse a la falta de disponibilidad o de continuidad del funcionamiento del sitio y de los servicios, a la defraudación de la utilidad que los usuarios hubieren podido atribuir al sitio y a los servicios, a la falibilidad del sitio y de los servicios, y en particular, aunque no de modo exclusivo, a los fallos en el acceso a las distintas páginas web del sitio o a aquellas desde las que se prestan los servicios.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(b) SURALPINE no puede en ningún caso tenerse por responsable de la puesta a disposición de los sitio que son objeto de un vínculo hipertexto a partir del sitio www.suralpine.com y no puede aceptar ninguna responsabilidad sobre el contenido, los productos, los servicios y demás elementos disponibles en estos sitios o a partir de estos sitios.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(c) En ningún caso, ni SURALPINE, ni sus filiales, ni los miembros de su red podrán considerarse responsables de los daños directos o indirectos y, en especial perjuicio material, pérdida de datos o del programa, perjuicio financiero, que resulte del acceso o de la utilización de este sitio o de cualquier sitio al que estén vinculados.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(d) SURALPINE no presta ningún tipo de asesoría legal, profesional o de cualquier otra índole a través de esta página. La información suministrada tiene como fin primordial proveer información general acerca de los productos y servicios, así como los medios para acceder a éstos.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(e) Las informaciones sobre los modelos y sus características corresponden a una definición en el momento de la puesta en línea o de las actualizaciones de las diferentes páginas del sitio; sólo se dan a título indicativo y no deben considerarse una oferta contractual de productos o servicios de SURALPINE, de sus filiales o de los miembros de su red.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(f) SURALPINE no asume la responsabilidad por daños a, o virus que puedan infectar su computador o otras propiedades dado su acceso a usar, o navegar el Sitio o su descarga de cualquier material, entre ellos textos, imágenes, video o audio del Sitio.</span></li>
                <li class="flex gap-3"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-alerta-500"></span><span>(g) SURALPINE no garantiza la privacidad y seguridad de la utilización del Sitio y de los Servicios y, en particular, no garantiza que terceros no autorizados no puedan tener conocimiento de la clase, condiciones, características y circunstancias del uso que los Usuarios hacen del Sitio y de los Servicios.SURALPINE se exime de toda responsabilidad por los daños y perjuicios de toda naturaleza que pudieran deberse al conocimiento que puedan tener terceros no autorizados de la clase, condiciones, características y circunstancias del uso que los usuarios hacen del sitio y de los servicios.* (h) Las opiniones publicadas de los Usuarios sobre los servicios y/o productos que ofrece la página no comprometen de ninguna manera SURALPINE.</span></li>
            </ul>
            <h3 class="pt-4 text-lg font-bold text-marca-700">1.5. Ley Aplicable y Jurisdicción</h3>
            <p>Estos Términos y Condiciones de Uso se rigen por las leyes colombianas y el Usuario renuncia de forma expresa a cualquier otro fuero, se someten al de los Juzgados y Tribunales de la ciudad de Medellín. Todas las disputas que resulten del presente Contrato se someterán exclusivamente a la jurisdicción de los Tribunales Superiores del Estado Colombiano (según se permita por ley) y cada parte acuerda no impugnar la jurisdicción personal de dichos tribunales. Sin perjuicio de lo indicado anteriormente, SURALPINE tendrá derecho a interponer y comenzar cualquier acción legal o equitativa o procedimiento ante cualquier tribunal competente que no sea Colombiano para obtener medidas cautelares o cualquier otra medida en el caso de que, en opinión de SURALPINE, dicha acción sea necesaria o deseable.</p>
            <p>Ninguna acción de SURALPINE, que no sea una renuncia o modificación expresa por escrito, puede ser interpretada como una renuncia o modificación de cualquiera de los presentes Términos y Condiciones de Uso, o Política de Privacidad. En el caso de que cualquier cláusula de los presentes Términos y Condiciones de Uso o Política de Privacidad no fuera aplicable, esto no afectará, donde sea posible, cualquier otra cláusula y cada una permanecerá plenamente vigente y con efecto.</p>
            <p>El Usuario acepta que no existe ninguna relación de colaboración empresarial, asociación, vínculo laboral o de mediación con SURALPINE como resultado del uso de su portal y de los demás servicios asociados</p>
            @endif
        </div>

        <p class="mx-auto mt-10 max-w-3xl text-sm text-tinta-500">
            Última actualización:
            <time datetime="{{ $vigencia }}">
                {{ \Illuminate\Support\Carbon::parse($vigencia)->translatedFormat('j \d\e F \d\e Y') }}
            </time>
        </p>

        <p class="mx-auto mt-6 max-w-3xl rounded-2xl bg-marca-50 p-6 text-sm text-marca-900 ring-1 ring-marca-100">
            ¿Dudas sobre estos términos? Escríbenos a
            <a href="mailto:info@suralpine.com" class="font-semibold underline underline-offset-2">info@suralpine.com</a>
            o llámanos al
            <a href="tel:{{ $contacto->pbxTel() }}" class="font-semibold underline underline-offset-2">{{ $contacto->pbx() }}</a>.
        </p>
    </div>
@endsection
