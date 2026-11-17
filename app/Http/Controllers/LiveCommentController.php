<?php

namespace App\Http\Controllers;

use App\Models\LiveComment;
use App\Models\LiveSession;
use App\Services\LiveAutoResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveCommentController extends Controller
{
    /**
     * Polled by viewers and the host for new comments and live state.
     */
    public function index(Request $request, LiveSession $liveSession): JsonResponse
    {
        $afterId = (int) $request->query('after', 0);

        $comments = $liveSession->comments()
            ->with('user:id,name')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->latest('id')
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'comments' => $comments->map(fn (LiveComment $comment) => $this->present($comment)),
            'status' => $liveSession->status,
            'pinned_product_id' => $liveSession->pinned_product_id,
            'viewers_count' => $liveSession->viewers_count,
        ]);
    }

    public function store(Request $request, LiveSession $liveSession, LiveAutoResponder $responder): JsonResponse
    {
        abort_unless($liveSession->isLive(), 422, 'Live ini belum bermula atau sudah tamat.');

        $validated = $request->validate(['body' => ['required', 'string', 'max:300']]);

        $comment = $liveSession->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $isHost = $request->user()->shop?->id === $liveSession->shop_id;

        if ($liveSession->auto_reply && ! $isHost) {
            dispatch(function () use ($liveSession, $comment, $responder) {
                $answer = $responder->reply($liveSession->fresh('pinnedProduct'), $comment->body);

                if ($answer !== null) {
                    $liveSession->comments()->create(['body' => $answer, 'is_auto_reply' => true]);
                }
            })->afterResponse();
        }

        return response()->json(['comment' => $this->present($comment->load('user:id,name'))], 201);
    }

    /**
     * @return array{id: int, body: string, is_auto_reply: bool, author: string, created_at: string}
     */
    private function present(LiveComment $comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'is_auto_reply' => $comment->is_auto_reply,
            'author' => $comment->is_auto_reply ? 'Pembantu DeBatik' : ($comment->user?->name ?? 'Tetamu'),
            'created_at' => $comment->created_at->toIso8601String(),
        ];
    }
}
