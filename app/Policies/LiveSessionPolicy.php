<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\Models\User;

class LiveSessionPolicy
{
    /**
     * Only the shop owner can run (start, pin, end) a live session.
     */
    public function host(User $user, LiveSession $liveSession): bool
    {
        return $user->shop?->id === $liveSession->shop_id;
    }
}
