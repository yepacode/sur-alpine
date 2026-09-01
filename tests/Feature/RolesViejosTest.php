<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Una cuenta con un rol del esquema viejo no puede tumbar el panel.
 *
 * El sitio nació con cuatro roles y se recortó a dos. La migración que
 * convertía los viejos se borró dando por hecho que no había usuarios ni
 * despliegue; sí los había. En producción quedaron cuentas con «vendedor» y
 * «mostrador», el casteo lanzaba `ValueError` al hidratar, y la lista de
 * usuarios del panel respondía 500 ENTERA: el administrador no podía ver a su
 * equipo ni arreglar el problema desde ahí.
 *
 * Se ve claro en el síntoma que lo delató: `?q=admin` respondía 200 y
 * `?q=vendedor` reventaba.
 */
class RolesViejosTest extends TestCase
{
    use RefreshDatabase;

    private function conRolCrudo(string $rol, string $correo): User
    {
        $usuario = $this->usuario(Rol::Cliente, ['email' => $correo, 'name' => 'Pedro Vendedor']);

        // A pelo, saltándose el casteo: es como llegan de una base vieja.
        DB::table('users')->where('id', $usuario->id)->update(['rol' => $rol]);

        return $usuario;
    }

    public function test_un_rol_desconocido_se_lee_como_cliente(): void
    {
        $usuario = $this->conRolCrudo('vendedor', 'vendedor@ejemplo.test');

        $leido = User::find($usuario->id);

        $this->assertSame(Rol::Cliente, $leido->rol, 'Un valor raro tiene que caer al privilegio mínimo.');
        $this->assertFalse($leido->entraAlPanel(), 'Y desde luego no puede abrir el panel.');
    }

    /** Y la pantalla de usuarios se pinta igual, que es lo que se rompía. */
    public function test_la_lista_de_usuarios_del_panel_no_revienta(): void
    {
        $this->conRolCrudo('vendedor', 'vendedor@ejemplo.test');
        $this->conRolCrudo('mostrador', 'mostrador@ejemplo.test');

        $this->entrarComo($this->usuario(Rol::Admin))
            ->get(route('panel.usuarios'))
            ->assertOk()
            ->assertSee('vendedor@ejemplo.test');
    }

    /** La migración deja el dato bien, no sólo la lectura. */
    public function test_la_migracion_normaliza_los_roles_viejos(): void
    {
        $viejo = $this->conRolCrudo('asesor', 'asesor@ejemplo.test');
        $admin = $this->usuario(Rol::Admin, ['email' => 'jefa@ejemplo.test']);

        (require database_path('migrations/2026_09_01_100000_normalizar_roles_viejos.php'))->up();

        $this->assertSame('cliente', DB::table('users')->where('id', $viejo->id)->value('rol'));
        $this->assertSame('admin', DB::table('users')->where('id', $admin->id)->value('rol'),
            'A un administrador de verdad no se le puede tocar el rol.');
    }

    /** Escribir un rol inválido SÍ tiene que fallar: eso es un error del código. */
    public function test_guardar_un_rol_inventado_falla(): void
    {
        $usuario = $this->usuario(Rol::Cliente);

        $this->expectException(\ValueError::class);

        $usuario->forceFill(['rol' => 'jefe-supremo'])->save();
    }
}
