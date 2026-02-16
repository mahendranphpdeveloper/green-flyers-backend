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
        $offsetDays = (int) $reminderSettings->offset_reminder_days;
        $today = Carbon::today();

        Log::info('NotificationService: Deadline and offset days calculated', [
            'deadlineDays' => $deadlineDays,
            'offsetDays' => $offsetDays,
            'today' => $today->toDateString(),
        ]);

        $itineraries = ItineraryData::with('user')
            ->where('date', '>=', $today->copy()->subDays($deadlineDays))
            ->get();

        Log::info('NotificationService: Fetched itineraries for reminders', [
            'count' => $itineraries->count()
        ]);

        // Fetch template from database
        $template = EmailTemplate::find(1);

        if (!$template) {
            Log::error('NotificationService: Email template (ID: 1) not found.');
            return;
        }

        $htmlTemplate = $template->message;
        $title = $template->subject ?? "Offset Reminder";

        foreach ($itineraries as $itinerary) {
            $itineraryId = $itinerary->ItineraryId;

            if (!$itineraryId || !$itinerary->date) {
                continue;
            }

            $createdAt = Carbon::parse($itinerary->date);
            $daysSinceCreated = $createdAt->diffInDays($today);
            $remainingDays = $deadlineDays - $daysSinceCreated;

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
                '{{name}}' => $user->userName ?? '',
                '{{tripId}}' => $itineraryId,
                '{{tripDate}}' => Carbon::parse($itinerary->date)->toDateString(),
                '{{emissionValue}}' => number_format((float) ($itinerary->emission ?? 0), 2),
                '{{offsetLink}}' => 'https://jayamdesigners.co.in/green-flyers11/'
            ];

            $htmlMessage = str_replace(
                array_keys($emailVars),
                array_values($emailVars),
                $htmlTemplate
            );

            // Store only trip details (itinerary details) in the message column
            $itineraryDetails = "Trip ID: {$itineraryId}\n"
                . "Date: " . Carbon::parse($itinerary->date)->toDateString() . "\n"
                . "Emissions: " . number_format((float) ($itinerary->emission ?? 0), 2) . " kg CO₂\n";

            try {
                Mail::html($htmlMessage, function ($mail) use ($user, $title) {
                    $mail->to($user->userEmail)
                        ->subject($title);
                });

                UserNotification::create([
                    'singleitinerary_id' => $itineraryId,
                    'user_id' => $user->userId,
                    'title' => 'Offset Reminder',
                    'message' => $itineraryDetails,
                    'status' => 'unread',
                    'sent_at' => now()
                ]);

                Log::info('NotificationService: Notification sent successfully', [
                    'itinerary_id' => $itineraryId,
                    'user_id' => $user->userId
                ]);
            } catch (\Exception $e) {
                Log::error('NotificationService: Email send failed', [
                    'itinerary_id' => $itineraryId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('NotificationService: Finished processing itineraries.');
    }
}
