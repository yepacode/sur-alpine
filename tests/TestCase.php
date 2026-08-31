<?php

namespace Tests;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea un usuario para una prueba.
     *
     * Existe por dos motivos que se juntaron:
     *
     * · `rol` y `activo` salieron de `$fillable` en `User` —son la clase de
     *   campo que no debe poder llegar desde una petición— así que
     *   `User::create(['rol' => ...])` los ignora en silencio y la prueba se
     *   queda con un cliente donde esperaba un administrador. Aquí se asignan
     *   a mano, que es lo mismo que hace el código de producción.
     *
     * · Trece clases de prueba definían su propio `admin()`/`cliente()`, cada
     *   una con forma distinta: unas con teléfono, otras no; unas con
     *   `firstOrCreate`, otras con `create`. Un solo sitio.
     */
    protected function usuario(Rol $rol = Rol::Cliente, array $atributos = []): User
    {
        $activo = $atributos['activo'] ?? true;
        unset($atributos['rol'], $atributos['activo']);

        $usuario = User::firstOrNew([
            'email' => $atributos['email'] ?? mb_strtolower($rol->value).'@prueba.test',
        ]);

        $usuario->fill($atributos + [
            'name' => $rol === Rol::Admin ? 'Administradora de prueba' : 'Cliente de prueba',
            'telefono' => '3134223861',
        ]);

        $usuario->password = $atributos['password'] ?? 'secreto123';
        $usuario->rol = $rol;
        $usuario->activo = $activo;
        $usuario->save();

        return $usuario;
    }

    /**
     * Cambia de identidad dentro de una misma prueba.
     *
     * Hace falta `flushSession()` en medio: la sesión queda atada a la
     * contraseña del primero (middleware `AuthenticateSession`) y reusarla con
     * otra cuenta lo saca, así que la segunda petición sale 302 en vez de 403.
     * Un navegador no puede cambiar de identidad sin cerrar sesión; esto es
     * sólo el atajo de las pruebas, y aquí se paga.
     *
     * El comentario estaba copiado literalmente en tres clases.
     */
    protected function entrarComo(User $usuario): static
    {
        $this->flushSession();
        $this->actingAs($usuario);

        return $this;
    }
}
