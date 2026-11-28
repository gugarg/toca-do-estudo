<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reacao extends Model
{
    use HasFactory;

    public const ALLOWED_REACTIONS = ['fire', 'books', 'applause', 'target', 'brain'];

    public const CREATED_AT = 'data_criacao';
    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $table = 'reacoes';

    protected $fillable = [
        'sessao_estudo_id',
        'grupo_id',
        'usuario_id',
        'tipo_reacao',
        'data_criacao',
        // Aliases para retrocompatibilidade
        'study_session_id',
        'group_id',
        'user_id',
        'reaction_type',
    ];

    protected function casts(): array
    {
        return [
            'data_criacao' => 'datetime',
        ];
    }

    // --- Accessors & Mutators ---

    public function getStudySessionIdAttribute(): mixed
    {
        return $this->attributes['sessao_estudo_id'] ?? null;
    }

    public function setStudySessionIdAttribute(mixed $value): void
    {
        $this->attributes['sessao_estudo_id'] = $value;
    }

    public function getGroupIdAttribute(): mixed
    {
        return $this->attributes['grupo_id'] ?? null;
    }

    public function setGroupIdAttribute(mixed $value): void
    {
        $this->attributes['grupo_id'] = $value;
    }

    public function getUserIdAttribute(): mixed
    {
        return $this->attributes['usuario_id'] ?? null;
    }

    public function setUserIdAttribute(mixed $value): void
    {
        $this->attributes['usuario_id'] = $value;
    }

    public function getReactionTypeAttribute(): ?string
    {
        return $this->attributes['tipo_reacao'] ?? null;
    }

    public function setReactionTypeAttribute(?string $value): void
    {
        $this->attributes['tipo_reacao'] = $value;
    }

    // --- Relacionamentos Relacionais ---

    public function sessaoEstudo(): BelongsTo
    {
        return $this->belongsTo(SessaoEstudo::class, 'sessao_estudo_id');
    }

    public function studySession(): BelongsTo
    {
        return $this->sessaoEstudo();
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function group(): BelongsTo
    {
        return $this->grupo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function user(): BelongsTo
    {
        return $this->usuario();
    }
}
