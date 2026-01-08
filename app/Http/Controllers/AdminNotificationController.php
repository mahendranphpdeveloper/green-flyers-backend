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

        $notifications = UserNotification::with(['singleitinerary']) // only singleitinerary for now
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Log fetched notifications count
        Log::debug('Number of notifications fetched', ['count' => $notifications->count()]);

        $notificationsTransformed = $notifications->map(function ($notification) {
            Log::debug('Transforming notification', ['notification_id' => $notification->id]);
            return [
                'id' => $notification->id,
                'singleItinerary_id' => $notification->singleItinerary_id,
                'user_id' => $notification->user_id,
                'title' => $notification->title,
                'message' => $notification->message,
                'status' => $notification->status,
                'update_date' => $notification->update_date,
                'created_at' => $notification->created_at,
                'single_itinerary' => $notification->singleitinerary, // full single itinerary data
                'itinerary_id' => $notification->singleitinerary ? $notification->singleitinerary->itinerary_id : null,
            ];
        });

        Log::info('Returning notifications for user', ['userId' => $userId, 'notification_count' => $notificationsTransformed->count()]);

        return response()->json([
            'success' => true,
            'notifications' => $notificationsTransformed
        ]);
    }
    
    // POST: /api/admin/notification-reminder/store
    public function store(Request $request)
    {
        Log::info('Attempt to store admin notification reminder', ['data' => $request->all()]);

        $request->validate([
            'singleItinerary_id' => 'required|integer',
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:255',
            'status' => 'required|string|max:255',
        ]);

        $notification = UserNotification::create([
            'singleItinerary_id' => $request->singleItinerary_id,
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
            'status' => $request->status, // status sent by frontend
            'update_date' => now(),
        ]);

        Log::info('Admin notification reminder created', ['notification_id' => $notification->id]);

        return response()->json([
            'success' => true,
            'notification' => $notification
        ]);
    }

}
