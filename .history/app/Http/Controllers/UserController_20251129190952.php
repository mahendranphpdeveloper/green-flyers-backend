<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function profile(Request $request)
{
    return response()->json([
        'status' => true,
        'user'   => $request->user()
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'userName' => 'sometimes|string|max:255',
            'profilePic' => 'sometimes|file|image|max:5120'
        ]);

        // Handle userName update
        if ($request->has('userName')) {
            $user->userName = $request->input('userName');
        }

        // Handle profilePic update
        if ($request->hasFile('profilePic')) {
            // Delete old profilePic file if it exists
            if ($user->profilePic && file_exists(public_path('uploads/profilefix/' . $user->profilePic))) {
                @unlink(public_path('uploads/profilefix/' . $user->profilePic));
            }

            $file = $request->file('profilePic');
            $filename = uniqid('profile_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/profilefix'), $filename);

            // Store new filename only
            $user->profilePic = $filename;
        }

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
    public function destroy(string $id)
    {
        //
    }
}
