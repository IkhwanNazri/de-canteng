<?php

namespace App\Services;

use App\Models\LiveSession;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Answers common viewer questions during a live session.
 *
 * Uses Ollama (Qwen) when OLLAMA_URL is set, otherwise keyword rules built
 * from the session's product data. Returns null when it has nothing reliable
 * to say, so the seller can answer instead.
 */
class LiveAutoResponder
{
    public function reply(LiveSession $session, string $question): ?string
    {
        $product = $this->productInFocus($session);

        if (! $product) {
            return null;
        }

        if (filled(config('services.ollama.url'))) {
            $answer = $this->askOllama($product, $question);

            if ($answer !== null) {
                return $answer;
            }
        }

        return $this->answerFromRules($product, $question);
    }

    private function productInFocus(LiveSession $session): ?Product
    {
        return $session->pinnedProduct ?? $session->products()->first();
    }

    private function answerFromRules(Product $product, string $question): ?string
    {
        $text = Str::lower($question);
        $name = $product->name;

        return match (true) {
            Str::contains($text, ['harga', 'berapa', 'price', 'rm']) => $this->priceAnswer($product),
            Str::contains($text, ['stok', 'stock', 'ada lagi', 'habis', 'available', 'tinggal']) => $product->stock > 0
                ? "Stok {$name} tinggal {$product->stock} helai. Cepat tekan Beli!"
                : "Maaf, {$name} dah habis stok.",
            Str::contains($text, ['pos', 'penghantaran', 'shipping', 'hantar', 'courier']) => 'Penghantaran ke seluruh Malaysia. Kos pos dikira masa checkout.',
            Str::contains($text, ['cara beli', 'macam mana beli', 'nak beli', 'order', 'checkout']) => "Tekan butang Beli pada kad {$name} di skrin.",
            Str::contains($text, ['saiz', 'size', 'ukuran', 'warna', 'color', 'kain', 'material']) => "Soalan bagus! Penjual akan jawab tentang {$name} sekejap lagi.",
            default => null,
        };
    }

    private function priceAnswer(Product $product): string
    {
        $price = 'RM'.number_format($product->price, 2);

        if ($product->compare_price && $product->compare_price > $product->price) {
            return "Harga {$product->name} sekarang {$price} (asal RM".number_format($product->compare_price, 2).').';
        }

        return "Harga {$product->name}: {$price}.";
    }

    private function askOllama(Product $product, string $question): ?string
    {
        $facts = json_encode([
            'nama' => $product->name,
            'harga_rm' => $product->price,
            'harga_asal_rm' => $product->compare_price,
            'stok' => $product->stock,
            'penerangan' => $product->description,
        ], JSON_UNESCAPED_UNICODE);

        try {
            $response = Http::timeout(20)->post(rtrim(config('services.ollama.url'), '/').'/api/chat', [
                'model' => config('services.ollama.model'),
                'stream' => false,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda pembantu live shopping kedai batik DeBatik. Jawab soalan penonton dalam Bahasa Melayu santai, maksimum 2 ayat. '
                            .'Guna HANYA fakta produk ini: '.$facts.'. Jika jawapan tiada dalam fakta, kata penjual akan jawab sekejap lagi.',
                    ],
                    ['role' => 'user', 'content' => $question],
                ],
            ]);

            $answer = trim((string) $response->json('message.content'));

            return $response->successful() && $answer !== '' ? Str::limit($answer, 480) : null;
        } catch (Throwable $exception) {
            Log::warning('Ollama auto-reply failed', ['message' => $exception->getMessage()]);

            return null;
        }
    }
}
