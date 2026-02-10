<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
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

        // Login the admin using the 'admin' guard and regenerate session
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Admin login successful',
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
            'password' => 'required|string|min:6',
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
        $admin->password = Hash::make($request->password);
        $admin->save();

        // Logout other devices
        $guard = Auth::guard('admin');
        if (method_exists($guard, 'logoutOtherDevices')) {
            $guard->setUser($admin);
            $guard->logoutOtherDevices($request->password);
        }

        // Revoke all other tokens (if using Sanctum)
        if ($admin->currentAccessToken()) {
            $admin->tokens()->where('id', '!=', $admin->currentAccessToken()->id)->delete();
        }

        // Send email notification with HTML content
        try {
            \Illuminate\Support\Facades\Mail::html(
                "<html>
                    <body>
                        <h1>Password Changed Successfully</h1>
                        <p>Hello {$admin->adminname},</p>
                        <p>Your password was changed recently. If you did not perform this action, please contact support immediately.</p>
                        <p>Thank you.</p>
                    </body>
                </html>",
                function ($message) use ($admin) {
                    $message->to($admin->email)
                        ->subject('Security Alert: Password Changed');
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to send password change email: ' . $e->getMessage());
        }

        Log::info('Password updated and email sent', ['admin_id' => $admin->id]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully. You have been logged out from other devices and an email has been sent.'
        ]);
    }



    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}
