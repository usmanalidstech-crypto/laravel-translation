<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TokenAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TokenAuthTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TokenAuth();
    }

    public function test_middleware_accepts_valid_token()
    {
        $token = 'valid-test-token';
        $hash = hash_hmac('sha256', $token, config('app.key'));
        
        DB::table('api_tokens')->insert([
            'name' => 'test',
            'token_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $request = Request::create('/api/keys', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_rejects_invalid_token()
    {
        $request = Request::create('/api/keys', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token'
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $data['message']);
    }

    public function test_middleware_rejects_missing_token()
    {
        $request = Request::create('/api/keys', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $data['message']);
    }

    public function test_middleware_rejects_malformed_authorization_header()
    {
        $request = Request::create('/api/keys', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'InvalidFormat token'
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }
}
