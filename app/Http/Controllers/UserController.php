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
 
// public function update(Request $request, $id)
// {
//     $user = $request->user();
//     Log::info('update() called in UserController', ['user' => $user, 'targetUserId' => $id]);

//     if (!$user) {
//         Log::warning('Unauthorized access attempt in update()');
//         return response()->json(['message' => 'Unauthorized.'], 401);
//     }

//     $targetUser = \App\Models\User::find($id);

//     if (!$targetUser) {
//         Log::warning('User not found in update()', ['id' => $id]);
//         return response()->json(['message' => 'User not found.'], 404);
//     }

//     // Optional: Only allow users to update their own account (customize as needed)
//     if ($user->id != $targetUser->id) {
//         Log::warning('Unauthorized update attempt on User', [
//             'userId' => $user->id,
//             'ownerUserId' => $targetUser->id
//         ]);
//         return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
//     }

//     $validatedData = $request->validate([
//         'userName' => 'sometimes|string|max:255',
//         'profilePic' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
//         'lastModification' => 'nullable|date',
//     ]);
//     Log::info('Validated data in update()', ['validatedData' => $validatedData]);

//     if (isset($validatedData['userName'])) {
//         $targetUser->userName = $validatedData['userName'];
//     }

//     if (isset($validatedData['lastModification'])) {
//         $targetUser->lastModification = $validatedData['lastModification'];
//     }

//     if ($request->hasFile('profilePic')) {
//         if ($targetUser->profilePic && Storage::disk('public')->exists($targetUser->profilePic)) {
//             Storage::disk('public')->delete($targetUser->profilePic);
//             Log::info('Old profilePic deleted in update()', ['previous' => $targetUser->profilePic]);
//         }

//         $file = $request->file('profilePic');
//         $path = $file->store('profilefix', 'public');
//         $validatedData['profilePic'] = $path;
//         Log::info('ProfilePic file uploaded in update()', ['path' => $path]);
//         $targetUser->profilePic = $path;
//     } else {
//         unset($validatedData['profilePic']);
//     }

//     $targetUser->save();
//     Log::info('User updated', [
//         'userId' => $id,
//         'updatedFields' => $validatedData
//     ]);

//     return response()->json([
//         'message' => 'User updated successfully.',
//         'user' => $targetUser
//     ]);
// }

public function update(Request $request, string $id)
{
    $request->merge(['userId' => $id]);

    $validated = $request->validate([
        'userId' => 'required|integer|exists:users,id',
        'userName' => 'sometimes|string|max:255',
        'profilePic' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',
        'lastModification' => 'sometimes|date',
    ]);

    $user = \App\Models\User::findOrFail($id);

    if ($request->has('userName')) {
        $user->userName = $request->userName;
    }

    if ($request->has('lastModification')) {
        $user->lastModification = $request->lastModification;
    }

    if ($request->hasFile('profilePic')) {

        // Delete old image if exists
        if ($user->profilePic && Storage::disk('public')->exists($user->profilePic)) {
            Storage::disk('public')->delete($user->profilePic);
        }

        // Store new image
        $path = $request->file('profilePic')->store('profilefix', 'public');

        $user->profilePic = $path;
    }

    $user->save();  
    return response()->json([
        'status' => true,
        'message' => 'User updated successfully',
        'user' => $user
    ]);
}




}