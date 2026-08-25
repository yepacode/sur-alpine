<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'rol', 'telefono', 'activo',
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

    public function esAdmin(): bool
    {
        return $this->puede(Rol::Admin);
    }

    /** Nombre corto para saludar en el panel. */
    public function getPrimerNombreAttribute(): string
    {
        return explode(' ', trim($this->name))[0];
    }
}
