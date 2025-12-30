<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminData;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // public function adminLogin(Request $request)
    // {
    //     $request->validate([
    //         'adminname' => 'required|string',
    //         'password' => 'required|string',
    //     ]);

    //     $admin = AdminData::where('adminname', $request->adminname)->first();

    //     if (!$admin || !Hash::check($request->password, $admin->password)) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }

    //     // Issue Sanctum token for admin
    //     $token = $admin->createToken('AdminToken')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Admin login successful',
    //         'token' => $token,
    //         'admin' => [
    //             'id' => $admin->id,
    //             'adminname' => $admin->adminname,
    //             'email' => $admin->email,
    //         ],
    //     ]);
    // }

    public function adminLogin(Request $request)
{
    $request->validate([
        'adminname' => 'required|string',
        'password' => 'required|string',
    ]);

    $admin = AdminData::where('adminname', $request->adminname)->first();

    if (!$admin || !Hash::check($request->password, $admin->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    // Issue Sanctum token for admin
    $token = $admin->createToken('AdminToken')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Admin login successful',
        'token' => $token,
        'admin' => [
            'id' => $admin->id,
            'adminname' => $admin->adminname,
            'email' => $admin->email,
        ],
    ]);
}

public function verifyOldPassword(Request $request)
{
    $request->validate([
        'old_password' => 'required|string',
    ]);

    $admin = $request->user(); // Logged-in admin via Sanctum

    Log::info('Verify old password called', [
        'admin_id' => $admin->id
    ]);

    if (Hash::check($request->old_password, $admin->password)) {
        return response()->json([
            'success' => true,
            'message' => 'Old password matched'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Old password does not match'
    ], 401);
}


public function NewPasswordChange(Request $request)
{
    $request->validate([
        'password' => 'required|string|min:6|confirmed', // changed field name
    ]);

    // Get the logged-in admin via Sanctum
    $admin = $request->user();

    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized user'
        ], 401);
    }

    // Update the password field in the admins table
    $admin->password = Hash::make($request->password); // changed field name
    $admin->save();

    Log::info('Password updated', ['admin_id' => $admin->id]);

    return response()->json([
        'success' => true,
        'message' => 'Password updated successfully'
    ]);
}



}
