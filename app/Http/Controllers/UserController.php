<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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
        

        // Log raw incoming request data to help debugging frontend issues
        Log::info('User update request payload', [
            'request_all' => $request->all(),
            'request_files' => $request->allFiles()
        ]);

        $user = \App\Models\User::findOrFail($id);

        // Log info before validation for debugging validation problems
        Log::info('Validating user update', [
            'userId' => $id,
            'input' => $request->all()
        ]);

        $validated = $request->validate([
            'userName' => 'sometimes|string|max:255',
            'profilePic' => 'sometimes|file|image|max:5120',
            'lastModification' => 'sometimes|date' // Accept lastModification from frontend, should be a date/datetime format string
        ]);

        Log::info('Validated user update request', [
            'validated' => $validated
        ]);

        // Handle userName update
        if ($request->has('userName')) {
            Log::info('Updating userName', [
                'old' => $user->userName,
                'new' => $request->input('userName')
            ]);
            $user->userName = $request->input('userName');
        }

        // Handle lastModification update if provided
        if ($request->has('lastModification')) {
            Log::info('Updating lastModification', [
                'old' => $user->lastModification,
                'new' => $request->input('lastModification')
            ]);
            $user->lastModification = $request->input('lastModification');
        }

        // Handle profilePic update using Laravel Storage
        if ($request->hasFile('profilePic')) {
            Log::info('profilePic file found in request');
            // Delete old profilePic file if it exists and is stored via Storage
            if ($user->profilePic && Storage::disk('public')->exists($user->profilePic)) {
                Log::info('Deleting old profilePic file from storage', [
                    'old_profilePic' => $user->profilePic
                ]);
                Storage::disk('public')->delete($user->profilePic);
            } elseif ($user->profilePic && file_exists(public_path($user->profilePic))) {
                // Extra fallback for legacy: remove manually from public path
                @unlink(public_path($user->profilePic));
            }

            $path = $request->file('profilePic')->store('profilefix', 'public');
            // Store only the relative path, e.g., "profilefix/xxxx.jpg"
            $user->profilePic = $path;
            Log::info('Updated user profilePic file in storage', [
                'new_profilePic' => $user->profilePic
            ]);
        } else {
            Log::info('No profilePic file uploaded in update request.');
        }

        // Save changes to the user, including possible updated userName, lastModification, and/or profilePic
        $user->save();

        Log::info('User updated successfully', [
            'userId' => $user->id,
            'updated_data' => $user->toArray()
        ]);

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