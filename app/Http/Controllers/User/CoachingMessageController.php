<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CoachingMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoachingMessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->activePjl($request);
        $messages = CoachingMessage::query()
            ->select(['id', 'recipient_user_id', 'subject', 'body', 'received_at', 'expires_at', 'read_at'])
            ->where('recipient_user_id', $user->getKey())
            ->active()
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('user.coaching-messages.index', [
            'messages' => $messages,
            'unreadCount' => $this->unreadCountFor($user),
        ]);
    }

    public function show(Request $request, string $message): View
    {
        $user = $this->activePjl($request);
        $coachingMessage = $this->activeMessageFor($message, $user);

        return view('user.coaching-messages.show', [
            'message' => $coachingMessage,
            'unreadCount' => $this->unreadCountFor($user),
        ]);
    }

    public function markAsRead(Request $request, string $message): RedirectResponse
    {
        $user = $this->activePjl($request);
        $coachingMessage = $this->activeMessageFor($message, $user);

        if ($coachingMessage->read_at === null) {
            CoachingMessage::query()
                ->whereKey($coachingMessage->getKey())
                ->where('recipient_user_id', $user->getKey())
                ->active()
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return redirect()
            ->route('user.coaching-messages.show', $coachingMessage)
            ->with('success', 'Pesan ditandai sudah dibaca.');
    }

    private function activePjl(Request $request): User
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->hasRole('pjl') && $user->is_active,
            403,
        );

        return $user;
    }

    private function activeMessageFor(string $messageId, User $user): CoachingMessage
    {
        return CoachingMessage::query()
            ->select(['id', 'recipient_user_id', 'subject', 'body', 'received_at', 'expires_at', 'read_at'])
            ->whereKey($messageId)
            ->where('recipient_user_id', $user->getKey())
            ->active()
            ->firstOrFail();
    }

    private function unreadCountFor(User $user): int
    {
        return CoachingMessage::query()
            ->where('recipient_user_id', $user->getKey())
            ->active()
            ->whereNull('read_at')
            ->count();
    }
}
