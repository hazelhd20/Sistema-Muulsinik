<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'active',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Verifica si el usuario tiene un permiso específico. */
    public function hasPermission(string $permission): bool
    {
        if (!$this->role) {
            return false;
        }

        $permissions = $this->role->permissions ?? [];

        // Administrador con permiso comodín
        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    /** Verifica si el usuario tiene un rol específico por nombre. */
    public function hasRole(string $roleName): bool
    {
        return $this->role?->name === $roleName;
    }

    /**
     * Retorna todos los usuarios con permiso para aprobar requisiciones.
     */
    public static function getApprovers(): \Illuminate\Support\Collection
    {
        return static::with('role')
            ->where('active', true)
            ->whereHas('role', function ($query) {
                $query->whereJsonContains('permissions', 'requisiciones.aprobar')
                    ->orWhereJsonContains('permissions', '*');
            })
            ->get();
    }

    /**
     * Retorna la URL óptima de la fotografía del avatar sin pasar por PHP:
     * - S3/Tigris: URL pre-firmada temporal válida por 120 minutos (para cubrir una sesión laboral completa).
     * - Local/Public: URL estática directa vía Storage::url().
     * - URL externa (http/https): se retorna tal cual.
     * - Sin avatar: null.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        // URL externa completa (ej. OAuth avatar) — no requiere resolución
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        // Resolver sin round-trip PHP: S3 pre-signed URL o URL pública local
        return \App\Support\StorageResolver::resolveUrl($this->avatar)
            ?? route('file.preview', ['path' => $this->avatar]); // fallback defensivo
    }
}
