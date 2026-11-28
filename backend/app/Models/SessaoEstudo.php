<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessaoEstudo extends Model
{
    use HasFactory;

    public const CREATED_AT = 'data_criacao';
    public const UPDATED_AT = 'data_edicao';

    /**
     * Limite máximo de dias corridos permitidos para lançamento de estudos retroativos.
     */
    public const LIMITE_DIAS_RETROATIVO = 7;

    /**
     * Retorna a data mínima permitida (em formato 'Y-m-d') para lançamento de estudo retroativo.
     */
    public static function getDataLimiteRetroativa(): string
    {
        return Carbon::now()->subDays(self::LIMITE_DIAS_RETROATIVO)->toDateString();
    }

    protected $table = 'sessoes_estudo';

    protected $fillable = [
        'usuario_id',
        'data_estudo',
        'disciplina',
        'minutos',
        'questoes',
        'anotacoes',
        'caminho_foto',
        'compartilhar',
        // Aliases para retrocompatibilidade
        'user_id',
        'study_date',
        'subject',
        'minutes',
        'questions',
        'notes',
        'photo_path',
        'share',
    ];

    protected function casts(): array
    {
        return [
            'data_estudo' => 'date:Y-m-d',
            'minutos' => 'integer',
            'questoes' => 'integer',
            'compartilhar' => 'boolean',
        ];
    }

    // --- Accessors & Mutators ---

    public function getUserIdAttribute(): mixed
    {
        return $this->attributes['usuario_id'] ?? null;
    }

    public function setUserIdAttribute(mixed $value): void
    {
        $this->attributes['usuario_id'] = $value;
    }

    public function getStudyDateAttribute(): mixed
    {
        $val = $this->attributes['data_estudo'] ?? null;
        if ($val === null) {
            return null;
        }
        return is_string($val) ? Carbon::parse($val) : $val;
    }

    public function setStudyDateAttribute(mixed $value): void
    {
        $this->attributes['data_estudo'] = $value;
    }

    public function getSubjectAttribute(): ?string
    {
        return $this->attributes['disciplina'] ?? null;
    }

    public function setSubjectAttribute(?string $value): void
    {
        $this->attributes['disciplina'] = $value;
    }

    public function getMinutesAttribute(): int
    {
        return (int) ($this->attributes['minutos'] ?? 0);
    }

    public function setMinutesAttribute(mixed $value): void
    {
        $this->attributes['minutos'] = (int) $value;
    }

    public function getQuestionsAttribute(): int
    {
        return (int) ($this->attributes['questoes'] ?? 0);
    }

    public function setQuestionsAttribute(mixed $value): void
    {
        $this->attributes['questoes'] = (int) $value;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->attributes['anotacoes'] ?? null;
    }

    public function setNotesAttribute(?string $value): void
    {
        $this->attributes['anotacoes'] = $value;
    }

    public function getPhotoPathAttribute(): ?string
    {
        return $this->attributes['caminho_foto'] ?? null;
    }

    public function setPhotoPathAttribute(?string $value): void
    {
        $this->attributes['caminho_foto'] = $value;
    }

    // --- Relacionamentos Relacionais ---

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function user(): BelongsTo
    {
        return $this->usuario();
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'membros_grupo', 'usuario_id', 'grupo_id', 'usuario_id', 'id')
            ->withPivot('papel', 'ingressou_em');
    }

    public function groups(): BelongsToMany
    {
        return $this->grupos();
    }

    public function reacoes(): HasMany
    {
        return $this->hasMany(Reacao::class, 'sessao_estudo_id');
    }

    public function kudos(): HasMany
    {
        return $this->reacoes();
    }

    public function reactions(): HasMany
    {
        return $this->reacoes();
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class, 'sessao_estudo_id');
    }

    public function comments(): HasMany
    {
        return $this->comentarios();
    }

    public function getHoursFormattedAttribute(): string
    {
        return \App\Helpers\TempoHelper::minutosParaTexto((int) ($this->attributes['minutos'] ?? 0));
    }
}
