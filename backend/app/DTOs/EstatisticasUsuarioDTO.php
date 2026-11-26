<?php

declare(strict_types=1);

namespace App\DTOs;

use ArrayAccess;
use Carbon\Carbon;
use JsonSerializable;

class EstatisticasUsuarioDTO implements JsonSerializable, ArrayAccess
{
    public function __construct(
        public readonly int $usuario_id,
        public readonly int $total_dias_ativos,
        public readonly int $total_minutos,
        public readonly int $total_questoes,
        public readonly ?Carbon $calculado_em = null,
    ) {}

    public function getTotalHoursAttribute(): float
    {
        return round($this->total_minutos / 60.0, 1);
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'total_hours' => $this->getTotalHoursAttribute(),
            'total_active_days' => $this->total_dias_ativos,
            'total_minutes' => $this->total_minutos,
            'total_questions' => $this->total_questoes,
            'user_id' => $this->usuario_id,
            'last_calculated_at' => $this->calculado_em,
            default => null,
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, [
            'total_hours',
            'total_active_days',
            'total_minutes',
            'total_questions',
            'user_id',
            'last_calculated_at',
            'usuario_id',
            'total_dias_ativos',
            'total_minutos',
            'total_questoes',
            'calculado_em',
        ], true);
    }

    public function toArray(): array
    {
        return [
            'usuario_id' => $this->usuario_id,
            'user_id' => $this->usuario_id,
            'total_dias_ativos' => $this->total_dias_ativos,
            'total_active_days' => $this->total_dias_ativos,
            'total_minutos' => $this->total_minutos,
            'total_minutes' => $this->total_minutos,
            'total_questoes' => $this->total_questoes,
            'total_questions' => $this->total_questoes,
            'total_hours' => $this->getTotalHoursAttribute(),
            'calculado_em' => $this->calculado_em?->toIso8601String(),
            'last_calculated_at' => $this->calculado_em?->toIso8601String(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // --- ArrayAccess ---
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Imutável
    }

    public function offsetUnset(mixed $offset): void
    {
        // Imutável
    }
}
