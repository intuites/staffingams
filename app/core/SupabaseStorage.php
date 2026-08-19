<?php
/**
 * Guzzle wrapper for the Supabase Storage REST API.
 * Server-side only — uses the service_role key, which must never reach the browser.
 */

use GuzzleHttp\Client;

class SupabaseStorage
{
    private Client $http;
    private string $baseUrl;
    private string $serviceKey;

    public function __construct()
    {
        $this->baseUrl    = rtrim(env('SUPABASE_URL', ''), '/');
        $this->serviceKey = env('SUPABASE_SERVICE_KEY', '');
        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'apikey'        => $this->serviceKey,
            ],
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->serviceKey !== '';
    }

    /** Upload a local file. Returns the storage path. */
    public function upload(string $bucket, string $path, string $localFile, ?string $mime = null): string
    {
        $this->http->request('POST', "/storage/v1/object/{$bucket}/{$path}", [
            'headers' => [
                'Content-Type'  => $mime ?: 'application/octet-stream',
                'x-upsert'      => 'true',
            ],
            'body' => fopen($localFile, 'rb'),
        ]);
        return $path;
    }

    /** Create a signed download URL (default 1 hour). */
    public function signedUrl(string $bucket, string $path, int $ttlSeconds = 3600): string
    {
        $res = $this->http->request('POST', "/storage/v1/object/sign/{$bucket}/{$path}", [
            'json' => ['expiresIn' => $ttlSeconds],
        ]);
        $data = json_decode((string) $res->getBody(), true);
        // API returns a relative signed path like /object/sign/bucket/path?token=...
        $signed = $data['signedURL'] ?? $data['signedUrl'] ?? '';
        return $this->baseUrl . '/storage/v1' . $signed;
    }

    public function delete(string $bucket, string $path): void
    {
        $this->http->request('DELETE', "/storage/v1/object/{$bucket}/{$path}");
    }
}
