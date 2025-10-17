<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CdnService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('cdn');
    }

    /**
     * Purge CDN cache for specific URLs
     *
     * @param array $urls
     * @return bool
     */
    public function purgeCache(array $urls): bool
    {
        if (!$this->config['purge']['enabled']) {
            return true;
        }

        $success = true;

        // CloudFlare
        if ($this->config['providers']['cloudflare']['enabled']) {
            $success = $this->purgeCloudFlare($urls) && $success;
        }

        // AWS CloudFront
        if ($this->config['providers']['aws_cloudfront']['enabled']) {
            $success = $this->purgeAwsCloudFront($urls) && $success;
        }

        return $success;
    }

    /**
     * Purge CloudFlare cache
     *
     * @param array $urls
     * @return bool
     */
    protected function purgeCloudFlare(array $urls): bool
    {
        $config = $this->config['providers']['cloudflare'];
        
        if (empty($config['zone_id']) || empty($config['api_token'])) {
            Log::warning('CloudFlare CDN not configured properly');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_token'],
                'Content-Type' => 'application/json',
            ])->post(str_replace('{zone_id}', $config['zone_id'], $config['purge_url']), [
                'purge_everything' => false,
                'files' => $urls,
            ]);

            if ($response->successful()) {
                Log::info('CloudFlare cache purged successfully', ['urls' => $urls]);
                return true;
            }

            Log::error('CloudFlare cache purge failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('CloudFlare cache purge error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Purge AWS CloudFront cache
     *
     * @param array $urls
     * @return bool
     */
    protected function purgeAwsCloudFront(array $urls): bool
    {
        $config = $this->config['providers']['aws_cloudfront'];
        
        if (empty($config['distribution_id']) || empty($config['access_key'])) {
            Log::warning('AWS CloudFront CDN not configured properly');
            return false;
        }

        try {
            // This would require AWS SDK implementation
            // For now, just log the action
            Log::info('AWS CloudFront cache purge requested', [
                'distribution_id' => $config['distribution_id'],
                'urls' => $urls,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('AWS CloudFront cache purge error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get CDN URL for a given path
     *
     * @param string $path
     * @return string
     */
    public function getCdnUrl(string $path): string
    {
        $cdnUrl = env('CDN_URL');
        
        if (!$cdnUrl) {
            return url($path);
        }

        return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Check if CDN is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'];
    }
}
