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

    
   
//     public function update(Request $request, string $id)
// {
//     $request->merge(['userId' => $id]);

//     $validated = $request->validate([
//         'userId' => 'required|integer|exists:users,id',
//         'userName' => 'sometimes|string|max:255',
//         'profilePic' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',
//         'lastModification' => 'sometimes|date',
//     ]);

//     $user = \App\Models\User::findOrFail($id);

//     if ($request->has('userName')) {
//         $user->userName = $request->userName;
//     }

//     if ($request->has('lastModification')) {
//         $user->lastModification = $request->lastModification;
//     }

//     if ($request->hasFile('profilePic')) {

//         // Delete old image if exists
//         if ($user->profilePic && Storage::disk('public')->exists($user->profilePic)) {
//             Storage::disk('public')->delete($user->profilePic);
//         }

//         // Store new image
//         $path = $request->file('profilePic')->store('profilefix', 'public');

//         $user->profilePic = $path;
//     }

//     $user->save();

//     return response()->json([
//         'status' => true,
//         'message' => 'User updated successfully',
//         'user' => $user
//     ]);
// }


public function update(Request $request, string $id)
{
    Log::info('UPDATE USER API CALLED');
    Log::info('User ID from URL', ['id' => $id]);

    // Log full request (except files content)
    Log::info('Request data', $request->except(['profilePic']));

    // Merge route id into request
    $request->merge(['userId' => $id]);
    Log::info('Merged userId into request', ['userId' => $id]);

    // Validate request
    try {
        $validated = $request->validate([
            'userId' => 'required|integer|exists:users,id',
            'userName' => 'sometimes|string|max:255',
            'profilePic' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',
            'lastModification' => 'sometimes|date',
        ]);
        Log::info('Validation passed', $validated);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed', $e->errors());
        throw $e;
    }

    // Fetch user
    $user = \App\Models\User::findOrFail($id);
    Log::info('User found', ['user' => $user->toArray()]);

    // Update username
    if ($request->has('userName')) {
        Log::info('Updating userName', ['old' => $user->userName, 'new' => $request->userName]);
        $user->userName = $request->userName;
    }

    // Update last modification
    if ($request->has('lastModification')) {
        Log::info('Updating lastModification', [
            'old' => $user->lastModification,
            'new' => $request->lastModification
        ]);
        $user->lastModification = $request->lastModification;
    }

    // Handle profile picture
    if ($request->hasFile('profilePic')) {
        Log::info('Profile picture upload detected');

        // Delete old image
        if ($user->profilePic && Storage::disk('public')->exists($user->profilePic)) {
            Log::info('Deleting old profile picture', ['path' => $user->profilePic]);
            Storage::disk('public')->delete($user->profilePic);
        }

        // Store new image
        $path = $request->file('profilePic')->store('profilefix', 'public');
        Log::info('New profile picture stored', ['path' => $path]);

        $user->profilePic = $path;
    } else {
        Log::info('No profile picture uploaded');
    }

    // Save user
    $user->save();
    Log::info('User updated successfully', ['user' => $user->toArray()]);

    return response()->json([
        'status' => true,
        'message' => 'User updated successfully',
        'user' => $user
    ]);
}

}