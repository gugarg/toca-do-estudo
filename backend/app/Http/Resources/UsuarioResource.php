<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stats = $this->statsCache;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url ? url("storage/{$this->avatar_url}") : null,
            'bio' => $this->bio,
            'timezone' => $this->timezone,
            'stats' => [
                'total_active_days' => $stats?->total_active_days ?? 0,
                'total_hours' => $stats?->total_hours ?? 0.0,
                'total_questions' => $stats?->total_questions ?? 0,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
