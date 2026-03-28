<?php

namespace App\Http\Controllers;

use App\Events\BoothAnnouncement;
use App\Models\Booth;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoothStaffController extends Controller
{
    public function leads(Request $request, Event $event, Booth $booth): JsonResponse
    {
        $this->authorizeStaff($request, $booth);

        $visits = $booth->visits()
            ->where('is_anonymous', false)
            ->with('user:id,name,email,company,role_title')
            ->orderByDesc('entered_at')
            ->get();

        $physicalCount = $visits->where('participant_type', 'physical')->count();
        $remoteCount = $visits->where('participant_type', 'remote')->count();

        $leads = $visits->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->user->name,
            'email' => $v->user->email,
            'company' => $v->user->company,
            'role_title' => $v->user->role_title,
            'participant_type' => $v->participant_type,
            'entered_at' => $v->entered_at->toISOString(),
            'duration_minutes' => $v->durationInMinutes(),
            'from_session_id' => $v->from_session_id,
        ]);

        return response()->json([
            'data' => [
                'total_visitors' => $visits->count(),
                'physical_count' => $physicalCount,
                'remote_count' => $remoteCount,
                'leads' => $leads,
            ],
        ]);
    }

    public function announce(Request $request, Event $event, Booth $booth): JsonResponse
    {
        $this->authorizeStaff($request, $booth);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:280'],
        ]);

        BoothAnnouncement::dispatch($booth, $validated['message']);

        return response()->json(['message' => 'Ankündigung gesendet']);
    }

    public function exportLeads(Request $request, Event $event, Booth $booth): StreamedResponse
    {
        $this->authorizeStaff($request, $booth);

        $visits = $booth->visits()
            ->where('is_anonymous', false)
            ->with('user:id,name,email,company,role_title')
            ->orderByDesc('entered_at')
            ->get();

        return response()->streamDownload(function () use ($visits) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'E-Mail', 'Unternehmen', 'Rolle', 'Typ', 'Dauer (Min.)', 'Besucht am']);

            foreach ($visits as $visit) {
                fputcsv($handle, [
                    $visit->user->name,
                    $visit->user->email,
                    $visit->user->company,
                    $visit->user->role_title,
                    $visit->participant_type,
                    $visit->durationInMinutes(),
                    $visit->entered_at->toISOString(),
                ]);
            }

            fclose($handle);
        }, "{$booth->company}-leads.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function authorizeStaff(Request $request, Booth $booth): void
    {
        abort_unless($booth->staff()->where('user_id', $request->user()->id)->exists(), 403);
    }
}
