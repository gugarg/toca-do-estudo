<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessaoEstudoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;

        $reactions = $this->kudos;
        $reactionsCount = $reactions->count();
        $reactionsBreakdown = $reactions->groupBy('reaction_type')->map->count();
        $userReaction = $reactions->firstWhere('user_id', $currentUserId)?->reaction_type;

        // Acesso à sessão não concede acesso às demais comunidades do autor,
        // nem exibe comunidades em que o autor ingressou após a data do estudo.
        $gruposDoLeitor = $request->user()?->grupos()->pluck('grupos.id')->all() ?? [];
        $dataEstudoStr = $this->study_date?->format('Y-m-d');
        $gruposVisiveis = $this->compartilhar
            ? $this->groups
                ->whereIn('id', $gruposDoLeitor)
                ->filter(function ($g) use ($dataEstudoStr) {
                    if (!$dataEstudoStr || !$g->pivot?->ingressou_em) {
                        return true;
                    }
                    return $dataEstudoStr >= \Carbon\Carbon::parse($g->pivot->ingressou_em)->toDateString();
                })
                ->values()
            : collect();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'avatar_url' => $this->user?->avatar_url ? asset('storage/' . ltrim($this->user->avatar_url, '/')) : null,
            ],
            'study_date' => $this->study_date->format('Y-m-d'),
            'subject' => $this->subject,
            'minutes' => $this->minutes,
            'hours_formatted' => $this->hours_formatted,
            'questions' => $this->questions,
            'notes' => $this->notes,
            'photo_url' => $this->photo_path ? asset('storage/' . ltrim($this->photo_path, '/')) : null,
            'groups' => $gruposVisiveis->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
            ]),
            'kudos_count' => $reactionsCount,
            'user_has_kudo' => $userReaction !== null,
            'kudos_breakdown' => $reactionsBreakdown,
            'reactions_count' => $reactionsCount,
            'user_reaction' => $userReaction,
            'reactions_breakdown' => $reactionsBreakdown,
            'comments_count' => $this->comments_count ?? ($this->relationLoaded('comments') ? $this->comments->count() : $this->comments()->count()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
