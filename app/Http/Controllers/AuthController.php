<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $token = Str::uuid();
        $user = User::query()
            ->where('login', $request->login)
            ->where('password', $request->password)
            ->first();

        if (!$user) {
            return $this->baseError('Authentication failed', 401);
        }

        $user->update([
            'api_token' => $token,
        ]);

        return response()->json([
            'data' => [
                'user_token' => $token,
            ]
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $user = Auth::user();
        $user->update([
            'api_token' => ''
        ]);

        return response()->json([
            'data' => [
                'message' => 'logout',
            ]
        ]);
    }
}
