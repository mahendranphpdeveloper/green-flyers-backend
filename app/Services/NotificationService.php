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
        // Get active reminder settings
        $reminderSettings = NotificationsReminder::first();

        Log::info('NotificationService: Fetched reminder settings', [
            'reminderSettings' => $reminderSettings
        ]);

        if (!$reminderSettings || $reminderSettings->offset_reminder_status !== 'active') {
            Log::info('NotificationService: Reminders are inactive or not found. Exiting.');
            return; // Do nothing if reminders are inactive
        }

        $deadlineDays = $reminderSettings->notification_deadline; // e.g., 30
        $offsetDays = $reminderSettings->offset_reminder_days;    // e.g., 5
        $today = Carbon::today();

        Log::info('NotificationService: Deadline and offset days calculated', [
            'deadlineDays' => $deadlineDays,
            'offsetDays' => $offsetDays,
            'today' => $today->toDateString()
        ]);

        // Get itineraries created/updated in last deadlineDays
        $itineraries = ItineraryData::where('created_at', '>=', $today->copy()->subDays($deadlineDays))
            ->orWhere('updated_at', '>=', $today->copy()->subDays($deadlineDays))
            ->get();

        Log::info('NotificationService: Fetched itineraries for reminders', [
            'count' => $itineraries->count()
        ]);

        // Get HTML email template (id = 1)
        $template = EmailTemplate::find(1);
        if (!$template) {
            Log::warning('NotificationService: No email template (ID 1) found, aborting.');
            return;
        }

        foreach ($itineraries as $itinerary) {
            $daysSinceCreated = Carbon::parse($itinerary->created_at)->diffInDays($today);
            $remainingDays = $deadlineDays - $daysSinceCreated;

            Log::info('NotificationService: Processing itinerary', [
                'itinerary_id' => $itinerary->id,
                'daysSinceCreated' => $daysSinceCreated,
                'remainingDays' => $remainingDays
            ]);

            // Only send if remaining days <= offset
            if ($remainingDays <= $offsetDays) {
                $user = $itinerary->user; // Assumes relation user() exists in ItineraryData

                Log::info('NotificationService: User associated with itinerary', [
                    'userId' => $user ? $user->userId : null,
                    'userEmail' => $user ? $user->email : null
                ]);

                if ($user && $user->email) {

                    // Check if notification already sent
                    $alreadyNotified = UserNotification::where('singleitinerary_id', $itinerary->id)
                        ->where('user_id', $user->userId)
                        ->exists();

                    Log::info('NotificationService: Already notified check', [
                        'alreadyNotified' => $alreadyNotified
                    ]);

                    if (!$alreadyNotified) {

                        $title = $template->subject;

                        // Replace placeholders in HTML
                        $htmlMessage = str_replace(
                            ['{{name}}', '{{itinerary_id}}', '{{remaining_days}}'],
                            [$user->name, $itinerary->id, $remainingDays],
                            $template->body
                        );

                        // Log before sending email
                        Log::info('NotificationService: Sending email', [
                            'to' => $user->email,
                            'subject' => $title,
                            'itinerary_id' => $itinerary->id
                        ]);

                        // Send HTML email
                        Mail::send([], [], function ($mail) use ($user, $title, $htmlMessage) {
                            $mail->to($user->email)
                                 ->subject($title)
                                 ->setBody($htmlMessage, 'text/html');
                        });

                        // Save notification in DB
                        UserNotification::create([
                            'singleitinerary_id' => $itinerary->id,
                            'user_id' => $user->userId,
                            'title' => $title,
                            'message' => strip_tags($htmlMessage), // optional plain-text
                            'status' => 'unread'
                        ]);

                        Log::info('NotificationService: Notification created in DB', [
                            'itinerary_id' => $itinerary->id,
                            'user_id' => $user->userId
                        ]);
                    }
                } else {
                    Log::warning('NotificationService: No user or user email associated with itinerary', [
                        'itinerary_id' => $itinerary->id
                    ]);
                }
            }
        }
    }
}
