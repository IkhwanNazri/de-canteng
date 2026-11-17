<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShopFollowController extends Controller
{
    public function toggle(Request $request, Shop $shop): RedirectResponse
    {
        abort_if($shop->user_id === $request->user()->id, 422, 'Anda tidak boleh ikut kedai sendiri.');

        $changes = $request->user()->followedShops()->toggle($shop->id);

        if ($changes['attached']) {
            $shop->increment('followers_count');
        } elseif ($changes['detached'] && $shop->followers_count > 0) {
            $shop->decrement('followers_count');
        }

        return back();
    }
}
