<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminData;
use App\Models\ItineraryData;
use App\Models\BackgroundImage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    

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
    Log::info('UserController@store called', [
        'userName' => $request->input('userName'),
        'userEmail' => $request->input('userEmail'),
    ]);

    // Get authenticated user
    $admin = $request->user();

    // Check if logged in
    if (!$admin) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized - Not logged in',
        ], 401);
    }

    // Check admin
    $isAdmin = AdminData::where('id', $admin->id)->exists();

    if (!$isAdmin) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized - Not an admin',
        ], 403);
    }

    // Validate (removed unique)
    $validatedData = $request->validate([
        'userName' => 'required|string|max:255',
        'userEmail' => 'required|email',
    ]);

    try {
        // Manual check for existing user
        $existingUser = User::where('userEmail', $validatedData['userEmail'])->first();

        if ($existingUser) {
            return response()->json([
                'status' => false,
                'message' => 'Email already exists',
            ], 409); // 409 Conflict (best practice)
        }

        // Create user
        $user = User::create([
            'userName' => $validatedData['userName'],
            'userEmail' => $validatedData['userEmail'],
            'is_new_user' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error creating user', [
            'error' => $e->getMessage(),
            'admin_id' => $admin->id,
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Failed to create user',
            'error' => $e->getMessage(),
        ], 500);
    }
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


    // public function show(Request $request, string $id)
    // {
    //     $authUser = $request->user();

    //     if (!$authUser) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not authenticated.'
    //         ], 401);
    //     }

    //     // Fetch the requested user by the provided ID, not the authenticated user's ID
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
    $authUser = $request->user();

    if (!$authUser) {
        return response()->json([
            'status' => false,
            'message' => 'User not authenticated.'
        ], 401);
    }

    // Fetch the requested user by the provided ID
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found.'
        ], 404);
    }

    // ✅ Calculate tree count for offsetCredit
    $treeOffsetValue = (int) BackgroundImage::where('id', 1)
        ->value('treeOffsetsValue');

    $treeCount = 0;
    if ($treeOffsetValue > 0 && $user->offsetCredit > 0) {
        $treeCount = round($user->offsetCredit / $treeOffsetValue);
    }

    // Convert user model to array and override offsetCredit
    $userArray = $user->toArray();
    $userArray['offsetCredit'] = $treeCount;

    return response()->json([
        'status' => true,
        'user' => $userArray
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


public function destroy(Request $request, string $id)
{
    Log::info('AdminController@destroy called', [
        'request_user_id' => optional($request->user())->id,
        'target_user_id' => $id,
    ]);

    // Get authenticated user
    $admin = $request->user();

    // Verify admin exists in admindata table
    $isAdmin = AdminData::where('id', $admin->id)->exists();

    if (!$isAdmin) {
        Log::warning('Unauthorized delete attempt', [
            'user_id' => $admin->id ?? null,
            'target_user_id' => $id,
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Unauthorized - Not an admin',
        ], 403);
    }

    // Find user
    $user = User::find($id);

    if (!$user) {
        Log::warning('User not found for delete', [
            'admin_id' => $admin->id,
            'target_user_id' => $id,
        ]);

        return response()->json([
            'status' => false,
            'message' => 'User not found.',
        ], 404);
    }

    // Find user itineraries, and save to DeleteItinerary table
    $itineraries = \App\Models\ItineraryData::where('userId', $id)->get();
    
    foreach ($itineraries as $itinerary) {
        \App\Models\DeleteItinerary::create([
            'origin' => $itinerary->origin,
            'originCity' => $itinerary->originCity,
            'destination' => $itinerary->destination,
            'destinationCity' => $itinerary->destinationCity,
            'class' => $itinerary->class,
            'userName' => $user->userName,
            'deleted_date' => now()->toDateString(),
        ]);
        
        $itinerary->delete(); // Also delete the itinerary itself
    }

    // Delete user
    $user->delete();

    Log::info('User and related itineraries deleted successfully', [
        'admin_id' => $admin->id,
        'deleted_user_id' => $id,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'User deleted successfully.',
    ]);
}

// get the emission offset chart values
// public function getEmissionOffsetChart(Request $request, $id)
// {
//     $authUser = $request->user();

//     if (!$authUser) {
//         return response()->json([
//             'status' => false,
//             'message' => 'User not authenticated.'
//         ], 401);
//     }

//     $userId = $id;
//     $year   = now()->year;
//     $today  = now()->toDateString();

//     // ------------------ Monthly defaults ------------------
//     $emissionsData = array_fill(1, 12, 0);
//     $offsetsData   = array_fill(1, 12, 0);

//     // ------------------ Itinerary monthly sums (PAST + CURRENT ONLY) ------------------
//     $itineraries = ItineraryData::selectRaw('
//             MONTH(`date`) as month,
//             SUM(emission) as total_emission,
//             SUM(offsetAmount) as total_offset
//         ')
//         ->where('userId', $userId)
//         ->whereYear('date', $year)
//         ->whereDate('date', '<=', $today)
//         ->groupBy('month')
//         ->get();

//     foreach ($itineraries as $row) {
//         $emissionsData[$row->month] = (float) $row->total_emission;
//         $offsetsData[$row->month]   = (float) $row->total_offset;
//     }

//     // ------------------ TOTAL EMISSION TILL DATE (KG + TONNES) ------------------
//     $totalEmissionKg     = array_sum($emissionsData);
//     $totalEmissionTonnes = round($totalEmissionKg / 1000, 3);

//     // ------------------ Offset credit (UNCHANGED) ------------------
//     $user = User::where('userId', $userId)->first();

//     if ($user && $user->offsetCredit > 0) {

//         $creditDate = \Carbon\Carbon::parse($user->updated_at)->toDateString();

//         if ($creditDate <= $today) {
//             $creditMonth = \Carbon\Carbon::parse($creditDate)->month;

//             if (isset($offsetsData[$creditMonth])) {
//                 $offsetsData[$creditMonth] += (float) $user->offsetCredit;
//             }
//         }
//     }

//     return response()->json([
//         'year' => $year,
//         'user_id' => (int) $userId,
//         'months' => [
//             'Jan','Feb','Mar','Apr','May','Jun',
//             'Jul','Aug','Sep','Oct','Nov','Dec'
//         ],
//         'emissions' => array_values($emissionsData),
//         'offsets'   => array_values($offsetsData),

//         'total_emission_kg_till_date'     => $totalEmissionKg,
//         'total_emission_tonnes_till_date' => $totalEmissionTonnes
//     ]);
// }

public function getEmissionOffsetChart(Request $request, $id)
{
    $authUser = $request->user();

    if (!$authUser) {
        return response()->json([
            'status' => false,
            'message' => 'User not authenticated.'
        ], 401);
    }

    $userId = $id;
    $year   = now()->year;
    $today  = now()->toDateString();

    // ------------------ Monthly defaults ------------------
    $emissionsData = array_fill(1, 12, 0);
    $offsetsData   = array_fill(1, 12, 0);

    // ------------------ Itinerary monthly sums ------------------
    $itineraries = ItineraryData::selectRaw('
            MONTH(`date`) as month,
            SUM(emission) as total_emission,
            SUM(offsetAmount) as total_offset
        ')
        ->where('userId', $userId)
        ->whereYear('date', $year)
        ->whereDate('date', '<=', $today)
        ->groupBy('month')
        ->get();

    foreach ($itineraries as $row) {
        $emissionsData[$row->month] = (float) $row->total_emission;
        $offsetsData[$row->month]   = (float) $row->total_offset;
    }

    // ------------------ TOTAL EMISSION ------------------
    $totalEmissionKg     = array_sum($emissionsData);
    $totalEmissionTonnes = round($totalEmissionKg / 1000, 3);

    return response()->json([
        'year' => $year,
        'user_id' => (int) $userId,
        'months' => [
            'Jan','Feb','Mar','Apr','May','Jun',
            'Jul','Aug','Sep','Oct','Nov','Dec'
        ],
        'emissions' => array_values($emissionsData),
        'offsets'   => array_values($offsetsData),

        'total_emission_kg_till_date'     => $totalEmissionKg,
        'total_emission_tonnes_till_date' => $totalEmissionTonnes
    ]);
}




}