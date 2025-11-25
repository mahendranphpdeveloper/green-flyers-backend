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
            'userEmail' => 'required|email',
            'userName' => 'required|string',
            'profilePic' => 'nullable|url',
            'google_token' => 'required|string'
        ]);

        $email = $request->input('userEmail');
        $name = $request->input('userName');
        $profilePic = $request->input('profilePic');
        $googleToken = $request->input('google_token');

        // NOTE: userEmail is now the "official" field - map to model as appropriate
        $user = User::where('userEmail', $email)->first();

        if (!$user) {
         
            $user = User::create([
                'userName' => $name,
                'userEmail' => $email,
                'profilePic' => $profilePic,
                'google_token' => $googleToken,
                // You may want to generate a random string for password or set to null if not needed
                'password' => \Illuminate\Support\Str::random(32),
                // set created_at and updated_at automatically, or let Eloquent do it
            ]);
        } else {
            $updated = false;
            if ($user->userName !== $name) {
                $user->userName = $name;
                $updated = true;
            }
            if ($profilePic && $user->profilePic !== $profilePic) {
                $user->profilePic = $profilePic;
                $updated = true;
            }
            if ($user->google_token !== $googleToken) {
                $user->google_token = $googleToken;
                $updated = true;
            }

            if ($updated) {
                $user->lastModification = now()->toDateTimeString();
                $user->save();
            }
        }

        // Issue token using Laravel Sanctum
        $apiToken = $user->createToken('GreenFlyers_Token')->plainTextToken;

        return response()->json([
            'message' => 'Google login successful',
            'token' => $apiToken,
            'user' => [
                'userId'    => $user->userId,
                'userName'  => $user->userName,
                'userEmail' => $user->userEmail,
                'profilePic' => $user->profilePic ?? null,
                'google_token' => $user->google_token ?? null,
                'lastModification' => $user->lastModification ?? null,
                'created_at' => $user->created_at ?? null,
                'updated_at' => $user->updated_at ?? null,
            ]
        ]);
    }
}
