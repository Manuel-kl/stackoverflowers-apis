<?php

namespace App\Services;

use App\Models\GoogleAuthToken;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;

class GoogleMailService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setClientId(config('services.gmail.google_client_id'));
        $this->client->setClientSecret(config('services.gmail.google_client_secret'));
        $this->client->setRedirectUri(config('services.gmail.google_redirect_uri'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->addScope([
            Gmail::GMAIL_SEND,
        ]);
    }

    public function checkValidity($token)
    {
        if ($this->client->isAccessTokenExpired()) {
            return $this->refreshToken($token);
        }

        return 'active';
    }

    public function createAuthUrl(array $scope = [], array $queryParams = [])
    {
        $defaultScopes = [
            Gmail::GMAIL_SEND,
        ];

        return $this->client->createAuthUrl($scope ?: $defaultScopes, $queryParams);
    }

    public function handleCallback($code)
    {
        if (!$code) {
            throw new \Exception('The code parameter is missing.');
        }

        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \Exception('Error fetching access token: '.$token['error']);
        }

        $this->client->setAccessToken($token);

        $refreshToken = $token['refresh_token'] ?? null;

        if (!$refreshToken) {
            throw new \Exception('Refresh token is null. Try revoking access and re-authenticating.');
        }

        $accessToken = $token['access_token'];
        $expiresIn = $token['expires_in'];
        $projectId = config('services.gmail.google_project_id');

        GoogleAuthToken::updateOrCreate(
            ['project_id' => $projectId],
            [
                'refresh_token' => base64_encode(encrypt($refreshToken)),
                'access_token' => base64_encode(encrypt($accessToken)),
                'expires_in' => $expiresIn,
            ]
        );

        return true;
    }

    public function sendEmail(array $to, string $subject, string $messageText, array $attachments = [])
    {
        $projectId = config('services.gmail.google_project_id');
        $tokenData = GoogleAuthToken::where('project_id', $projectId)->first();

        if (!$tokenData) {
            throw new \Exception('No OAuth token found for this project.');
        }

        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken($tokenData);
        }

        $boundary = uniqid('boundary_', true);
        $rawMessageString = "MIME-Version: 1.0\r\n";
        $rawMessageString .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $rawMessageString .= 'To: '.implode(', ', $to)."\r\n";
        $rawMessageString .= "Subject: {$subject}\r\n\r\n";

        // HTML body
        $rawMessageString .= "--$boundary\r\n";
        $rawMessageString .= "Content-Type: text/html; charset=UTF-8\r\n";
        $rawMessageString .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $rawMessageString .= chunk_split(base64_encode($messageText))."\r\n";

        // Add attachments
        foreach ($attachments as $attachment) {
            $filePath = $attachment['path'] ?? null;
            if (!$filePath || !file_exists($filePath)) {
                continue;
            }

            $fileData = file_get_contents($filePath);
            $base64File = chunk_split(base64_encode($fileData));

            $rawMessageString .= "--$boundary\r\n";
            $rawMessageString .= "Content-Type: {$attachment['mime']}; name=\"{$attachment['filename']}\"\r\n";
            $rawMessageString .= "Content-Disposition: attachment; filename=\"{$attachment['filename']}\"\r\n";
            $rawMessageString .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $rawMessageString .= $base64File."\r\n";
        }

        $rawMessageString .= "--$boundary--";

        $rawMessage = strtr(base64_encode($rawMessageString), '+/', '-_');
        $rawMessage = rtrim($rawMessage, '=');

        $message = new Message;
        $message->setRaw($rawMessage);

        $service = new Gmail($this->client);

        return $service->users_messages->send('me', $message);
    }

    private function refreshToken($token)
    {
        $refreshToken = decrypt(base64_decode($token->refresh_token));

        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $newAccessToken = $this->client->getAccessToken() ?? [];

        if (empty($newAccessToken) || !isset($newAccessToken['access_token'])) {
            throw new \Exception('Failed to fetch new access token. Check your refresh token.');
        }

        $newRefreshToken = $this->client->getRefreshToken() ?? $refreshToken;
        $this->client->setAccessToken(['access_token' => $newAccessToken['access_token']]);

        $token->update([
            'refresh_token' => base64_encode(encrypt($newRefreshToken)),
            'access_token' => base64_encode(encrypt($newAccessToken['access_token'])),
            'expires_in' => $newAccessToken['expires_in'],
        ]);

        return 'active';
    }
}
