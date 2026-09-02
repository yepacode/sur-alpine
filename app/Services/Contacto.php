<?php

namespace App\Services;

use App\Models\Configuracion;

/**
 * Los datos de contacto del negocio, en un solo sitio.
 *
 * Antes vivían quemados en ocho lugares distintos mientras el panel ya permitía
 * editarlos. El caso peor estaba en la cabecera: el texto del PBX salía de la
 * configuración pero el enlace `tel:` era fijo, así que al cambiar el número el
 * visitante veía el nuevo, lo tocaba y llamaba al viejo.
 *
 * Aquí también se normaliza para marcar: «(601) 366 0066» se muestra tal cual y
 * se enlaza como «+576013660066».
 */
class Contacto
{
    public const INDICATIVO = '+57';

    public function pbx(): string
    {
        return (string) Configuracion::valor('telefono_pbx', '(601) 366 0066');
    }

    public function pbxTel(): string
    {
        return $this->paraMarcar($this->pbx());
    }

    /**
     * Los celulares se guardan separados por coma; salen listos para pintar y
     * para marcar.
     *
     * @return array<int, array{texto: string, tel: string}>
     */
    public function celulares(): array
    {
        $crudo = (string) Configuracion::valor('celulares', '313 422 3861, 310 205 8051');

        return collect(preg_split('/[,;\n]+/', $crudo))
            ->map(fn ($numero) => trim($numero))
            ->filter()
            ->map(fn ($numero) => ['texto' => $numero, 'tel' => $this->paraMarcar($numero)])
            ->values()
            ->all();
    }

    /**
     * La calle y el barrio, sin ciudad: el dato estructurado las pide aparte.
     *
     * El barrio va DENTRO de este valor y ya no clavado en las vistas. Estaba
     * pegado detras en la portada y en «Contactenos», y en produccion salia
     * «Av. Caracas 19-21 sur, Barrio Restrepo, Barrio Restrepo.»: quien lleno
     * el campo del panel escribio la direccion entera, que es lo que cualquiera
     * haria viendo una casilla que dice «Direccion». Mismo error que el de los
     * textos: la pantalla que edita tiene que ensenar lo que se publica, sin
     * anadidos invisibles.
     *
     * Es «19-15», no «19-21». El sitio de la empresa se contradice a sí mismo
     * —su meta descripción dice 19-21— pero los dos sitios donde el dato se ve
     * y se usa dicen 19-15: la sección «¿Dónde estamos ubicados?» y la política
     * de tratamiento de datos, que es donde se pide que la gente escriba para
     * ejercer sus derechos. Entre un meta que nadie lee y la dirección a la que
     * se manda correo legal, manda la segunda.
     */
    public function direccion(): string
    {
        return (string) Configuracion::valor('direccion', 'Av. Caracas #19-15 sur, Barrio Restrepo');
    }

    public function ciudad(): string
    {
        return (string) Configuracion::valor('ciudad', 'Bogotá D.C.');
    }

    /** Como se lee de corrido: «Av. Caracas 19-21 sur, Bogotá D.C.» */
    public function direccionCompleta(): string
    {
        return trim($this->direccion().', '.$this->ciudad(), ', ');
    }

    /**
     * El WhatsApp de atención.
     *
     * El valor por defecto es el que su sitio actual tiene puesto en el botón
     * flotante. Antes esto salía vacío mientras nadie lo escribiera en el
     * panel, y el botón —que es por donde entra media atención de este
     * negocio— sencillamente no se pintaba.
     */
    public function whatsapp(): ?string
    {
        $numero = trim((string) Configuracion::valor('whatsapp', '313 422 3861'));

        return $numero === '' ? null : $numero;
    }

    /**
     * El enlace de WhatsApp, ya armado.
     *
     * Con `$mensaje` el chat se abre con el texto escrito: quien atiende ve de
     * entrada desde dónde llegó la persona en vez de un «Hola» a secas.
     */
    public function whatsappUrl(?string $mensaje = null): ?string
    {
        $numero = $this->whatsapp();

        if (! $numero) {
            return null;
        }

        $url = 'https://wa.me/'.ltrim($this->paraMarcar($numero), '+');

        return $mensaje ? $url.'?text='.rawurlencode($mensaje) : $url;
    }

    /*
     * Los perfiles reales de la empresa como valor por defecto. Antes esto
     * devolvía null mientras nadie los escribiera en el panel, y la columna de
     * redes del pie salía con el título y ningún enlace debajo.
     */
    public function facebook(): ?string
    {
        return $this->url('facebook', 'https://www.facebook.com/Importadorasuralpinesa');
    }

    public function instagram(): ?string
    {
        return $this->url('instagram', 'https://www.instagram.com/importadorasuralpine');
    }

