<?php

namespace App\Providers;

use App\Models\SmtpSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Use caching to avoid DB calls on every request
        $settings = Cache::remember('smtp_settings', 3600, function () {
            // Check if table exists to avoid errors during initial setup/migrations
            try {
                return SmtpSetting::first();
            } catch (\Exception $e) {
                return null;
            }
        });

        if ($settings) {
            try {
                Config::set('mail.default', $settings->mail_mailer);

                // Specifically override the smtp mailer settings
                Config::set('mail.mailers.smtp.host', $settings->mail_host);
                Config::set('mail.mailers.smtp.port', $settings->mail_port);
                Config::set('mail.mailers.smtp.username', $settings->mail_username);
                Config::set('mail.mailers.smtp.password', $settings->mail_password);
                Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption);

                // Global From address
                Config::set('mail.from.address', $settings->mail_from_address);
                Config::set('mail.from.name', $settings->mail_from_name);
            } catch (\Exception $e) {
                // If decryption fails, clear the cache and log the error
                Cache::forget('smtp_settings');
                Log::error('SMTP decryption failed: ' . $e->getMessage());
            }
        }
    }
}
