<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
            'password' => 'required|string|min:6', // changed field name
        ]);

        // Get the logged-in admin via Sanctum
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user'
            ], 401);
        }

        $plainPassword = $request->password;

        // Update the password field in the admins table
        $admin->password = Hash::make($plainPassword); // changed field name
        $admin->save();

        // Logout other devices
        // 1. Handle Sanctum tokens
        if (method_exists($admin, 'currentAccessToken') && $admin->currentAccessToken()) {
            $currentToken = $admin->currentAccessToken();
            if ($currentToken instanceof \Laravel\Sanctum\TransientToken) {
                $admin->tokens()->delete();
            } else {
                $admin->tokens()->where('id', '!=', $currentToken->id)->delete();
            }
        }

        // 2. Handle Session based auth (if applicable)
        // This requires the 'web' middleware group or similar to be applying 'AuthenticateSession'
        // We use the 'admin' guard as seen in other methods
        try {
             // Re-login the current user to ensure session stays valid if using session driver
             // And invalidate others.
             // Note: logoutOtherDevices() expects the current password to verify.
             // Since we just updated the password, we pass the new one.
             $guard = Auth::guard('admin');
             
             // Check if session is active and method exists (to avoid errors in API-only context)
             if ($request->hasSession() && method_exists($guard, 'logoutOtherDevices')) {
                 $guard->logoutOtherDevices($plainPassword);
             }
        } catch (\Exception $e) {
            // Ignore if session guard is not active or configured differently
            Log::info('Session logout failed or not applicable: ' . $e->getMessage());
        }


        // Send Email Notification
        try {
            $details = [
                'time' => now()->toDateTimeString(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ];

            Mail::send([], [], function ($message) use ($admin, $details) {
                $message->to($admin->email)
                    ->subject('Security Alert: Password Changed')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                            <h2 style='color: #333;'>Password Changed Successfully</h2>
                            <p>Hello {$admin->adminname},</p>
                            <p>This is a notification that the password for your admin account was recently changed.</p>
                            <div style='background-color: #f9f9f9; padding: 15px; border-radius: 4px; margin: 15px 0;'>
                                <p style='margin: 5px 0;'><strong>Time:</strong> {$details['time']}</p>
                                <p style='margin: 5px 0;'><strong>IP Address:</strong> {$details['ip']}</p>
                                <p style='margin: 5px 0;'><strong>Device:</strong> {$details['user_agent']}</p>
                            </div>
                            <p>If you did not make this change, please contact support immediately.</p>
                            <p style='color: #888; font-size: 12px; margin-top: 20px;'>Green Flyers Backend Security</p>
                        </div>
                    ");
            });
        } catch (\Exception $e) {
            Log::error('Failed to send password change email', ['error' => $e->getMessage()]);
        }

        Log::info('Password updated', ['admin_id' => $admin->id]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
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
