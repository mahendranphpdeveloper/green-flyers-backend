<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class AdminNotificationController extends Controller
{
    public function getUserNotifications($userId)
    {
        Log::info('Fetching notifications for user', ['userId' => $userId]);

        // Fetch notifications belonging to the user with relevant relations
        $notifications = UserNotification::with(['singleitinerary'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::debug('Number of notifications fetched', ['count' => $notifications->count()]);

        // Transform for API response.
        $notificationsTransformed = $notifications->map(function ($notification) {
            Log::debug('Transforming notification', ['notification_id' => $notification->id]);
            return [
                'id' => $notification->id,
                // Per DB: 'singleitinerary_id'
                'singleitinerary_id' => $notification->singleitinerary_id,
                'user_id'            => $notification->user_id,
                'title'              => $notification->title,
                'message'            => $notification->message,
                'status'             => $notification->status,
                // Per DB: 'update_date' is nullable string/varchar(255)
                'update_date'        => $notification->update_date,
                'created_at'         => $notification->created_at,
                'updated_at'         => $notification->updated_at,
                // Full related singleitinerary data
                'singleitinerary'    => $notification->singleitinerary,
                // ItineraryId from related singleitinerary (nullable)
                'ItineraryId'        => $notification->singleitinerary ? $notification->singleitinerary->ItineraryId : null,
            ];
        });

        Log::info('Returning notifications for user', [
            'userId' => $userId,
            'notification_count' => $notificationsTransformed->count()
        ]);

        return response()->json([
            'success' => true,
            'notifications' => $notificationsTransformed
        ]);
    }

    // POST: /api/admin/notification-reminder/store
    public function store(Request $request)
    {
        Log::info('Attempt to store admin notification reminder', ['data' => $request->all()]);

        // Validate according to DB structure. Note: status (varchar 50) and update_date is nullable.
        $request->validate([
            'singleitinerary_id' => 'required|integer',
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:255',
            'status' => 'required|string|max:50',
            'update_date' => 'nullable|string|max:255',
        ]);

        $notification = UserNotification::create([
            'singleitinerary_id' => $request->singleitinerary_id,
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
            'status' => $request->status,
            // update_date is a string; if provided, use it, else null or default to now() string
            'update_date' => $request->update_date ?? now()->toDateTimeString(),
        ]);

        Log::info('Admin notification reminder created', ['notification_id' => $notification->id]);

        return response()->json([
            'success' => true,
            'notification' => $notification
        ]);
    }
}
