{{--
    El telón de entrada: la pantalla en negro y el logo llenándose de abajo
    hacia arriba, como carga una batería.

    Tres decisiones que no se ven pero sostienen esto:

    · Se muestra UNA vez por sesión. Un telón que aparece en cada clic deja de
      ser un detalle y se vuelve un peaje: este sitio se navega saltando de
      una pieza a otra, y a la quinta vez ya nadie lo encuentra simpático.

    · La clase la pone un script en línea, ANTES de pintar. Si el telón
      viniera visible por defecto y JavaScript lo escondiera después, quien ya
      lo vio en esta sesión alcanzaría a verlo parpadear. Al revés no: si el
      script no corre —JavaScript apagado, un error antes— la clase nunca se
      pone y no hay telón. Que falle hacia «no hay telón» y no hacia «pantalla
      negra pegada» es la mitad del diseño.

    · Se quita con `load`, pero también con un temporizador de seguridad. Una
      foto que no baja no puede dejar a nadie mirando un logo a medio llenar.
--}}
<div id="cargador" class="cargador" aria-hidden="true">
    <div class="cargador-logo">
        {{-- Dos veces la misma imagen: la de abajo apagada, la de arriba a
             color y recortada por arriba. Lo que se anima es ese recorte. --}}
        <img src="/img/logo/logo-en-png-sur-alpine.webp" alt="" width="280" height="351"
             fetchpriority="high" decoding="sync" class="cargador-apagado">
        <img src="/img/logo/logo-en-png-sur-alpine.webp" alt="" width="280" height="351"
             fetchpriority="high" decoding="sync" class="cargador-lleno">

        {{-- La línea que sube pegada al borde del relleno. Sin ella el logo
             sólo «aparece»; con ella se está cargando. --}}
        <span class="cargador-nivel"></span>
    </div>

    <p class="cargador-marca">
        IMPORTADORA <span>SUR ALPINE</span>
    </p>
</div>
