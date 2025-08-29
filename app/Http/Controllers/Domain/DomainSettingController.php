<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DomainSettingController extends Controller
{
    public function getEppCode(Request $request)
    {
        $domainId = $request->input('domainid');

        if (!$domainId) {
            return response()->json([
                'success' => false,
                'message' => 'Domain ID is required',
            ], 422);
        }

        $identifier = config('services.whmcs.identifier');
        $secret = config('services.whmcs.secret');
        $whmcsUrl = rtrim(config('services.whmcs.url'), '/').'/includes/api.php';

        $eppCodeRes = Http::asForm()
            ->timeout(300)
            ->post($whmcsUrl, [
                'action' => 'DomainRequestEPP',
                'identifier' => $identifier,
                'secret' => $secret,
                'domainid' => $domainId,
                'responsetype' => 'json',
            ]);

        if (!$eppCodeRes->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to communicate with WHMCS API',
                'data' => $eppCodeRes->body(),
            ], 500);
        }

        $response = $eppCodeRes->json();

        if (($response['result'] ?? '') !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request EPP code',
                'data' => $response,
            ], 400);
        }

        $eppCode = $response['eppcode'] ?? null;

        if ($eppCode) {
            $eppCode = html_entity_decode($eppCode);

            return response()->json([
                'success' => true,
                'message' => 'EPP code retrieved successfully',
                'data' => $eppCode,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'EPP code request submitted successfully. Check your email for the EPP code.',
            'data' => null,
        ]);
    }

    public function updateLockingStatus(Request $request)
    {
        $domainId = $request->input('domainid');
        $lockStatus = $request->input('lockstatus');

        if (!$domainId) {
            return response()->json([
                'success' => false,
                'message' => 'Domain ID is required',
            ], 422);
        }

        $identifier = config('services.whmcs.identifier');
        $secret = config('services.whmcs.secret');
        $whmcsUrl = rtrim(config('services.whmcs.url'), '/').'/includes/api.php';

        $params = [
            'action' => 'DomainUpdateLockingStatus',
            'identifier' => $identifier,
            'secret' => $secret,
            'domainid' => $domainId,
            'responsetype' => 'json',
        ];

        if ($lockStatus !== null) {
            $params['lockstatus'] = $lockStatus;
        }

        $lockRes = Http::asForm()
            ->timeout(300)
            ->post($whmcsUrl, $params);

        if (!$lockRes->ok()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to communicate with WHMCS API',
                'data' => $lockRes->body(),
            ], 500);
        }

        $response = $lockRes->json();

        if (($response['result'] ?? '') !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update domain locking status',
                'data' => $response,
            ], 400);
        }

        $status = $lockStatus !== null ? ($lockStatus ? 'enabled' : 'disabled') : 'updated';

        return response()->json([
            'success' => true,
            'message' => "Domain lock {$status} successfully",
            'data' => [
                'domainid' => $domainId,
                'lockstatus' => $lockStatus,
            ],
        ]);
    }
}
