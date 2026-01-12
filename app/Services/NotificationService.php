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

        // Fetch reminder settings
        $reminderSettings = NotificationsReminder::first();

        if (!$reminderSettings) {
            Log::info('NotificationService: No reminder settings found. Exiting.');
            return;
        }

        // INCLUDE: check reminder status
        if ($reminderSettings->offset_reminder_status !== 'active') {
            Log::info('NotificationService: Offset reminder is not active. Exiting.');
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

        // Fetch itineraries using `date`
        $itineraries = ItineraryData::where(
            'date',
            '>=',
            $today->copy()->subDays($deadlineDays)
        )->get();

        Log::info('NotificationService: Fetched itineraries for reminders', [
            'count' => $itineraries->count()
        ]);

        // Fetch email template
        $template = EmailTemplate::find(1);
        if (!$template) {
            Log::warning('NotificationService: No email template (ID 1) found. Aborting.');
            return;
        }

        // Process each itinerary
        foreach ($itineraries as $itinerary) {

            $itineraryId = $itinerary->ItineraryId;

            // INCLUDE: debug log (remove later if needed)
            Log::debug('NotificationService: Raw itinerary data', [
                'ItineraryId' => $itineraryId,
                'date'        => $itinerary->date,
            ]);

            if (!$itineraryId || !$itinerary->date) {
                Log::warning('NotificationService: Invalid itinerary data', [
                    'itinerary' => $itinerary
                ]);
                continue;
            }

            // Date calculation
            $createdAt        = Carbon::parse($itinerary->date);
            $daysSinceCreated = $createdAt->diffInDays($today);
            $remainingDays    = $deadlineDays - $daysSinceCreated;

            Log::info('NotificationService: Processing itinerary', [
                'itinerary_id'     => $itineraryId,
                'created_at_date'  => $createdAt->toDateString(),
                'daysSinceCreated' => $daysSinceCreated,
                'remainingDays'    => $remainingDays
            ]);

            // Check offset window
            if ($remainingDays > $offsetDays) {
                continue;
            }

            // Fetch user
            $user = $itinerary->user;

            if (!$user || !$user->email) {
                Log::warning('NotificationService: No user or email found', [
                    'itinerary_id' => $itineraryId
                ]);
                continue;
            }

            // Prevent duplicate reminder
            $alreadyNotified = UserNotification::where('singleitinerary_id', $itineraryId)
                ->where('user_id', $user->userId)
                ->whereNotNull('sent_at')
                ->exists();

            if ($alreadyNotified) {
                Log::info('NotificationService: Reminder already sent', [
                    'itinerary_id' => $itineraryId,
                    'user_id'      => $user->userId
                ]);
                continue;
            }

            //  Prepare email
            $title = $template->subject;

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
                    'user_id'      => $user->userId,
                    'error'        => $e->getMessage()
                ]);
            }
        }

        Log::info('NotificationService: Finished processing itineraries.');
    }
}
