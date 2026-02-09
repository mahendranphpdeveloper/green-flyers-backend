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

        // Update the password field in the admins table
        $admin->password = Hash::make($request->password);
        $admin->save();

        // Re-login the admin to update the current session's password hash
        // This is necessary for session-based security features to stay in sync
        Auth::guard('admin')->login($admin);

        /** @var \Illuminate\Auth\SessionGuard $adminGuard */
        $adminGuard = Auth::guard('admin');

        // Logout other devices for the admin guard
        if (method_exists($adminGuard, 'logoutOtherDevices')) {
            $adminGuard->logoutOtherDevices($request->password);
        }

        // Send confirmation email
        $adminEmail = $admin->email;
        $ipAddress = $request->ip();
        $userAgent = $request->header('User-Agent');
        $timestamp = now()->toDayDateTimeString();

        $html = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #2e7d32;'>Security Alert: Password Changed</h2>
                <p>Hello <strong>{$admin->adminname}</strong>,</p>
                <p>This is a confirmation that your administrator account password was recently changed.</p>
                <hr style='border: 0; border-top: 1px solid #eee;' />
                <p><strong>Change Details:</strong></p>
                <ul style='list-style: none; padding: 0;'>
                    <li>📅 <strong>Date/Time:</strong> {$timestamp}</li>
                    <li>🌍 <strong>IP Address:</strong> {$ipAddress}</li>
                    <li>💻 <strong>Browser:</strong> {$userAgent}</li>
                </ul>
                <hr style='border: 0; border-top: 1px solid #eee;' />
                <p>If you did <strong>not</strong> perform this action, please contact support immediately to secure your account.</p>
                <p>Regards,<br><strong>Green Flyers Support Team</strong></p>
            </div>
        ";

        try {
            Mail::html($html, function ($message) use ($adminEmail) {
                $message->to($adminEmail)
                    ->subject('Security Alert: Your Admin Password Has Been Changed');
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
