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

        // Fetch reminder settings (no status check)
        $reminderSettings = NotificationsReminder::first();

        if (!$reminderSettings) {
            Log::info('NotificationService: No reminder settings found. Exiting.');
            return;
        }

        $deadlineDays = (int) $reminderSettings->notification_deadline;
        $offsetDays   = (int) $reminderSettings->offset_reminder_days;
        $today        = Carbon::today();

        Log::info('NotificationService: Deadline and offset days calculated', [
            'deadlineDays' => $deadlineDays,
            'offsetDays' => $offsetDays,
            'today' => $today->toDateString()
        ]);

        // Fetch itineraries created or updated within the last $deadlineDays
        $itineraries = ItineraryData::where('created_at', '>=', $today->copy()->subDays($deadlineDays))
            ->orWhere('updated_at', '>=', $today->copy()->subDays($deadlineDays))
            ->get();

        Log::info('NotificationService: Fetched itineraries for reminders', [
            'count' => $itineraries->count()
        ]);

        // Fetch HTML email template (id = 1)
        $template = EmailTemplate::find(1);
        if (!$template) {
            Log::warning('NotificationService: No email template (ID 1) found. Aborting.');
            return;
        }

        foreach ($itineraries as $itinerary) {
            // ✅ Use correct primary key
            $itineraryId = $itinerary->ItineraryId;

            // Calculate days since created (integer)
            $daysSinceCreated = Carbon::parse($itinerary->created_at)->diffInDays($today);
            $remainingDays = $deadlineDays - $daysSinceCreated;

            Log::info('NotificationService: Processing itinerary', [
                'itinerary_id' => $itineraryId,
                'daysSinceCreated' => $daysSinceCreated,
                'remainingDays' => $remainingDays
            ]);

            // Only send reminder if remainingDays <= offsetDays
            if ($remainingDays <= $offsetDays) {
                $user = $itinerary->user;

                if (!$user || !$user->email) {
                    Log::warning('NotificationService: No user or user email associated', [
                        'itinerary_id' => $itineraryId
                    ]);
                    continue;
                }

                // Prevent duplicate notifications
                $alreadyNotified = UserNotification::where('singleitinerary_id', $itineraryId)
                    ->where('user_id', $user->userId)
                    ->whereNotNull('sent_at')
                    ->exists();

                if ($alreadyNotified) {
                    Log::info('NotificationService: Notification already sent', [
                        'itinerary_id' => $itineraryId,
                        'user_id' => $user->userId
                    ]);
                    continue;
                }

                $title = $template->subject;

                // Replace placeholders in template
                $htmlMessage = str_replace(
                    ['{{name}}', '{{itinerary_id}}', '{{remaining_days}}'],
                    [$user->name, $itineraryId, $remainingDays],
                    $template->body
                );

                // Send email
                try {
                    Mail::send([], [], function ($mail) use ($user, $title, $htmlMessage) {
                        $mail->to($user->email)
                            ->subject($title)
                            ->setBody($htmlMessage, 'text/html');
                    });

                    // Record notification in DB
                    UserNotification::create([
                        'singleitinerary_id' => $itineraryId,
                        'user_id' => $user->userId,
                        'title' => $title,
                        'message' => strip_tags($htmlMessage),
                        'status' => 'unread',
                        'sent_at' => now()
                    ]);

                    Log::info('NotificationService: Notification sent and recorded', [
                        'itinerary_id' => $itineraryId,
                        'user_id' => $user->userId
                    ]);
                } catch (\Exception $e) {
                    Log::error('NotificationService: Failed to send email', [
                        'itinerary_id' => $itineraryId,
                        'user_id' => $user->userId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        Log::info('NotificationService: Finished processing itineraries.');
    }
}
