<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Operator;

class ShanLaunchGameController extends Controller
{
    public function launch(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'member_account' => 'required|string|max:50',
            'operator_code'  => 'required|string',
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

        // Lookup operator to get callback_url and secret_key
        $operator = Operator::where('code', $operator_code)
                            ->where('active', true)
                            ->first();

        if (!$operator) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Invalid operator code',
            ], 403);
        }

        $callbackUrl = $operator->callback_url ?? 'https://a1yoma.online/api/shan/balance';
        $secret_key = $operator->secret_key;

        // 1. Auto-create member if not exists
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

        // 2. Prepare payload for client callback getbalance
        $request_time = time();
        $sign = md5($operator_code . $request_time . 'getbalance' . $secret_key);
        $payload = [
            'batch_requests' => [
                [
                    'member_account' => $member_account,
                    'product_code'   => 1002 // or as required
                ]
            ],
            'operator_code' => $operator_code,
            'currency'      => 'MMK',
            'request_time'  => $request_time,
            'sign'          => $sign,
        ];

        // 3. Call client's getbalance API
        $balance = 0;
        try {
            $response = Http::timeout(5)->post($callbackUrl, $payload);
            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['data'][0]['balance'])) {
                    $balance = $json['data'][0]['balance'];
                }
            }
        } catch (\Exception $e) {
            // If call fails, fallback to 0 or handle as needed
            $balance = 0;
        }

        // 4. Build launch game URL
        $launchGameUrl = 'https://goldendragon7.pro/?user_name=' . urlencode($member_account) . '&balance=' . $balance;

        return response()->json([
            'status' => 'success',
            'launch_game_url' => $launchGameUrl
        ]);
    }
}
