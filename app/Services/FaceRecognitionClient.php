<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FaceRecognitionClient
{
    /**
     * Extract a face embedding from an image at the given absolute path.
     *
     * Returns null when the image has no face, when the service is unreachable,
     * or when the response payload is malformed. Callers should treat a null
     * result as "no embedding available" rather than as a hard failure.
     *
     * @return array{
     *     embedding: list<float>,
     *     embedding_dim: int,
     *     bbox: array<string, float>,
     *     det_score: float,
     *     face_count: int,
     * }|null
     */
    public function embed(string $absolutePath): ?array
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            Log::warning('Face embedding skipped: file not readable.', [
                'path' => $absolutePath,
            ]);

            return null;
        }

        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            Log::warning('Face embedding skipped: could not read file contents.', [
                'path' => $absolutePath,
            ]);

            return null;
        }

        try {
            $response = $this->request()
                ->attach('file', $contents, basename($absolutePath))
                ->post('/v1/embed');
        } catch (Throwable $exception) {
            Log::warning('Face embedding request failed.', [
                'path' => $absolutePath,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Face embedding response was unsuccessful.', [
                'path' => $absolutePath,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $this->normalize($response->json());
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) config('services.face.url'))
            ->timeout((int) config('services.face.timeout', 15))
            ->acceptJson();

        $apiKey = config('services.face.key');

        if (is_string($apiKey) && $apiKey !== '') {
            $request = $request->withHeaders(['X-API-Key' => $apiKey]);
        }

        return $request;
    }

    /**
     * @return array{
     *     embedding: list<float>,
     *     embedding_dim: int,
     *     bbox: array<string, float>,
     *     det_score: float,
     *     face_count: int,
     * }|null
     */
    private function normalize(mixed $payload): ?array
    {
        if (! is_array($payload) || ! isset($payload['embedding'], $payload['embedding_dim'])) {
            return null;
        }

        return [
            'embedding' => array_values(array_map('floatval', (array) $payload['embedding'])),
            'embedding_dim' => (int) $payload['embedding_dim'],
            'bbox' => (array) ($payload['bbox'] ?? []),
            'det_score' => (float) ($payload['det_score'] ?? 0.0),
            'face_count' => (int) ($payload['face_count'] ?? 0),
        ];
    }
}
