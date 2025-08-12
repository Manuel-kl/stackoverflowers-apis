<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyUserRequest;
use App\Models\UserVerificationCode;
use App\Services\SmsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function sendVerificationCode(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'type' => 'required|in:sms',
        ]);

        $type = $request->input('type');

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'User account already verified, login instead',
                'success' => false,
            ], 409);
        }

        $code = rand(100000, 999999);
        $expiresAt = now()->addMinutes(30);

        UserVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => $expiresAt,
            'type' => $type,
        ]);

        try {
            if ($user->phone_number) {
                $this->smsService->sendSmsCode([
                    'phone_number' => $user->phone_number,
                    'message' => "Your verification code is: $code\nThis code will expire in 30 minutes.",
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($type).' failed to send',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent successfully',
        ]);
    }

    public function verifyCode(VerifyUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'User account already verified',
                'success' => false,
            ], 409);
        }

        $verification = UserVerificationCode::where('user_id', $user->id)
            ->where('code', $data['code'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response()->json([
                'message' => 'Invalid code',
                'success' => false,
            ], 400);
        }

        $verificationCodes = $user->userVerifications;

        foreach ($verificationCodes as $code) {
            $code->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }

        $user->update([
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'User account verified',
            'success' => true,
        ]);
    }
}
