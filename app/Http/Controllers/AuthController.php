<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;


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




    // public function googleLogin(Request $request)
    // {
    //     $request->validate([
    //         'userEmail' => 'required|email',
    //         'userName' => 'required|string',
    //         'profilePic' => 'nullable|url',
    //         'token' => 'required|string'
    //     ]);

    //     $email = $request->input('userEmail');
    //     $name = $request->input('userName');
    //     $profilePic = $request->input('profilePic');
    //     $googleToken = $request->input('token');

    //     $user = User::where('userEmail', $email)->first();

    //     if (!$user) {
    //         $user = User::create([
    //             'userName' => $name,
    //             'userEmail' => $email,
    //             'profilePic' => $profilePic,
    //             'google_token' => $googleToken,
    //             // password not required for google login (auth happens by google token)
    //         ]);
    //     } else {
    //         $updated = false;
    //         if ($user->userName !== $name) {
    //             $user->userName = $name;
    //             $updated = true;
    //         }
    //         if ($profilePic && $user->profilePic !== $profilePic) {
    //             $user->profilePic = $profilePic;
    //             $updated = true;
    //         }
    //         if ($user->google_token !== $googleToken) {
    //             $user->google_token = $googleToken;
    //             $updated = true;
    //         }
    //         if ($updated) {
    //             $user->save();
    //         }
    //     }

    //     // Issue token using Laravel Sanctum
    //     $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Google login successful',
    //         'token' => $token,
    //         'user' => [
    //             'userId' => $user->userId,
    //             'name' => $user->userName,
    //             'email' => $user->userEmail,
    //             'profilePic' => $user->profilePic,
    //         ]
    //     ]);
    // }

    public function googleLogin(Request $request)
{
    $request->validate([
        'userEmail' => 'required|email',
        'userName' => 'required|string',
        'profilePic' => 'nullable|url',
        'token' => 'required|string'
    ]);

    $email = $request->userEmail;
    $name = $request->userName;
    $profilePic = $request->profilePic;
    $googleToken = $request->token;

    $user = User::where('userEmail', $email)->first();

    if (!$user) {
        // FIRST TIME GOOGLE LOGIN
        $user = User::create([
            'userName'     => $name,
            'userEmail'    => $email,
            'profilePic'   => $profilePic,
            'google_token' => $googleToken,
        ]);
    } else {
        // EXISTING USER → DO NOT overwrite profile edits
        $user->google_token = $googleToken;
        $user->save();
    }

    $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

    return response()->json([
        'message' => 'Google login successful',
        'token' => $token,
        'user' => [
            'userId'     => $user->userId,
            'name'       => $user->userName,   // always DB value
            'email'      => $user->userEmail,
            'profilePic' => $user->profilePic, // always DB value
        ]
    ]);
}

}
