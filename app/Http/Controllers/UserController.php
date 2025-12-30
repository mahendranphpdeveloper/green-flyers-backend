<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminData;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     // Return all user data
    //     $users = User::all();
    //     return response()->json([
    //         'status' => true,
    //         'users' => $users,
    //     ]);
    // }

    public function index(Request $request)
    {
        Log::info('AdminController@index called', [
            'request_user_id' => optional($request->user())->id,
        ]);

        // Get the currently authenticated user
        $admin = $request->user();

        // Check if the authenticated user exists in the admindata table
        $isAdmin = AdminData::where('id', $admin->id)->first();

        if (!$isAdmin) {
            Log::warning('Unauthorized access attempt', [
                'user_id' => $admin->id ?? null,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unauthorized - Not an admin',
            ], 403);
        }

        Log::info('Admin verified, fetching users', [
            'admin_id' => $admin->id,
        ]);

        // Fetch all users
        $users = User::all();

        Log::info('Users fetched successfully', [
            'total_users' => $users->count(),
        ]);

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
    // public function show(Request $request, string $id)
    // {
    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found.'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'user' => $user
    //     ]);
    // }


    public function show(Request $request, string $id)
    {
        Log::info('AdminController@show called', [
            'request_user_id' => optional($request->user())->id,
            'target_user_id' => $id,
        ]);

        // Get the currently authenticated user
        $admin = $request->user();

        // Check if the authenticated user exists in admindata table
        $isAdmin = AdminData::where('id', $admin->id)->first();

        if (!$isAdmin) {
            Log::warning('Unauthorized access attempt to show user', [
                'user_id' => $admin->id ?? null,
                'target_user_id' => $id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unauthorized - Not an admin',
            ], 403);
        }

        // Fetch user by ID
        $user = User::find($id);

        if (!$user) {
            Log::warning('User not found', [
                'admin_id' => $admin->id,
                'target_user_id' => $id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        Log::info('User fetched successfully', [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
        ]);

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
    Log::info('update() called', ['request_all' => $request->all(), 'route_id' => $id]);

    // Validate only fields that can change
    $validated = $request->validate([
        'userName' => 'sometimes|string|max:255',
        'profilePic' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',
        'lastModification' => 'sometimes|date',
    ]);

    Log::info('Validated data', ['validated' => $validated]);

    // Find user by route param ID
    $user = \App\Models\User::findOrFail($id);

    // Update username
    if (isset($validated['userName'])) {
        $user->userName = $validated['userName'];
        Log::info('Updated userName', ['userName' => $user->userName]);
    }

    // Update lastModification
    if (isset($validated['lastModification'])) {
        $user->lastModification = $validated['lastModification'];
        Log::info('Updated lastModification', ['lastModification' => $user->lastModification]);
    }

    // Update profilePic
    if ($request->hasFile('profilePic')) {
        if ($user->profilePic && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profilePic)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profilePic);
            Log::info('Old profilePic deleted', ['previous' => $user->profilePic]);
        }
        $path = $request->file('profilePic')->store('profilefix', 'public');
        $user->profilePic = $path;
        Log::info('ProfilePic uploaded', ['path' => $path]);
    }

    $user->save();
    Log::info('User updated successfully', ['userId' => $id, 'updatedFields' => $validated]);

    return response()->json([
        'status' => true,
        'message' => 'User updated successfully',
        'user' => $user
    ]);
}





}