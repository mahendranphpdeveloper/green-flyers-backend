<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use App\Models\EmailTemplate;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'emailId' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);
    
        $data = [
            'to' => $request->emailId,
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        Mail::send([], [], function ($message) use ($data) {
            $message->to($data['to'])
                    ->subject($data['subject'])
                    ->html($data['message']);
        });

        
        return response()->json([
            'status' => 'success',
            'message' => 'Email sent successfully'
        ]);
    }

    /**
     * Check admin authentication
     */
    private function checkAdmin(Request $request)
    {
        $admin = $request->user();

        if (!$admin || !AdminData::where('id', $admin->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access'
            ], 403);
        }

        return null;
    }

    /**
     * GET Offset Reminder Template (ID = 1)
     */
    public function getOffsetReminderTemplate(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $template = EmailTemplate::find(1);

        if (!$template) {
            return response()->json([
                'status' => false,
                'message' => 'Offset Reminder template not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $template
        ]);
    }

    /**
     * GET Deletion Notification Template (ID = 2)
     */
    public function getDeletionNotificationTemplate(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $template = EmailTemplate::find(2);

        if (!$template) {
            return response()->json([
                'status' => false,
                'message' => 'Deletion Notification template not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $template
        ]);
    }

    /**
     * UPDATE Offset Reminder Template (ID = 1)
     */
    public function updateOffsetReminderTemplate(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $template = EmailTemplate::find(1);

        if (!$template) {
            return response()->json([
                'status' => false,
                'message' => 'Offset Reminder template not found'
            ], 404);
        }

        $template->update([
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        Log::info('Admin updated Offset Reminder template', [
            'admin_id' => $request->user()->id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Offset Reminder template updated successfully',
            'data' => $template
        ]);
    }

    /**
     * UPDATE Deletion Notification Template (ID = 2)
     */
    public function updateDeletionNotificationTemplate(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $template = EmailTemplate::find(2);

        if (!$template) {
            return response()->json([
                'status' => false,
                'message' => 'Deletion Notification template not found'
            ], 404);
        }

        $template->update([
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        Log::info('Admin updated Deletion Notification template', [
            'admin_id' => $request->user()->id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Deletion Notification template updated successfully',
            'data' => $template
        ]);
    }
}
