<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    public function validationError($errors): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 422,
                'message' => 'Validation error',
                'errors' => $errors
            ]
        ], 422);
    }

    public function baseError(string $message, int $code): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ], $code);
    }
}