    /**
     * Los perfiles que confirman ante Google que este es el negocio real y no
     * uno de los sitios que lo suplantan.
     *
     * @return array<int, string>
     */
    public function redes(): array
    {
        return array_values(array_filter([$this->facebook(), $this->instagram()]));
    }

    /**
     * El correo público del negocio.
     *
     * Sale del primero de los que reciben cotizaciones: es el buzón que el
     * equipo atiende de verdad. Google lo cruza con la ficha de Google
     * Business para confirmar que la web y el local son el mismo negocio.
     */
    public function correo(): ?string
    {
        $crudo = (string) Configuracion::valor('correos_cotizacion', '');

        $primero = collect(preg_split('/[,;\s]+/', $crudo))
            ->map(fn ($c) => trim($c))
            ->first(fn ($c) => filter_var($c, FILTER_VALIDATE_EMAIL));

        return $primero ?: null;
    }

    /**
     * El horario en el formato que entiende Google.
     *
     * Sale de «Configuración», en HH:MM de 24 horas, y NO del texto que se
     * lee en pantalla —«Lunes a viernes de 8:00 a.m. a 6:00 p.m.»—: adivinar
     * horas a partir de una frase escrita a mano se rompe el día que alguien
     * escriba «8 am» o «de 8 a 6». Son dos campos distintos a propósito: uno
     * para leer y otro para la máquina.
     *
     * Si el rango viene vacío o mal escrito, no se emite nada. Un horario
     * equivocado en la ficha del negocio es peor que ninguno: manda gente a
     * un local cerrado.
     *
     * @return list<array<string, mixed>>
     */
    public function horarioSchema(): array
    {
        $rangos = [
            'horario_semana' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'horario_sabado' => ['Saturday'],
            // Los festivos tambien: la pagina los anunciaba —«Festivos de 9:00
            // a 1:00»— y la ficha de Google no los mencionaba, asi que alguien
            // que preguntaba «esta abierto hoy» un 20 de julio recibia un no
            // que era falso.
            'horario_festivo' => ['PublicHolidays'],
        ];

        $salida = [];

        foreach ($rangos as $clave => $dias) {
            $crudo = trim((string) Configuracion::valor($clave, ''));

            if (! preg_match('/^(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$/', $crudo, $partes)) {
                continue;
            }

            $salida[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $dias,
                'opens' => $partes[1],
                'closes' => $partes[2],
            ];
        }

        return $salida;
    }

    /**
     * El horario escrito para leerse, sacado del MISMO sitio que el de Google.
     *
     * Habia dos horarios que no se hablaban: el que lee una persona vivia en
     * los textos («Lunes a viernes de 8:00 a.m. a 6:00 p.m.») y el que alimenta
     * la ficha de Google en la configuracion, en 24 horas. Son dos pantallas
     * distintas del panel: quien cambiaba uno no cambiaba el otro, y el sitio
     * podia estar anunciando dos horarios diferentes a la vez.
     *
     * Esto arma la frase desde el rango de la configuracion, y se usa como
     * valor POR DEFECTO del texto editable. Asi, quien sólo cambia la hora en
     * un sitio la cambia en los dos; y quien quiera redactarlo distinto
     * —«hasta las 6, o hasta que se vaya el ultimo cliente»— sigue pudiendo.
     */
    public function horarioTexto(string $clave, string $prefijo): ?string
    {
        $crudo = trim((string) Configuracion::valor($clave, ''));

        if (! preg_match('/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', $crudo, $p)) {
            return null;
        }

        $enLetra = static function (int $hora, string $minutos): string {
            $sufijo = $hora < 12 ? 'a.m.' : 'p.m.';
            $doce = $hora % 12 === 0 ? 12 : $hora % 12;

            return $doce.':'.$minutos.' '.$sufijo;
        };

        return $prefijo.' de '.$enLetra((int) $p[1], $p[2]).' a '.$enLetra((int) $p[3], $p[4]);
    }

    public function mapaUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='
            .rawurlencode('Importadora Sur Alpine, '.$this->direccionCompleta());
    }

    private function url(string $clave, ?string $defecto = null): ?string
    {
        $valor = trim((string) Configuracion::valor($clave, $defecto));

        return $valor === '' ? null : $valor;
    }

    /**
     * «(601) 366 0066» → «+576013660066».
     *
     * Si el número ya trae indicativo se respeta; si no, se le pone el de
     * Colombia, que es donde está el único punto de atención.
     */
    private function paraMarcar(string $numero): string
    {
        if (str_starts_with(trim($numero), '+')) {
            return '+'.preg_replace('/\D/', '', $numero);
        }

        return self::INDICATIVO.preg_replace('/\D/', '', $numero);
    }
}
