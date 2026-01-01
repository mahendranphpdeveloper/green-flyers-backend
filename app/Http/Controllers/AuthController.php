<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;



class AuthController extends Controller
{
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email'    => 'required|email',
    //         'password' => 'required'
    //     ]);

    //     $user = User::where('email', $request->email)->first();

    //     if (!$user || !Hash::check($request->password, $user->password)) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }

    //     // Create token
    //     $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Login successful',
    //         'token' => $token,
    //         'user' => [
    //             'id'    => $user->id,
    //             'name'  => $user->name,
    //             'email' => $user->email
    //         ]
    //     ]);
    // }

    //login with email id with otp
    
// public function login(Request $request)
// {
//     Log::info('Login API called', [
//         'email' => $request->email
//     ]);

//     $request->validate([
//         'email' => 'required|email',
//         'userName' => 'nullable|string|max:255',
//         'google_token' => 'nullable|string',
//     ]);

//     $email = $request->email;
//     $userName = $request->userName;               
//     $googleToken = $request->google_token;

//     $user = User::where('userEmail', $email)->first();
//     $isNewUser = false;

//     if ($user) {
//         // EXISTING USER
//         Log::info('Existing user found', [
//             'userId' => $user->userId,
//             'email' => $user->userEmail
//         ]);

//         if ($googleToken) {
//             $user->google_token = $googleToken;
//             Log::info('Google token updated', [
//                 'userId' => $user->userId
//             ]);
//         }

//         // Do NOT overwrite userName for existing user
//         $user->updated_at = now();
//         $user->save();

//     } else {
//         // NEW USER
//         Log::info('New user detected, creating record', [
//             'email' => $email,
//             'userName' => $userName
//         ]);

//         $user = User::create([
//             'userEmail' => $email,
//             'userName' => $userName,        
//             'google_token' => $googleToken,
//         ]);

//         $isNewUser = true;

//         Log::info('New user created', [
//             'userId' => $user->userId,
//             'email' => $user->userEmail
//         ]);
//     }

//     $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

//     Log::info('Login successful', [
//         'userId' => $user->userId,
//         'is_new_user' => $isNewUser
//     ]);

//     return response()->json([
//         'message' => 'Login successful',
//         'is_new_user' => $isNewUser,
//         'token' => $token,
//         'user' => $isNewUser ? null : [
//             'userId' => $user->userId,
//             'userName' => $user->userName,
//             'userEmail' => $user->userEmail,
//             'profilePic' => $user->profilePic,
//         ]
//     ]);
// }

public function login(Request $request)
{
    Log::info('Login API called', ['email' => $request->email]);

    $request->validate([
        'email' => 'required|email',
        'google_token' => 'nullable|string',
    ]);

    $email = $request->email;
    $googleToken = $request->google_token;

    $user = User::where('userEmail', $email)->first();

    // =========================
    // NEW USER (DO NOT STORE)
    // =========================
    if (!$user) {
        Log::info('New user detected, not stored yet', ['email' => $email]);

        return response()->json([
            'message' => 'New user',
            'is_new_user' => true,
            'userEmail' => $email
        ]);
    }

    // =========================
    // EXISTING USER
    // =========================
    Log::info('Existing user found', [
        'userId' => $user->userId,
        'email' => $user->userEmail
    ]);

    if ($googleToken) {
        $user->google_token = $googleToken;
        $user->save();
    }

    $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'is_new_user' => false,
        'token' => $token,
        'user' => [
            'userId' => $user->userId,
            'userName' => $user->userName,
            'userEmail' => $user->userEmail,
            'profilePic' => $user->profilePic,
            'offsetCredit' => $user->offsetCredit,
            'treeCredit' => $user->treeCredit,
        ]
    ]);
}

public function register(Request $request)
{
    Log::info('Register API called', $request->all());

    $request->validate([
        'email'        => 'required|email',
        'userName'     => 'required|string|max:255',
        'google_token' => 'nullable|string',
    ]);

    $email       = $request->email;
    $userName    = $request->userName;
    $googleToken = $request->google_token;

    // Prevent duplicate registration
    $existingUser = User::where('userEmail', $email)->first();

    if ($existingUser) {
        Log::warning('User already exists', ['email' => $email]);

        return response()->json([
            'message' => 'User already registered',
            'is_new_user' => false
        ], 409);
    }

    //  Create new user
    $user = User::create([
        'userEmail'    => $email,
        'userName'     => $userName,
        'google_token' => $googleToken,
    ]);

    Log::info('New user registered', [
        'userId' => $user->userId,
        'email' => $user->userEmail
    ]);

    //  Create auth token
    $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

    return response()->json([
        'message' => 'Registration successful',
        'is_new_user' => false,
        'token' => $token,
        'user' => [
            'userId'     => $user->userId,
            'userName'   => $user->userName,
            'userEmail'  => $user->userEmail,
            'profilePic' => $user->profilePic,
            'offsetCredit' => $user->offsetCredit,
            'treeCredit' => $user->treeCredit,
        ]
    ], 201);
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
            'offsetCredit' => $user->offsetCredit,
            'treeCredit' => $user->treeCredit,
        ]
    ]);
}

}
