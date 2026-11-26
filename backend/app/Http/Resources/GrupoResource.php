<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;
        $userMembership = $this->members->firstWhere('id', $currentUserId);

        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
            'cover_url' => $this->cover_url ? url("storage/{$this->cover_url}") : null,
            'invite_code' => $this->invite_code,
            'default_metric' => $this->default_metric,
            'metrica_padrao' => $this->metrica_padrao,
            'pontos_hora' => $this->pontos_hora,
            'pontos_questao' => $this->pontos_questao,
            'points_per_hour' => $this->points_per_hour,
            'points_per_question' => $this->points_per_question,
            'members_count' => $this->members->count(),
            'current_user_role' => $userMembership?->pivot?->role ?? null,
            'is_owner' => $this->owner_id === $currentUserId,
            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'avatar_url' => $m->avatar_url ? url("storage/{$m->avatar_url}") : null,
                    'role' => $m->pivot->role,
                    'joined_at' => $m->pivot->joined_at,
                ]);
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
