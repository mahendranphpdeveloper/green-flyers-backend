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
        
        // Store password hash in session for AdminMiddleware check
        $request->session()->put('admin_password_hash', $admin->password);
        Log::info('Admin Login: Session Hash set', ['hash' => $admin->password, 'admin_id' => $admin->id]);

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

        // Update session hash to prevent logging out the current device
        $request->session()->put('admin_password_hash', $admin->password);
        Log::info('Password Change: Session Hash updated', ['hash' => $admin->password, 'admin_id' => $admin->id]);

        // Logout other devices logic is now handled by AdminMiddleware checking the hash match
        // We can still keep the token revocation for Sanctum


        // Revoke all other tokens (if using Sanctum)
        $currentToken = $admin->currentAccessToken();
        if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $admin->tokens()->where('id', '!=', $currentToken->id)->delete();
        } elseif ($currentToken) {
            // If using session auth (TransientToken), revoke all API tokens
            $admin->tokens()->delete();
        }

        // Send email notification with HTML content
        $time = now()->format('Y-m-d H:i:s');
        $device = $request->header('User-Agent');

        try {
            \Illuminate\Support\Facades\Mail::html(
                "<html>
                    <body style='font-family: Arial, sans-serif; color: #333;'>
                        <h1 style='color: #2E8B57;'>Password Changed Successfully</h1>
                        <p>Hello <strong>{$admin->adminname}</strong>,</p>
                        <p>Your password was changed recently. Here are the details:</p>
                        <ul>
                            <li><strong>Time:</strong> {$time}</li>
                            <li><strong>Device:</strong> {$device}</li>
                        </ul>
                        <p>If you did not perform this action, please contact support immediately.</p>
                        <br>
                        <p>Best Regards,</p>
                        <p style='color: #2E8B57; font-weight: bold;'>Green Flyers Club</p>
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
