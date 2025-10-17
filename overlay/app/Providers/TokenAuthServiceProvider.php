<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider; use Illuminate\Routing\Router;
use App\Http\Middleware\TokenAuth;
use App\Http\Middleware\CdnHeaders;
class TokenAuthServiceProvider extends ServiceProvider {
  public function boot(Router $router): void { 
    $router->aliasMiddleware('token', TokenAuth::class);
    $router->aliasMiddleware('cdn', CdnHeaders::class);
  }
}