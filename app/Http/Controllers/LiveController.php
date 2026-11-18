<?php

namespace App\Http\Controllers;

use App\Enums\VoucherScope;
use App\Models\LiveSession;
use App\Services\LiveKitTokenService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LiveController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Live/Index', [
            'lives' => LiveSession::live()
                ->with('shop:id,name,slug,logo')
                ->orderByDesc('viewers_count')
                ->get(['id', 'shop_id', 'title', 'cover', 'viewers_count']),
            'upcoming' => LiveSession::where('status', 'scheduled')
                ->with('shop:id,name,slug')
                ->latest()
                ->take(6)
                ->get(['id', 'shop_id', 'title']),
        ]);
    }

    public function show(Request $request, LiveSession $liveSession, LiveKitTokenService $liveKit, VoucherService $vouchers): Response
    {
        if ($liveSession->isLive()) {
            $liveSession->increment('viewers_count');
        }

        $liveSession->load('shop:id,name,slug,logo,is_verified', 'products:id,name,slug,price,compare_price,stock,image');

        $viewer = $request->user();
        $identity = $viewer ? "viewer-{$viewer->id}" : 'guest-'.Str::lower(Str::random(8));

        return Inertia::render('Live/Show', [
            'session' => $liveSession,
            'vouchers' => $liveSession->isLive()
                ? $vouchers->forDisplay($liveSession->shop, $viewer, [VoucherScope::Live, VoucherScope::Shop])
                : [],
            'video' => $liveSession->isLive() && $liveKit->isConfigured() ? [
                'url' => $liveKit->url(),
                'token' => $liveKit->token($liveSession->roomName(), $identity, $viewer?->name ?? 'Tetamu', canPublish: false),
            ] : null,
        ]);
    }
}
