<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Adjust as needed
use Illuminate\Support\Facades\Validator;

class ShanLaunchGameController extends Controller
{
    public function launch(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'member_account' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'fail',
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::with('wallet')->where('user_name', $request->member_account)->first();
        if (!$user || !$user->wallet) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Member not found or wallet not available',
            ], 404);
        }

        // Get current balance
        $balance = (int) $user->wallet->balanceFloat; // or round as needed

        // Build launch game URL
        $launchGameUrl = 'https://goldendragon7.pro/?user_name=' . urlencode($request->member_account) . '&balance=' . $balance;

        return response()->json([
            'status' => 'success',
            'launch_game_url' => $launchGameUrl
        ]);
    }
}
