<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\RecuperacaoSenhaNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'password',
        'url_avatar',
        'biografia',
        // Aliases para retrocompatibilidade
        'name',
        'avatar_url',
        'bio',
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
        ];
    }

    // --- Accessors & Mutators ---

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nome'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nome'] = $value;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->attributes['url_avatar'] ?? null;
    }

    public function setAvatarUrlAttribute(?string $value): void
    {
        $this->attributes['url_avatar'] = $value;
    }

    public function getBioAttribute(): ?string
    {
        return $this->attributes['biografia'] ?? null;
    }

    public function setBioAttribute(?string $value): void
    {
        $this->attributes['biografia'] = $value;
    }

    public function getTimezoneAttribute(): string
    {
        return 'America/Sao_Paulo';
    }

    // --- Relacionamentos Relacionais ---

    public function sessoesEstudo(): HasMany
    {
        return $this->hasMany(SessaoEstudo::class, 'usuario_id');
    }

    public function studySessions(): HasMany
    {
        return $this->sessoesEstudo();
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class, 'usuario_id');
    }

    public function comments(): HasMany
    {
        return $this->comentarios();
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'membros_grupo', 'usuario_id', 'grupo_id')
            ->using(MembroGrupo::class)
            ->withPivot('papel', 'ingressou_em');
    }

    public function groups(): BelongsToMany
    {
        return $this->grupos();
    }

    public function gruposProprios(): HasMany
    {
        return $this->hasMany(Grupo::class, 'proprietario_id');
    }

    public function ownedGroups(): HasMany
    {
        return $this->gruposProprios();
    }

    public function estatisticas(): \App\DTOs\EstatisticasUsuarioDTO
    {
        return app(\App\Services\AgregadorMetricasService::class)->obterEstatisticasUsuario((int) $this->id);
    }

    public function getEstatisticasAttribute(): \App\DTOs\EstatisticasUsuarioDTO
    {
        return $this->estatisticas();
    }

    public function getCacheEstatisticasAttribute(): \App\DTOs\EstatisticasUsuarioDTO
    {
        return $this->estatisticas();
    }

    public function getStatsCacheAttribute(): \App\DTOs\EstatisticasUsuarioDTO
    {
        return $this->estatisticas();
    }

    public function cacheEstatisticas(): \App\DTOs\EstatisticasUsuarioDTO
    {
        return $this->estatisticas();
    }

    public function statsCache(): \App\DTOs\EstatisticasUsuarioDTO
    {
        return $this->estatisticas();
    }

    public function reacoes(): HasMany
    {
        return $this->hasMany(Reacao::class, 'usuario_id');
    }

    public function kudos(): HasMany
    {
        return $this->reacoes();
    }

    public function sendPasswordResetNotification($token): void
    {
        $baseUrl = rtrim((string) (config('app.url_site') ?: env('URL_SITE') ?: config('app.url') ?: url('/')), '/');
        $urlRedefinicao = "{$baseUrl}/redefinir-senha?token={$token}&email=" . urlencode($this->email);

        $this->notify(new RecuperacaoSenhaNotification($urlRedefinicao));
    }
}
