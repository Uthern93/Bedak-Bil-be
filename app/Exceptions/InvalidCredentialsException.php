<?php

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidCredentialsException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
            'errors' => ['email' => trans('auth.failed')]
        ], 422);
    }
}
