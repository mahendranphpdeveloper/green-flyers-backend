<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create token
        $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email
            ]
        ]);
    }

    public function googleLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'profilePic' => 'nullable|url',
            'token' => 'required|string'
        ]);

        $email = $request->input('email');
        $name = $request->input('name');
        $profilePic = $request->input('profilePic');

        // Try to find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
         
            $user = User::create([
                'name' => $name,
                'email' => $email,
              
                'password' => \Illuminate\Support\Str::random(32),
           
            ]);
        } else {
            
            $updated = false;
            if ($user->name !== $name) {
                $user->name = $name;
                $updated = true;
            }
   
            if ($updated) {
                $user->save();
            }
        }

        // Issue token using Laravel Sanctum
        $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

        return response()->json([
            'message' => 'Google login successful',
            'token' => $token,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                // 'profilePic' => $user->profile_pic ?? null,
            ]
        ]);
    }
}
