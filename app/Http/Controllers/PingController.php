<?php

namespace App\Http\Controllers;

use App\Exceptions\BlockedUserException;
use App\Exceptions\CooldownException;
use App\Exceptions\DuplicatePingException;
use App\Exceptions\RateLimitExceededException;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;
use App\Services\PingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function store(Request $request, Event $event, User $user, PingService $pingService): JsonResponse
    {
        try {
            $ping = $pingService->send($request->user(), $user, $event);

            return response()->json([
                'message' => $ping->status === 'matched' ? "It's a match!" : 'Ping sent!',
                'status' => $ping->status,
            ]);
        } catch (RateLimitExceededException) {
            return response()->json(['message' => 'Too many pings. Try again later.'], 429);
        } catch (DuplicatePingException) {
            return response()->json(['message' => 'Already pinged this person.'], 409);
        } catch (CooldownException) {
            return response()->json(['message' => 'This person has not responded to your pings.'], 403);
        } catch (BlockedUserException) {
            return response()->json(['message' => 'Unable to ping this user.'], 403);
        } catch (\InvalidArgumentException) {
            return response()->json(['message' => 'Invalid ping target.'], 422);
        }
    }

    public function ignore(Request $request, Event $event, Ping $ping): JsonResponse
    {
        abort_unless($ping->receiver_id === $request->user()->id, 403);

        $ping->update(['status' => 'ignored']);

        return response()->json(['message' => 'Ping ignored']);
    }
}
