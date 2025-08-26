<?php

namespace App;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

trait HandlesExceptions
{
    protected function handleException(Exception $e, string $message, int $statusCode = 400): JsonResponse
    {
        $isProduction = config('app.env') === 'production';
        $debugInfo = [];

        if (!$isProduction) {
            $debugInfo = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            if ($e instanceof RequestException && $e->response) {
                $debugInfo['response'] = json_decode($e->response->getBody(), true);
            }
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'debug' => $isProduction ? null : $debugInfo,
        ], $statusCode);
    }
}
