<?php

namespace App\Http\Controllers;

use App\Models\SmtpSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class SmtpSettingController extends Controller
{
    /**
     * Display the current SMTP settings.
     */
    public function show()
    {
        $settings = SmtpSetting::first();

        return response()->json([
            'status' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update the SMTP settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer' => 'string|max:255',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|string|max:255',
            'mail_username' => 'required|string|email|max:255',
            'mail_password' => 'required|string',
            'mail_encryption' => 'required|string|in:ssl,tls',
            'mail_from_address' => 'required|string|email|max:255',
            'mail_from_name' => 'required|string|max:255',
        ]);

        $settings = SmtpSetting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = SmtpSetting::create($validated);
        }

        // 1️⃣ Clear the cache to ensure new settings are picked up
        Cache::forget('smtp_settings');

        // 2️⃣ Force Laravel to reload mailers with new config
        Mail::forgetMailers();

        return response()->json([
            'status' => true,
            'message' => 'SMTP settings updated successfully and mailers reloaded.',
            'data' => $settings
        ]);
    }
}
