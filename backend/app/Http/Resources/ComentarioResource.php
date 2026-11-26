<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComentarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;

        return [
            'id' => $this->id,
            'study_session_id' => $this->study_session_id,
            'group_id' => $this->group_id,
            'group' => $this->whenLoaded('grupo', fn () => $this->grupo ? [
                'id' => $this->grupo->id,
                'name' => $this->grupo->nome,
            ] : null),
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'avatar_url' => $this->user?->avatar_url ? url("storage/{$this->user->avatar_url}") : null,
            ],
            'content' => $this->content,
            'comment' => $this->content,
            'is_author' => $currentUserId === $this->user_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
