<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineAuthService
{
    /**
     * LINE IDトークンをLINE検証APIに問い合わせて検証し、正当なプロフィール情報を取得する
     *
     * @return array{line_user_id: string, line_display_name: ?string, line_picture_url: ?string}|null
     */
    public function verifyIdToken(string $idToken): ?array
    {
        $channelId = config('services.line.channel_id');
        if (empty($channelId)) {
            $liffId = config('services.line.liff_id');
            if (! empty($liffId) && str_contains($liffId, '-')) {
                $channelId = explode('-', $liffId)[0];
            }
        }

        if (empty($channelId)) {
            Log::error('[LINE Auth] LINE_CHANNEL_ID is not configured.');

            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://api.line.me/oauth2/v2.1/verify', [
                    'id_token' => $idToken,
                    'client_id' => $channelId,
                ]);

            if (! $response->successful()) {
                Log::warning('[LINE Auth] Token verification failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            $payload = $response->json();

            // 対象チャネル向けの発行トークンであることを再確認
            if (($payload['aud'] ?? null) !== (string) $channelId) {
                Log::warning('[LINE Auth] Token audience mismatch', [
                    'expected' => $channelId,
                    'actual' => $payload['aud'] ?? null,
                ]);

                return null;
            }

            return [
                'line_user_id' => $payload['sub'],
                'line_display_name' => $payload['name'] ?? null,
                'line_picture_url' => $payload['picture'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('[LINE Auth] Exception during verifyIdToken: '.$e->getMessage());

            return null;
        }
    }
}
