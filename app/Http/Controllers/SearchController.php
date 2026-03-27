<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        abort_unless($event->participants()->where('users.id', $user->id)->exists(), 403);

        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $participants = $event->participants()
            ->where('users.is_invisible', false)
            ->where('users.id', '!=', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('users.name', 'like', "%{$query}%")
                    ->orWhere('users.company', 'like', "%{$query}%")
                    ->orWhereHas('interestTags', function ($tagQuery) use ($query) {
                        $tagQuery->where('name', 'like', "%{$query}%");
                    });
            })
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'company' => $p->company,
                'participant_type' => $p->pivot->participant_type,
                'status' => $p->pivot->status,
            ]);

        return response()->json(['data' => $participants]);
    }
}
