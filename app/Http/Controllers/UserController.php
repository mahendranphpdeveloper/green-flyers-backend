<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Return all user data
        $users = User::all();
        return response()->json([
            'status' => true,
            'users' => $users,
        ]);
    }

    public function profile(Request $request)
    {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated.'
            ], 401);
        }

        // Fetch from User model to ensure up-to-date information
        $user = User::find($authUser->id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'user' => $user
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'user' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     Log::info($request->all());
    //     $user = \App\Models\User::findOrFail($id);

    //     $request->validate([
    //         'userName' => 'sometimes|string|max:255',
    //         'profilePic' => 'sometimes|file|image|max:5120'
    //     ]);

    //     // Handle userName update
    //     if ($request->has('userName')) {
    //         $user->userName = $request->input('userName');
    //     }

    //     if (!file_exists(public_path('uploads/profilefix'))) {
    //         mkdir(public_path('uploads/profilefix'), 0777, true);
    //     }
    //     // Handle profilePic update
    //     if ($request->hasFile('profilePic')) {
    //         // Delete old profilePic file if it exists
    //         if ($user->profilePic && file_exists(public_path('uploads/profilefix/' . $user->profilePic))) {
    //             @unlink(public_path('uploads/profilefix/' . $user->profilePic));
    //         }

    //         $file = $request->file('profilePic');
    //         $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
    //         $file->move(public_path('uploads/profilefix'), $filename);

    //         // Store new filename only
    //         $user->profilePic = 'uploads/profilefix/' . $filename;
    //     }

    //     $user->save();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'User updated successfully.',
    //         'user' => $user
    //     ]);
    // }

    public function update(Request $request, string $id)
    {
        Log::info($request->all());
        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'userName' => 'sometimes|string|max:255',
            'profilePic' => 'sometimes|file|image|max:5120',
            'lastModification' => 'sometimes|date' // Accept lastModification from frontend, should be a date/datetime format string
        ]);

        // Handle userName update
        if ($request->has('userName')) {
            $user->userName = $request->input('userName');
        }

        // Handle lastModification update if provided
        if ($request->has('lastModification')) {
            $user->lastModification = $request->input('lastModification');
        }

        // Ensure uploads directory exists
        $uploadDir = public_path('uploads/profilefix');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle profilePic update if the file exists in the request
        if ($request->hasFile('profilePic')) {
            // Delete old profilePic file if it exists
            // Only delete if the saved field is not empty and file exists on disk
            if ($user->profilePic && file_exists(public_path($user->profilePic))) {
                @unlink(public_path($user->profilePic));
            }

            $file = $request->file('profilePic');
            $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);

            // Save the new relative path to profilePic
            $user->profilePic = 'uploads/profilefix/' . $filename;
        } else {
            // Handle case where no file is uploaded, but you may still want to update other fields
            // If you want to set profilePic field to null when not passed, uncomment the below:
            // $user->profilePic = null;
        }

        // Save changes to the user, including possible updated userName, lastModification, and/or profilePic
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User updated successfully.',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Optionally delete user's profile picture file if exists
        if ($user->profilePic && file_exists(public_path($user->profilePic))) {
            @unlink(public_path($user->profilePic));
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}