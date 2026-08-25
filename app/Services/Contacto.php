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

    /** La calle, sin ciudad: el dato estructurado las pide por separado. */
    public function direccion(): string
    {
        return (string) Configuracion::valor('direccion', 'Av. Caracas 19-21 sur');
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

    public function whatsapp(): ?string
    {
        $numero = trim((string) Configuracion::valor('whatsapp'));

        return $numero === '' ? null : $numero;
    }

    /** El enlace de WhatsApp, ya armado. */
    public function whatsappUrl(): ?string
    {
        $numero = $this->whatsapp();

        return $numero ? 'https://wa.me/'.ltrim($this->paraMarcar($numero), '+') : null;
    }

    public function facebook(): ?string
    {
        return $this->url('facebook');
    }

    public function instagram(): ?string
    {
        return $this->url('instagram');
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

    public function mapaUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='
            .rawurlencode('Importadora Sur Alpine, '.$this->direccionCompleta());
    }

    private function url(string $clave): ?string
    {
        $valor = trim((string) Configuracion::valor($clave));

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
