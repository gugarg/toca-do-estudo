<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    public const CREATED_AT = 'data_criacao';
    public const UPDATED_AT = 'data_edicao';

    public const MODALIDADE_DIAS_ATIVOS = 'dias_ativos';
    public const MODALIDADE_HORAS_QUESTOES = 'horas_questoes';

    protected $fillable = [
        'proprietario_id',
        'nome',
        'descricao',
        'codigo_convite',
        'metrica_padrao',
        'pontos_hora',
        'pontos_questao',
        // Aliases para retrocompatibilidade
        'owner_id',
        'name',
        'description',
        'invite_code',
        'default_metric',
        'points_per_hour',
        'points_per_question',
        'weight_hours',
        'weight_questions',
    ];

    protected $casts = [
        'pontos_hora' => 'float',
        'pontos_questao' => 'float',
    ];

    /**
     * Garante a imutabilidade estrita dos pesos e da modalidade após a criação do grupo.
     */
    protected static function booted(): void
    {
        static::updating(function (Grupo $grupo) {
            if ($grupo->isDirty(['pontos_hora', 'pontos_questao', 'metrica_padrao'])) {
                throw new \DomainException('Os pesos e a modalidade de competição do grupo são imutáveis após a criação.');
            }
        });
    }

    // --- Accessors & Mutators ---

    public function getOwnerIdAttribute(): mixed
    {
        return $this->attributes['proprietario_id'] ?? null;
    }

    public function setOwnerIdAttribute(mixed $value): void
    {
        $this->attributes['proprietario_id'] = $value;
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nome'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nome'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['descricao'] ?? null;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['descricao'] = $value;
    }

    public function getInviteCodeAttribute(): ?string
    {
        return $this->attributes['codigo_convite'] ?? null;
    }

    public function setInviteCodeAttribute(?string $value): void
    {
        $this->attributes['codigo_convite'] = $value;
    }

    public function getDefaultMetricAttribute(): ?string
    {
        return $this->attributes['metrica_padrao'] ?? 'dias_ativos';
    }

    public function setDefaultMetricAttribute(?string $value): void
    {
        $this->attributes['metrica_padrao'] = $value;
    }

    public function getPointsPerHourAttribute(): ?float
    {
        return isset($this->attributes['pontos_hora']) ? (float) $this->attributes['pontos_hora'] : null;
    }

    public function setPointsPerHourAttribute(mixed $value): void
    {
        $this->attributes['pontos_hora'] = $value !== null ? (float) $value : null;
    }

    public function getPointsPerQuestionAttribute(): ?float
    {
        return isset($this->attributes['pontos_questao']) ? (float) $this->attributes['pontos_questao'] : null;
    }

    public function setPointsPerQuestionAttribute(mixed $value): void
    {
        $this->attributes['pontos_questao'] = $value !== null ? (float) $value : null;
    }

    // --- Relacionamentos Relacionais ---

    public function proprietario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'proprietario_id');
    }

    public function owner(): BelongsTo
    {
        return $this->proprietario();
    }

    public function membros(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'membros_grupo', 'grupo_id', 'usuario_id')
            ->using(MembroGrupo::class)
            ->withPivot('papel', 'ingressou_em');
    }

    public function members(): BelongsToMany
    {
        return $this->membros();
    }

    public function sessoes(): BelongsToMany
    {
        return $this->belongsToMany(SessaoEstudo::class, 'membros_grupo', 'grupo_id', 'usuario_id', 'id', 'usuario_id')
            ->where('sessoes_estudo.compartilhar', true)
            ->whereRaw('sessoes_estudo.data_estudo >= (SELECT DATE(g.data_criacao) FROM grupos g WHERE g.id = membros_grupo.grupo_id)');
    }

    public function sessions(): BelongsToMany
    {
        return $this->sessoes();
    }

    public function reacoes(): HasMany
    {
        return $this->hasMany(Reacao::class, 'grupo_id');
    }

    public function reactions(): HasMany
    {
        return $this->reacoes();
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class, 'grupo_id');
    }

    public function comments(): HasMany
    {
        return $this->comentarios();
    }

    public function getMembrosCountAttribute(): int
    {
        return (int) ($this->attributes['membros_count'] ?? $this->attributes['members_count'] ?? $this->membros()->count());
    }

    public function getMembersCountAttribute(): int
    {
        return $this->membros_count;
    }
}
