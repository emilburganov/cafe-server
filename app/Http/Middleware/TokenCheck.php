<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TokenCheck
{
    use ResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return $this->baseError('Login failed', 403);
        }

        $user = User::query()->firstWhere('api_token', $token);

        if (!$user) {
            return $this->baseError('Login failed', 403);
        }

        Auth::login($user);

        return $next($request);
    }
}
