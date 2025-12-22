<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
}
