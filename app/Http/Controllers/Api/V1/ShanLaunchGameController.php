<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShanLaunchGameController extends Controller
{
    public function launch(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'member_account' => 'required|string|max:50',
            'operator_code'  => 'required|string', // For security and correct sign
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $member_account = $request->member_account;
        $operator_code = $request->operator_code;
        $currency = 'MMK';
        $request_time = time();
        $secret_key = env('SEAMLESS_SECRET_KEY', ''); // Or get from Operator table if multi-operator
        $sign = md5($operator_code . $request_time . 'getbalance' . $secret_key);

        $getBalancePayload = [
            'batch_requests' => [
                [
                    'member_account' => $member_account,
                    'product_code'   => 1002, // or as appropriate
                ]
            ],
            'operator_code' => $operator_code,
            'currency'      => $currency,
            'request_time'  => $request_time,
            'sign'          => $sign,
        ];

        // 1. Call GetBalance API (internal call)
        $getBalanceApiUrl = url('/api/shan/balance');
        $response = Http::post($getBalanceApiUrl, $getBalancePayload);

        $resultData = $response->json();
        $balance = 0;
        $needCreate = false;

        if (isset($resultData['data'][0]['code']) && $resultData['data'][0]['code'] == 998) {
            // Member not found (use your SeamlessWalletCode for "MemberNotExist")
            $needCreate = true;
        } elseif (isset($resultData['data'][0]['balance'])) {
            $balance = $resultData['data'][0]['balance'];
        }

        // 2. If member doesn't exist, create them
        if ($needCreate) {
            DB::beginTransaction();
            $user = User::where('member_account', $member_account)->first();
            if (!$user) {
                $user = User::create([
                    'member_account' => $member_account,
                    'name'           => $member_account,
                    'balance'        => 0,
                    'password'       => bcrypt('defaultpassword'),
                    'register_date'  => now(),
                ]);
            }
            DB::commit();

            // 3. Call GetBalance API again for this new member
            $request_time = time();
            $sign = md5($operator_code . $request_time . 'getbalance' . $secret_key);
            $getBalancePayload['request_time'] = $request_time;
            $getBalancePayload['sign'] = $sign;

            $response = Http::post($getBalanceApiUrl, $getBalancePayload);
            $resultData = $response->json();
            $balance = 0;
            if (isset($resultData['data'][0]['balance'])) {
                $balance = $resultData['data'][0]['balance'];
            }
        }

        // 4. Build launch game URL
        $launchGameUrl = 'https://goldendragon7.pro/?member_account=' . urlencode($member_account) . '&balance=' . $balance;

        return response()->json([
            'status' => 'success',
            'launch_game_url' => $launchGameUrl
        ]);
    }
}
