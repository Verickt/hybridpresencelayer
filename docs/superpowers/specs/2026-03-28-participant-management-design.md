# Participant Management — Design Spec

## Overview
Organizers can view and delete event participants. Participants can delete their own account. All deletions are hard deletes with DB cascade cleanup.

## Organizer: Participants Page

### Route
- `GET /event/{event:slug}/participants` → `ParticipantController@index`
- `DELETE /event/{event:slug}/participants/{user}` → `ParticipantController@destroy`

### Authorization
- Only the event organizer (`$event->organizer_id === auth()->id()`)
- Organizer cannot delete themselves

### Page: `Event/Participants.vue`
- Linked from the organizer dashboard
- Table columns: name, email, participant type (physical/remote), status, last active
- Delete button per row → confirmation dialog → hard-deletes the user account
- No pagination, search, or filter for MVP

### Controller: `ParticipantController`
- `index(Event $event)`: returns all event participants via Inertia
- `destroy(Event $event, User $user)`: validates organizer, deletes user, redirects back

## Self-Delete: User Profile

### Route
- `DELETE /settings/profile` → existing profile controller or new endpoint

### Behavior
- "Delete my account" button with confirmation dialog
- Calls `$user->delete()` — DB cascades handle cleanup
- Logs user out, redirects to login page

## Data Cleanup
- Relies entirely on DB `cascadeOnDelete` foreign keys already in place on:
  - `event_user`, `connections`, `messages`, `session_check_ins`, `booth_visits`, `user_interest_tag`, etc.

## Out of Scope
- Soft deletes / audit trail
- Bulk delete actions
- Search/filter on participant list
- Pagination
