@props(['url'])
{{--
    La cabecera de todos los correos.

    El cliente pidió el logo aquí, y hay tres cosas que hacen la diferencia
    entre que se vea y que quede un hueco:

      · el archivo es PNG, no WebP. Outlook de escritorio y varios clientes
        antiguos no pintan WebP;
      · la URL es absoluta, porque el correo se abre fuera del sitio;
      · el `alt` es el nombre de la empresa. Casi todos los gestores bloquean
        las imágenes remotas la primera vez: con esto, quien reciba el correo
        lee «Importadora Sur Alpine» en vez de un cuadro vacío.
--}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ url('/img/logo/logo-correo.png') }}" width="75" height="93"
     alt="Importadora Sur Alpine"
     style="height: 93px; width: 75px; max-height: 93px; margin-top: 15px; margin-bottom: 10px; border: 0; color: #1866e0; font-family: Inter, Arial, sans-serif; font-size: 17px; font-weight: bold; text-decoration: none;">
</a>
</td>
</tr>
