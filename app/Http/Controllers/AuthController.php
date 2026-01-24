<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;



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

    //generate and send otp
    public function sendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $email = $request->email;

    Log::info('Send OTP called', ['email' => $email]);

    $user = User::where('userEmail', $email)->first();

    if (!$user) {
        return response()->json([
            'status'  => false,
            'message' => 'User not found',
            'action'  => 'REGISTER'
        ], 404);
    }

    // Generate 6-digit OTP
    $otp = random_int(100000, 999999);

    // Save OTP with expiry (5 minutes)
    $user->otp_code = (string) $otp;
    $user->otp_expires_at = now()->addMinutes(5);
    $user->save();

    // Email HTML Template
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>OTP Verification</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">

<tr>
<td style="padding:24px 32px;border-bottom:1px solid #eaeaea;">
<h2 style="margin:0;color:#2e7d32;">Green Flyers</h2>
</td>
</tr>

<tr>
<td style="padding:32px;">
<p style="font-size:15px;color:#333;">Hello,</p>

<p style="font-size:15px;color:#333;">
We received a request to verify your email address
<strong>' . $email . '</strong>.
</p>

<p style="font-size:15px;color:#333;margin-top:24px;">
Use the following One-Time Password (OTP):
</p>

<div style="margin:24px 0;padding:16px;background:#f1f8e9;border:1px dashed #81c784;text-align:center;border-radius:6px;">
<span style="font-size:28px;letter-spacing:6px;font-weight:bold;color:#2e7d32;">
' . $otp . '
</span>
</div>

<p style="font-size:14px;color:#555;">
This OTP is valid for <strong>5 minutes</strong>. Please do not share this code with anyone.
</p>

<p style="font-size:14px;color:#555;margin-top:20px;">
If you did not request this, please ignore this email.
</p>

<p style="font-size:14px;color:#333;margin-top:30px;">
Regards,<br><strong>Green Flyers Team</strong>
</p>
</td>
</tr>

<tr>
<td style="padding:16px 32px;background:#fafafa;border-top:1px solid #eaeaea;font-size:12px;color:#777;text-align:center;">
© ' . date('Y') . ' Green Flyers. All rights reserved.
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>';

    // Send Email
    Mail::html($html, function ($mail) use ($email) {
        $mail->to($email)
             ->subject('Your OTP Verification Code');
    });

    return response()->json([
        'status'     => true,
        'message'    => 'OTP sent successfully',
        'expires_in' => 300, // seconds
        'action'     => 'VERIFY_OTP'
    ]);
}


    //verify otp 
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6'
        ]);
    
        $user = User::where('userEmail', $request->email)->first();
    
        if (!$user || !$user->otp_code) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP not generated'
            ], 400);
        }
    
        if (now()->gt($user->otp_expires_at)) {
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();
    
            return response()->json([
                'status'  => false,
                'message' => 'OTP expired'
            ], 400);
        }
    
        if ($user->otp_code !== $request->otp) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid OTP'
            ], 400);
        }
    
        // OTP VERIFIED SUCCESSFULLY
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();
    
        return response()->json([
            'status'  => true,
            'message' => 'OTP verified successfully'
        ]);
    }
    



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



    //Google Login

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

    //Facebook Login
    public function facebookLogin(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'userEmail'   => 'required|email',
            'userName'    => 'required|string',
            'profilePic'  => 'nullable|url',
            'token'       => 'required|string'
        ]);

        $email = $request->userEmail;
        $name = $request->userName;
        $profilePic = $request->profilePic;
        $facebookToken = $request->token;

        // Check if user already exists
        $user = User::where('userEmail', $email)->first();

        if (!$user) {
            // FIRST TIME FACEBOOK LOGIN
            $user = User::create([
                'userName'       => $name,
                'userEmail'      => $email,
                'profilePic'     => $profilePic,
                'facebook_token' => $facebookToken,
            ]);
        } else {
            // EXISTING USER → update facebook token without overwriting profile edits
            $user->facebook_token = $facebookToken;
            $user->save();
        }

        // Generate API token
        $token = $user->createToken('GreenFlyers_Token')->plainTextToken;

        // Return response
        return response()->json([
            'message' => 'Facebook login successful',
            'token'   => $token,
            'user' => [
                'userId'       => $user->userId,
                'name'         => $user->userName,    // DB value
                'email'        => $user->userEmail,
                'profilePic'   => $user->profilePic,  // DB value
                'offsetCredit' => $user->offsetCredit,
                'treeCredit'   => $user->treeCredit,
            ]
        ]);
    }
}
