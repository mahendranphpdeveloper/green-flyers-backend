<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ItineraryData;
use App\Models\NotificationsReminder;
use App\Models\UserNotification;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send HTML itinerary reminders automatically
     */
    public static function sendItineraryReminders()
    {
        Log::info('NotificationService: Starting sendItineraryReminders method.');

        $reminderSettings = NotificationsReminder::first();
        if (!$reminderSettings || $reminderSettings->offset_reminder_status !== 'active') {
            Log::info('NotificationService: Reminder not active or missing.');
            return;
        }

        $deadlineDays = (int) $reminderSettings->notification_deadline;
        $offsetDays   = (int) $reminderSettings->offset_reminder_days;
        $today        = Carbon::today();

        Log::info('NotificationService: Deadline and offset days calculated', [
            'deadlineDays' => $deadlineDays,
            'offsetDays'   => $offsetDays,
            'today'        => $today->toDateString(),
        ]);

        $itineraries = ItineraryData::with('user')
            ->where('date', '>=', $today->copy()->subDays($deadlineDays))
            ->get();

        Log::info('NotificationService: Fetched itineraries for reminders', [
            'count' => $itineraries->count()
        ]);

        // Instead of using the message column, use the provided $htmlTemplate
        $htmlTemplate = <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Carbon Offset Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f7f7f7; color: #333333; line-height: 1.6; margin: 0; padding: 0; }
        .container { max-width: 600px; background-color: #ffffff; margin: 30px auto; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        h2 { color: #2E8B57; }
        p { margin: 12px 0; }
        .trip-details { background-color: #f0f0f0; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .trip-details strong { display: inline-block; width: 120px; }
        a.button { display: inline-block; padding: 12px 20px; margin: 20px 0; background-color: #2E8B57; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .footer { font-size: 12px; color: #888888; margin-top: 25px; border-top: 1px solid #eeeeee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Carbon Offset Reminder</h2>
        <p>Hi {{name}},</p>
        <p>We noticed that your recent trip is not fully carbon-offset yet. Offsetting your emissions helps reduce your environmental impact and brings you closer to your sustainability goals.</p>
        
        <div class="trip-details">
            <p><strong>Trip ID:</strong> {{tripId}}</p>
            <p><strong>Date:</strong> {{tripDate}}</p>
            <p><strong>Emissions:</strong> {{emissionValue}} kg CO₂</p>
        </div>
        <p>You can complete your offset quickly using the link below:</p>
        <p><a href="{{offsetLink}}" class="button">Complete Your Offset</a></p>
        <p>If you’ve already taken action, thank you! You may ignore this message.</p>
        <p>Need help? Just reply to this email, and we’ll assist you.</p>
        <p>Thank you for choosing to make a positive impact.</p>
        <p>Regards,<br>GreenFly Team</p>
        <div class="footer">
            &copy; 2026 GreenFly. All rights reserved.
        </div>
    </div>
</body>
</html>

HTML;

        // Subject hardcoded or optional fallback if subject not present
        $template = EmailTemplate::find(1);
        $title = $template && !empty($template->subject)
            ? $template->subject
            : "Carbon Offset Reminder";

        foreach ($itineraries as $itinerary) {
            $itineraryId = $itinerary->ItineraryId;

            if (!$itineraryId || !$itinerary->date) {
                continue;
            }

            $createdAt        = Carbon::parse($itinerary->date);
            $daysSinceCreated = $createdAt->diffInDays($today);
            $remainingDays    = $deadlineDays - $daysSinceCreated;

            if ($remainingDays > $offsetDays) {
                continue;
            }

            $user = $itinerary->user;

            if (!$user || empty($user->userEmail)) {
                Log::warning('NotificationService: No user or email found', [
                    'itinerary_id' => $itineraryId
                ]);
                continue;
            }

            $alreadyNotified = UserNotification::where('singleitinerary_id', $itineraryId)
                ->where('user_id', $user->userId)
                ->whereNotNull('sent_at')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            // Prepare template variables
            $emailVars = [
                '{{name}}'        => $user->userName ?? '',
                '{{tripId}}'      => $itineraryId,
                '{{tripDate}}'    => Carbon::parse($itinerary->date)->toDateString(),
                '{{emissionValue}}' => number_format((float)($itinerary->emission ?? 0), 2),
                '{{offsetLink}}'  => 'https://jayamdesigners.co.in/green-flyers11/'
            ];

            $htmlMessage = str_replace(
                array_keys($emailVars),
                array_values($emailVars),
                $htmlTemplate
            );

            try {
                Mail::html($htmlMessage, function ($mail) use ($user, $title) {
                    $mail->to($user->userEmail)
                        ->subject($title);
                });

                UserNotification::create([
                    'singleitinerary_id' => $itineraryId,
                    'user_id'            => $user->userId,
                    'title'              => $title,
                    'message'            => strip_tags($htmlMessage),
                    'status'             => 'unread',
                    'sent_at'            => now()
                ]);

                Log::info('NotificationService: Notification sent successfully', [
                    'itinerary_id' => $itineraryId,
                    'user_id'      => $user->userId
                ]);
            } catch (\Exception $e) {
                Log::error('NotificationService: Email send failed', [
                    'itinerary_id' => $itineraryId,
                    'error'        => $e->getMessage()
                ]);
            }
        }

        Log::info('NotificationService: Finished processing itineraries.');
    }
}
