<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        $data = $request->only(['to', 'subject', 'message']);

        Mail::raw($data['message'], function ($message) use ($data) {
            $message->to($data['to'])
                    ->subject($data['subject']);
        });

        return response()->json(['status' => 'success', 'message' => 'Email sent']);
    }
}
