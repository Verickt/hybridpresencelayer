<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StatusController extends Controller
{
    public function update(Request $request, Event $event, PresenceService $presenceService): JsonResponse
    {
        abort_unless(
            $request->user()->events()->where('event_id', $event->id)->exists(),
            403
        );

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:available,in_session,at_booth,busy,away'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator, response()->json([
                'message' => 'Die eingegebenen Daten sind ungültig.',
                'errors' => $validator->errors(),
            ], 422));
        }

        $presenceService->updateStatus($request->user(), $event, $validator->validated()['status']);

        return response()->json(['message' => 'Status aktualisiert']);
    }

    public function toggleInvisible(Request $request, Event $event, PresenceService $presenceService): JsonResponse
    {
        $presenceService->toggleInvisible($request->user());

        return response()->json(['message' => 'Sichtbarkeit umgeschaltet']);
    }
}
