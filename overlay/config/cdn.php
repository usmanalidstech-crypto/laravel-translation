<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for CDN integration and caching strategies
    |
    */

    'enabled' => env('CDN_ENABLED', true),

    'providers' => [
        'cloudflare' => [
            'enabled' => env('CDN_CLOUDFLARE_ENABLED', false),
            'zone_id' => env('CDN_CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CDN_CLOUDFLARE_API_TOKEN'),
            'purge_url' => 'https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache',
        ],
        'aws_cloudfront' => [
            'enabled' => env('CDN_AWS_CLOUDFRONT_ENABLED', false),
            'distribution_id' => env('CDN_AWS_CLOUDFRONT_DISTRIBUTION_ID'),
            'access_key' => env('CDN_AWS_ACCESS_KEY'),
            'secret_key' => env('CDN_AWS_SECRET_KEY'),
            'region' => env('CDN_AWS_REGION', 'us-east-1'),
        ],
    ],

    'cache' => [
        'export_ttl' => env('CDN_EXPORT_TTL', 300), // 5 minutes
        'export_smaxage' => env('CDN_EXPORT_SMAXAGE', 600), // 10 minutes for CDN
        'api_ttl' => env('CDN_API_TTL', 0), // No cache for API endpoints
    ],

    'headers' => [
        'security' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ],
        'cors' => [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
        ],
    ],

    'purge' => [
        'enabled' => env('CDN_PURGE_ENABLED', false),
        'endpoints' => [
            'export' => true,
            'keys' => false, // Don't purge CRUD endpoints
        ],
    ],
];
