<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use App\Models\SingleItineraryData;

class AdminNotificationController extends Controller
{
   
    // public function getUserNotifications($userId)
    // {
    //     Log::info('Fetching UNREAD notifications for user', ['userId' => $userId]);
    
    //     $notifications = UserNotification::with(['singleitinerary'])
    //         ->where('user_id', $userId)
    //         ->where('is_read', 'false') // ✅ ONLY unread
    //         ->orderBy('created_at', 'desc')
    //         ->get();
    
    //     Log::debug('Number of unread notifications fetched', [
    //         'count' => $notifications->count()
    //     ]);
    
    //     $notificationsTransformed = $notifications->map(function ($notification) {
    //         Log::debug('Transforming unread notification', [
    //             'notification_id' => $notification->id
    //         ]);
    
    //         return [
    //             'id'                 => $notification->id,
    //             'singleitinerary_id' => $notification->singleitinerary_id,
    //             'user_id'            => $notification->user_id,
    //             'title'              => $notification->title,
    //             'message'            => $notification->message,
    //             'status'             => $notification->status,
    //             'update_date'        => $notification->update_date,
    //             'created_at'         => $notification->created_at,
    //             'updated_at'         => $notification->updated_at,
    //             'is_read'            => $notification->is_read,
    //             'singleitinerary'    => $notification->singleitinerary,
    //             'ItineraryId'        => $notification->singleitinerary
    //                                     ? $notification->singleitinerary->ItineraryId
    //                                     : null,
    //         ];
    //     });
    
    //     Log::info('Returning unread notifications for user', [
    //         'userId' => $userId,
    //         'notification_count' => $notificationsTransformed->count()
    //     ]);
    
    //     return response()->json([
    //         'success' => true,
    //         'notifications' => $notificationsTransformed
    //     ]);
    // }

    public function getUserNotifications($userId)
{
    Log::info('Fetching ALL notifications for user', ['userId' => $userId]);

    $notifications = UserNotification::with('singleitinerary')
        ->where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->get();

    $notificationsTransformed = $notifications->map(function ($notification) {

        return [
            'id'          => $notification->id,
            'user_id'     => $notification->user_id,
            'title'       => $notification->title,
            'message'     => $notification->message,
            'status'      => $notification->status,
            'is_read'     => $notification->is_read,
            'read_at'     => $notification->read_at,
            'update_date' => $notification->update_date,
            'created_at'  => $notification->created_at,
            'updated_at'  => $notification->updated_at,

            //  Particular itinerary only
            'singleitinerary' => $notification->singleitinerary,

            //  Direct itinerary ID
            'ItineraryId' => $notification->singleitinerary
                ? $notification->singleitinerary->ItineraryId
                : null,
        ];
    });

    return response()->json([
        'success' => true,
        'notifications' => $notificationsTransformed
    ]);
}


    

    // POST: /api/admin/notification-reminder/store
//     public function store(Request $request)
// {
//     Log::info('Attempt to store admin notification reminder', [
//         'data' => $request->all()
//     ]);

//     // Validate based on updated table structure
//     $request->validate([
//         'user_id'     => 'required|integer',
//         'title'       => 'required|string|max:255',
//         'message'     => 'required|string|max:255',
//         'status'      => 'required|string|max:50',
//         'update_date' => 'nullable|string|max:255',
//     ]);

//     // Create notification (NO singleitinerary_id)
//     $notification = UserNotification::create([
//         'user_id'     => $request->user_id,
//         'title'       => $request->title,
//         'message'     => $request->message,
//         'status'      => $request->status,
//         'is_read'     => 'false',
//         'update_date' => $request->update_date ?? now()->toDateTimeString(),
//     ]);

//     Log::info('Admin notification reminder created successfully', [
//         'notification_id' => $notification->id
//     ]);

//     return response()->json([
//         'success'      => true,
//         'notification' => $notification
//     ]);
// }

public function store(Request $request)
{
    Log::info('Attempt to store admin notification reminder', [
        'data' => $request->all()
    ]);

    // Validate including singleitinerary_id
    $request->validate([
        'user_id'             => 'required|integer',
        'singleitinerary_id'  => 'required|integer',
        'title'               => 'required|string|max:255',
        'message'             => 'required|string|max:255',
        'status'              => 'required|string|max:50',
        'update_date'         => 'nullable|string|max:255',
    ]);

    //  Create notification WITH itinerary reference
    $notification = UserNotification::create([
        'user_id'            => $request->user_id,
        'singleitinerary_id' => $request->singleitinerary_id,
        'title'              => $request->title,
        'message'            => $request->message,
        'status'             => $request->status,
        'is_read'            => 'false',
        'update_date'        => $request->update_date ?? now()->toDateTimeString(),
    ]);

    Log::info('Admin notification reminder created successfully', [
        'notification_id' => $notification->id,
        'user_id' => $request->user_id,
        'singleitinerary_id' => $request->singleitinerary_id
    ]);

    return response()->json([
        'success'      => true,
        'notification' => $notification
    ]);
}


// update the is_read status in notification table 
    public function markAsRead(Request $request, $id)
{
    $user = $request->user();

    Log::info('Marking notification as read', [
        'notification_id' => $id,
        'user_id' => $user->id
    ]);

    $notification = UserNotification::where('id', $id)
        ->where('user_id', $user->id) // security check
        ->first();

    if (!$notification) {
        return response()->json([
            'success' => false,
            'message' => 'Notification not found'
        ], 404);
    }

    // If already read, no need to update again
    if ($notification->is_read === 'true') {
        return response()->json([
            'success' => true,
            'message' => 'Notification already marked as read'
        ]);
    }

    $notification->update([
        'is_read' => 'true',
        'read_at' => now(),
    ]);

    Log::info('Notification marked as read successfully', [
        'notification_id' => $id
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Notification marked as read'
    ]);
}
}
