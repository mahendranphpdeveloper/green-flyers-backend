<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminData;

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

}
