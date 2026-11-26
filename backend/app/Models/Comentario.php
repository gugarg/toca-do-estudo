<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comentario extends Model
{
    use HasFactory;

    protected $table = 'comentarios_sessao_estudo';

    protected $fillable = [
        'sessao_estudo_id',
        'grupo_id',
        'usuario_id',
        'conteudo',
        // Aliases para retrocompatibilidade
        'study_session_id',
        'group_id',
        'user_id',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function getContentAttribute(): ?string
    {
        return $this->attributes['conteudo'] ?? null;
    }

    public function setContentAttribute(?string $value): void
    {
        $this->attributes['conteudo'] = $value;
    }

    public function getUserAttribute(): ?Usuario
    {
        return $this->relationLoaded('usuario') ? $this->getRelation('usuario') : $this->usuario;
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
