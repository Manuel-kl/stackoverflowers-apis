<?php

namespace App\Http\Controllers;

use App\Models\GoogleAuthToken;
use App\Services\GoogleMailService;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MailController extends Controller
{
    protected $mailService;

    public function __construct(GoogleMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function index()
    {
        $token = GoogleAuthToken::where('project_id', config('services.gmail.google_project_id'))->first();

        if (!$token) {
            return view('mail.connect-google', [
                'status' => 'inactive',
                'token' => null,
            ]);
        }

        $status = $this->mailService->checkValidity($token);

        if ($status === 'active') {
            return view('mail.index', [
                'status' => $status,
                'token' => $token,
            ]);
        }

        // For expired or any non-active state show connect screen
        return view('mail.connect-google', [
            'status' => $status,
            'token' => $token,
        ]);
    }

    public function connectGoogle()
    {
        $token = GoogleAuthToken::where('project_id', config('services.gmail.google_project_id'))->first();

        if ($token) {
            $status = $this->mailService->checkValidity($token);
            if ($status === 'active') {
                return redirect('/mail')->with('error', 'Google account is already connected.');
            }
        }

        $state = Str::uuid()->toString();
        cache()->put('google_oauth_'.$state, true, now()->addMinutes(10));

        $authUrl = $this->mailService->createAuthUrl(
            [Gmail::GMAIL_SEND],
            ['state' => $state]
        );

        return redirect($authUrl);
    }

    public function handleCallback(Request $request)
    {
        $state = $request->query('state');

        if (!$state || !cache()->has('google_oauth_'.$state)) {
            return response()->json([
                'message' => 'We tried... but we couldn’t validate your request. It’s probably our fault. Maybe the session expired or something went wrong. Please don’t give up on us. Just try again, we really need this to work.',
            ], 400);
        }

        cache()->forget('google_oauth_'.$state);
        $request->session()->forget(['isCodeSent', 'currentMail', 'userVerified']);

        if (!$request->query('code')) {
            return response()->json([
                'success' => false,
                'message' => 'No authorization code present',
            ], 400);
        }

        try {
            $this->mailService->handleCallback($request->query('code'));

            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
