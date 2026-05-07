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

//     public static function sendItineraryReminders()
// {
//     Log::info('NotificationService: Starting sendItineraryReminders.');

//     $settings = NotificationsReminder::first();

//     if (!$settings || $settings->offset_reminder_status !== 'active') {
//         Log::info('Reminder inactive or settings missing.');
//         return;
//     }

//     $deadlineDays = (int) $settings->notification_deadline;
//     $offsetDays   = (int) $settings->offset_reminder_days;

//     // Safety: offset cannot be greater than deadline
//     if ($offsetDays > $deadlineDays) {
//         Log::warning('Offset greater than deadline. Adjusted automatically.');
//         $offsetDays = $deadlineDays;
//     }

//     $now = Carbon::now();

//     Log::info('Reminder settings loaded', [
//         'deadlineDays' => $deadlineDays,
//         'offsetDays'   => $offsetDays,
//         'currentTime'  => $now->toDateTimeString(),
//     ]);

//     // Fetch only itineraries inside deadline window
//     $itineraries = ItineraryData::with('user')
//         ->where('date', '>=', $now->copy()->subDays($deadlineDays))
//         ->get();

//     if ($itineraries->isEmpty()) {
//         Log::info('No itineraries found for reminder check.');
//         return;
//     }

//     $template = EmailTemplate::find(1);

//     if (!$template) {
//         Log::error('Email template (ID: 1) not found.');
//         return;
//     }

//     $htmlTemplate = $template->message;
//     $title        = $template->subject ?? 'Offset Reminder';

//     foreach ($itineraries as $itinerary) {

//         if (!$itinerary->ItineraryId || !$itinerary->date) {
//             continue;
//         }

//         $createdAt = Carbon::parse($itinerary->date);

//         // Calculate exact reminder trigger time
//         $deadlineDate       = $createdAt->copy()->addDays($deadlineDays);
//         $reminderTrigger    = $deadlineDate->copy()->subDays($offsetDays);

//         // Skip if reminder time not reached yet
//         if ($now->lt($reminderTrigger)) {
//             continue;
//         }

//         $user = $itinerary->user;

//         if (!$user || empty($user->userEmail)) {
//             continue;
//         }

//         // Prevent duplicate reminders
//         $alreadySent = UserNotification::where('singleitinerary_id', $itinerary->ItineraryId)
//             ->where('user_id', $user->userId)
//             ->whereNotNull('sent_at')
//             ->exists();

//         if ($alreadySent) {
//             continue;
//         }

//         // Trip date from itinerary table (NOT deadline date)
//         $tripDate = $createdAt->format('d-m-Y');

//         // Offset link from .env PROJECT_URL
//         $offsetLink = config('app.project_url');

//         $emailVars = [
//             '{{name}}'          => $user->userName ?? '',
//             '{{tripId}}'        => $itinerary->ItineraryId,
//             '{{tripDate}}'      => $itinerary->date,
//             '{{origin}}'        => $itinerary->origin ?? 'N/A',
//             '{{destination}}'   => $itinerary->destination ?? 'N/A',
//             '{{emissionValue}}' => number_format((float) ($itinerary->emission ?? 0), 2),
//             '{{offsetLink}}'    => $offsetLink,
//         ];

//         $htmlMessage = str_replace(
//             array_keys($emailVars),
//             array_values($emailVars),
//             $htmlTemplate
//         );

//         Log::info('MAIL ATTEMPT (Reminder)', [
//             'time' => now()->toDateTimeString(),
//             'email' => $user->userEmail ?? 'N/A',
//             'itinerary_id' => $itinerary->ItineraryId
//         ]);

//         try {

//             Mail::html($htmlMessage, function ($mail) use ($user, $title) {
//                 $mail->to($user->userEmail)
//                      ->subject($title);
//             });

//             $origin = $itinerary->origin ?? 'N/A';
//             $destination = $itinerary->destination ?? 'N/A';
//             $tripDate = $itinerary->date ? Carbon::parse($itinerary->date)->format('d-m-Y') : 'N/A';

//             $messageStr = "Reminder: Please complete your offset for your trip {$origin} → {$destination} on {$tripDate}.";

//             UserNotification::create([
//                 'singleitinerary_id' => $itinerary->ItineraryId,
//                 'user_id'            => $user->userId,
//                 'title'              => 'Offset Reminder',
//                 'message'            => $messageStr,
//                 'status'             => 'reminder',
//                 'sent_at'            => now(),
//             ]);

//             Log::info('Reminder sent successfully', [
//                 'itinerary_id' => $itinerary->ItineraryId,
//                 'user_id'      => $user->userId
//             ]);

//         } catch (\Exception $e) {

//             Log::error('Reminder email failed', [
//                 'itinerary_id' => $itinerary->ItineraryId,
//                 'error'        => $e->getMessage()
//             ]);
//         }
//     }

//     Log::info('NotificationService: Finished processing reminders.');
// }

