<?php

namespace App\Models;

use App\Enums\Rol;
use App\Notifications\ClaveOlvidada;
use App\Notifications\CorreoVerificar;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * `MustVerifyEmail` está puesto para poder OFRECER la confirmación, no para
 * exigirla: no hay ninguna ruta detrás del middleware `verified`.
 *
 * Es a propósito. Exigirla dejaría fuera de un golpe a todas las cuentas que
 * ya existen y convertiría «pedir una cotización» en «pedir una cotización,
 * ir al correo y volver». Lo que sí resuelve es lo que de verdad duele: que
 * alguien se equivoque al teclear su dirección y nadie se entere hasta que la
 * confirmación de su cotización rebota.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * `rol`, `activo`, `proveedor` y `proveedor_id` NO están aquí.
     *
     * Hoy ningún sitio hace `update($request->all())` —lo revisé entero— así
     * que no es explotable. Pero es la clase de mina que estalla el día que
     * alguien escriba `->update($request->validated())` sobre `User` con un
     * `rol` colado en el formulario: eso es escalada de privilegios. Se
     * asignan siempre a mano, que es lo que ya hacen los cuatro sitios que
     * los tocan.
     */
    protected $fillable = [
        'name', 'email', 'password', 'telefono',
        'acepto_en', 'politica_version', 'baja_solicitada_en',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
            'activo' => 'boolean',
            'acepto_en' => 'datetime',
            'baja_solicitada_en' => 'datetime',
        ];
    }

    /**
     * El correo de «olvidé mi contraseña».
     *
     * Se sobrescribe por dos razones: para mandar el nuestro —en español y
     * con la plantilla del sitio— y para NO mandar nada si la cuenta está
     * desactivada. Sin ese cierre, alguien a quien el administrador sacó
     * vuelve a entrar cambiándose la contraseña.
     *
     * No se avisa de que no se mandó: quien pide el enlace ve siempre la
     * misma respuesta, exista la cuenta o no.
     */
    public function sendPasswordResetNotification($token): void
    {
        if (! $this->activo) {
            return;
        }

        $this->notify(new ClaveOlvidada($token));
    }

    /** El correo de «confirma tu correo», en español y con nuestra plantilla. */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CorreoVerificar);
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function vehiculosGuardados(): BelongsToMany
    {
        return $this->belongsToMany(Vehiculo::class, 'vehiculos_usuario')
            ->withPivot(['alias', 'placa'])
            ->withTimestamps();
    }

    public function puede(Rol $minimo): bool
    {
        return $this->activo && $this->rol->alcanza($minimo);
    }

    public function entraAlPanel(): bool
    {
        return $this->activo && $this->rol->entraAlPanel();
    }

    /** Nombre corto para saludar en el panel. */
    public function getPrimerNombreAttribute(): string
    {
        return explode(' ', trim($this->name))[0];
    }
}
