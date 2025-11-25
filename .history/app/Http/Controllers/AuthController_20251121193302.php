<?php

namespace App\Http\Controllers;

use App\Models\UserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'userEmail' => 'required|email',
            'userPassword' => 'required'
        ]);

        $user = UserData::where('userEmail', $request->userEmail)->first();

        if (!$user || !Hash::check($request->userPassword, $user->userPassword)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create token
        $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id'    => $user->userId,
                'name'  => $user->userName,
                'email' => $user->userEmail
            ]
        ]);
    }
}
