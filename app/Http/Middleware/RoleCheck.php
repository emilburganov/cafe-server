<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\ResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    use ResponseTrait;

    public function handle(Request $request, Closure $next, int $roleId): Response
    {
        $token = $request->bearerToken();

        $user = User::query()->firstWhere('api_token', $token);

        if ($user->role_id !== $roleId) {
            return $this->baseError('Forbidden for you', 403);
        }

        return $next($request);
    }
}
