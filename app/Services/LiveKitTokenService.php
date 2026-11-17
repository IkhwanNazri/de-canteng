<?php

namespace App\Services;

/**
 * Issues LiveKit access tokens (HS256 JWT) without an extra SDK.
 *
 * @see https://docs.livekit.io/home/get-started/authentication/
 */
class LiveKitTokenService
{
    public function isConfigured(): bool
    {
        return filled(config('services.livekit.url'))
            && filled(config('services.livekit.key'))
            && filled(config('services.livekit.secret'));
    }

    public function url(): ?string
    {
        return config('services.livekit.url');
    }

    public function token(string $room, string $identity, string $name, bool $canPublish): string
    {
        $now = time();

        $payload = [
            'iss' => config('services.livekit.key'),
            'sub' => $identity,
            'name' => $name,
            'nbf' => $now,
            'exp' => $now + 6 * 3600,
            'video' => [
                'room' => $room,
                'roomJoin' => true,
                'canPublish' => $canPublish,
                'canSubscribe' => true,
                'canPublishData' => true,
            ],
        ];

        $segments = [
            $this->base64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode($payload)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), (string) config('services.livekit.secret'), true);
        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