public static function sendItineraryReminders()
{
    Log::info('NotificationService: Starting sendItineraryReminders.');

    $settings = NotificationsReminder::first();

    if (!$settings || $settings->offset_reminder_status !== 'active') {
        Log::info('Reminder inactive or settings missing.');
        return;
    }

    $deadlineDays = (int) $settings->notification_deadline;
    $offsetDays   = (int) $settings->offset_reminder_days;

    // Safety: offset cannot be greater than deadline
    if ($offsetDays > $deadlineDays) {
        Log::warning('Offset greater than deadline. Adjusted automatically.');
        $offsetDays = $deadlineDays;
    }

    $now = Carbon::now();

    Log::info('Reminder settings loaded', [
        'deadlineDays' => $deadlineDays,
        'offsetDays'   => $offsetDays,
        'currentTime'  => $now->toDateTimeString(),
    ]);

    // ✅ No date filter - fetch ALL itineraries
    $itineraries = ItineraryData::with('user')->get();

    if ($itineraries->isEmpty()) {
        Log::info('No itineraries found for reminder check.');
        return;
    }

    Log::info('NotificationService: Checking itineraries for reminders.', [
        'count' => $itineraries->count()
    ]);

    $template = EmailTemplate::find(1);

    if (!$template) {
        Log::error('Email template (ID: 1) not found.');
        return;
    }

    $htmlTemplate = $template->message;
    $title        = $template->subject ?? 'Offset Reminder';

    foreach ($itineraries as $itinerary) {

        if (!$itinerary->ItineraryId || !$itinerary->created_at) {
            continue;
        }

        $tripDateObj = $itinerary->date
            ? Carbon::parse($itinerary->date)
            : null;

        $createdAt = Carbon::parse($itinerary->created_at);

        // FUTURE / CURRENT TRIP
        if ($tripDateObj && $tripDateObj->gte($now->copy()->startOfDay())) {
            $reminderTrigger = $tripDateObj->copy()
                ->addDays($deadlineDays - $offsetDays);
        } else {
            // PAST TRIP
            $reminderTrigger = $createdAt->copy()
                ->addDays($deadlineDays - $offsetDays);
        }

        // Skip until reminder date reached
        if ($now->lt($reminderTrigger)) {
            Log::info('Reminder date not reached yet.', [
                'itinerary_id' => $itinerary->ItineraryId,
                'trigger_date' => $reminderTrigger->toDateString()
            ]);
            continue;
        }

        $user = $itinerary->user;

        if (!$user || empty($user->userEmail)) {
            continue;
        }

        // Prevent duplicate reminders
        $alreadySent = UserNotification::where('singleitinerary_id', $itinerary->ItineraryId)
            ->where('user_id', $user->userId)
            ->whereNotNull('sent_at')
            ->exists();

        if ($alreadySent) {
            continue;
        }

        // Trip date from itinerary table (NOT deadline date)
        // $tripDate = $createdAt->format('d-m-Y');

        // Offset link from .env PROJECT_URL
        $offsetLink = config('app.project_url');

        $emailVars = [
            '{{name}}'          => $user->userName ?? '',
            '{{tripId}}'        => $itinerary->ItineraryId,
            '{{tripDate}}'      => $itinerary->date,
            '{{origin}}'        => $itinerary->origin ?? 'N/A',
            '{{destination}}'   => $itinerary->destination ?? 'N/A',
            '{{emissionValue}}' => number_format((float) ($itinerary->emission ?? 0), 2),
            '{{offsetLink}}'    => $offsetLink,
        ];

        $htmlMessage = str_replace(
            array_keys($emailVars),
            array_values($emailVars),
            $htmlTemplate
        );

        Log::info('MAIL ATTEMPT (Reminder)', [
            'time' => now()->toDateTimeString(),
            'email' => $user->userEmail ?? 'N/A',
            'itinerary_id' => $itinerary->ItineraryId
        ]);

        try {

            Mail::html($htmlMessage, function ($mail) use ($user, $title) {
                $mail->to($user->userEmail)
                     ->subject($title);
            });

            $origin = $itinerary->origin ?? 'N/A';
            $destination = $itinerary->destination ?? 'N/A';
            $tripDate = $itinerary->date ? Carbon::parse($itinerary->date)->format('d-m-Y') : 'N/A';

            $messageStr = "Reminder: Please complete your offset for your trip {$origin} → {$destination} on {$tripDate}.";

            UserNotification::create([
                'singleitinerary_id' => $itinerary->ItineraryId,
                'user_id'            => $user->userId,
                'title'              => 'Offset Reminder',
                'message'            => $messageStr,
                'status'             => 'reminder',
                'sent_at'            => now(),
            ]);

            Log::info('Reminder sent successfully', [
                'itinerary_id' => $itinerary->ItineraryId,
                'user_id'      => $user->userId,
                'email'        => $user->userEmail
            ]);

        } catch (\Exception $e) {

            Log::error('Reminder email failed', [
                'itinerary_id' => $itinerary->ItineraryId,
                'error'        => $e->getMessage()
            ]);
        }
    }

    Log::info('NotificationService: Finished processing reminders.');
}





}
