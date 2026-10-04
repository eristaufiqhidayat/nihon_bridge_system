<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\MessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesanController extends Controller
{
    public function __construct(private MessageService $messages)
    {
    }

    public function index(Request $request, ?Conversation $conversation = null): View
    {
        $user = $request->user();
        if ($conversation) {
            $this->authorizeConversation($request, $conversation);
            $this->messages->markRead($conversation, $user);
            $conversation->load('messages.user', 'participants', 'classroom');
        }

        return view('shared.pesan', [
            'conversations' => $this->messages->conversationsFor($user),
            'active' => $conversation,
        ]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeConversation($request, $conversation);
        $body = $request->validate(['body' => ['required', 'string', 'max:2000']])['body'];
        $this->messages->send($conversation->load('participants'), $request->user(), trim($body));

        return redirect()->route('pesan.index', $conversation);
    }

    private function authorizeConversation(Request $request, Conversation $c): void
    {
        abort_unless($c->participants()->where('users.id', $request->user()->id)->exists(), 404);
    }
}
