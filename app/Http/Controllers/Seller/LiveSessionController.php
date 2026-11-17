<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreLiveSessionRequest;
use App\Models\LiveSession;
use App\Services\LiveKitTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LiveSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $shop = $request->user()->shop;

        return Inertia::render('Seller/Live/Index', [
            'sessions' => $shop->liveSessions()->withCount('products')->latest()->get(),
            'products' => $shop->products()->orderBy('name')->get(['id', 'name', 'price', 'stock']),
        ]);
    }

    public function store(StoreLiveSessionRequest $request): RedirectResponse
    {
        $session = $request->user()->shop->liveSessions()->create([
            'title' => $request->validated('title'),
            'status' => 'scheduled',
        ]);

        $session->products()->sync($request->validated('product_ids'));
        $session->update(['pinned_product_id' => $request->validated('product_ids')[0]]);

        return redirect()->route('seller.live.show', $session);
    }

    public function show(Request $request, LiveSession $liveSession, LiveKitTokenService $liveKit): Response
    {
        Gate::authorize('host', $liveSession);

        $liveSession->load('products:id,name,price,compare_price,stock,image');

        return Inertia::render('Seller/Live/Studio', [
            'session' => $liveSession,
            'video' => $liveKit->isConfigured() ? [
                'url' => $liveKit->url(),
                'token' => $liveKit->token($liveSession->roomName(), "host-{$request->user()->id}", $request->user()->name, canPublish: true),
            ] : null,
        ]);
    }

    public function start(LiveSession $liveSession): RedirectResponse
    {
        Gate::authorize('host', $liveSession);

        $liveSession->update(['status' => 'live', 'started_at' => now(), 'ended_at' => null]);

        return back()->with('success', 'Anda sedang LIVE!');
    }

    public function end(LiveSession $liveSession): RedirectResponse
    {
        Gate::authorize('host', $liveSession);

        $liveSession->update(['status' => 'ended', 'ended_at' => now()]);

        return back()->with('success', 'Live tamat.');
    }

    public function update(Request $request, LiveSession $liveSession): RedirectResponse
    {
        Gate::authorize('host', $liveSession);

        $validated = $request->validate([
            'pinned_product_id' => ['sometimes', 'nullable', Rule::in($liveSession->products()->pluck('products.id'))],
            'auto_reply' => ['sometimes', 'boolean'],
        ]);

        $liveSession->update($validated);

        return back();
    }
}
