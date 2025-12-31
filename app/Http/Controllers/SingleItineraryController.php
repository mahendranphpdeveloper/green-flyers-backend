<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SingleItineraryData;
use App\Models\ItineraryData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SingleItineraryController extends Controller
{

    // public function index(Request $request)
    // {
    //     $user = $request->user();
    //     Log::info('index() called in SingleItineraryController', ['user' => $user]);
    //     if (!$user) {
    //         Log::warning('Unauthorized access attempt in index()');
    //         return response()->json(['message' => 'Unauthorized.'], 401);
    //     }
    //     $singleItineraries = SingleItineraryData::where('userId', $user->userId)->get();
    //     Log::info('index() returning single itineraries', ['userId' => $user->userId, 'count' => $singleItineraries->count()]);
    //     return response()->json($singleItineraries);
    // }

public function index(Request $request)
{
    Log::info('Admin SingleItinerary index() called');

    // Get authenticated admin
    $admin = $request->user();

    if (!$admin) {
        Log::warning('Unauthorized access attempt in admin single itinerary index()');
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    // Verify admin
    if (!AdminData::where('id', $admin->id)->exists()) {
        Log::warning('Non-admin attempted to access admin single itineraries', [
            'auth_id' => $admin->id,
        ]);

        return response()->json([
            'message' => 'Unauthorized - Not an admin'
        ], 403);
    }

    // Fetch ALL single itineraries
    $singleItineraries = SingleItineraryData::orderBy('id', 'desc')->get();

    if ($singleItineraries->isEmpty()) {
        Log::warning('No single itineraries found');
        return response()->json([
            'status' => false,
            'message' => 'No records found'
        ], 404);
    }

    // Attach User & Itinerary data
    $singleItineraries->transform(function ($single) {
        $single->user = User::where('userId', $single->userId)->first();
        $single->itinerary = ItineraryData::where('ItineraryId', $single->ItineraryId)->first();
        return $single;
    });

    Log::info('Admin single itineraries fetched successfully', [
        'admin_id' => $admin->id,
        'count' => $singleItineraries->count(),
    ]);

    return response()->json([
        'status' => true,
        'data' => $singleItineraries
    ]);
}

    public function store(Request $request)
    {
        $user = $request->user();
        Log::info('store() called in SingleItineraryController', ['user' => $user, 'request' => $request->all()]);
        if (!$user) {
            Log::warning('Unauthorized access attempt in store()');
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $validatedData = $request->validate([
            'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
            'uploadDate'      => 'nullable|date',
            'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|numeric',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
            'comments'        => 'nullable|string|max:1000',
        ]);
        Log::info('Validated data in store()', ['validatedData' => $validatedData]);

        $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])
            ->where('userId', $user->userId)
            ->first();

        if (!$itinerary) {
            Log::warning('Attempt to store SingleItinerary with unauthorized ItineraryId', [
                'userId' => $user->userId,
                'ItineraryId' => $validatedData['ItineraryId']
            ]);
            return response()->json(['message' => 'Unauthorized: ItineraryId does not belong to the authenticated user.'], 403);
        }

        if ($request->hasFile('certificateFile')) {
            $file = $request->file('certificateFile');
            $path = $file->store('certificates', 'public');
            $validatedData['certificateFile'] = $path;
            Log::info('Certificate file uploaded in store()', ['path' => $path]);
        } else {
            unset($validatedData['certificateFile']);
        }

        $validatedData['userId'] = $user->userId;

        $singleItinerary = SingleItineraryData::create($validatedData);
        Log::info('SingleItinerary created', ['singleItinerary' => $singleItinerary]);

        return response()->json([
            'message' => 'SingleItinerary created successfully.',
            'data' => $singleItinerary
        ], 201);
    }


    public function show(Request $request, $id)
    {
        $user = $request->user();
        Log::info('show() called in SingleItineraryController', ['user' => $user, 'id' => $id]);
        if (!$user) {
            Log::warning('Unauthorized access attempt in show()');
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            Log::warning('SingleItinerary not found in show()', ['id' => $id]);
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        if ($singleItinerary->userId !== $user->userId) {
            Log::warning('Unauthorized access to SingleItinerary in show()', [
                'userId' => $user->userId,
                'ownerUserId' => $singleItinerary->userId
            ]);
            return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
        }

        Log::info('SingleItinerary successfully returned from show()', ['singleItinerary' => $singleItinerary]);
        return response()->json($singleItinerary);
    }

    public function getByUserAndItinerary(Request $request, $userId, $ItineraryId)
    {
        Log::info('getByUserAndItinerary() called', ['requestedUserId' => $userId, 'requestedItineraryId' => $ItineraryId]);
        $user = $request->user();
        if (!$user) {
            Log::warning('Unauthorized access attempt in getByUserAndItinerary()');
            return response()->json(['message' => 'Unauthorized.'], 401);
        }
        if ($user->userId != $userId) {
            Log::warning('Access denied in getByUserAndItinerary()', [
                'loginUserId' => $user->userId,
                'requestedUserId' => $userId
            ]);
            return response()->json(['message' => 'Unauthorized: You do not have access to this user\'s resources.'], 403);
        }

        $singleItineraries = SingleItineraryData::where('userId', $userId)
            ->where('ItineraryId', $ItineraryId)
            ->get();

        if ($singleItineraries->isEmpty()) {
            Log::warning("No records found for getByUserAndItinerary()", [
                'userId' => $userId,
                'ItineraryId' => $ItineraryId
            ]);
            return response()->json(['message' => 'No records found for this user and itinerary.'], 404);
        }

        Log::info('getByUserAndItinerary() returning records', [
            'count' => $singleItineraries->count(),
            'userId' => $userId,
            'ItineraryId' => $ItineraryId
        ]);
        return response()->json($singleItineraries);
    }


    // public function update(Request $request, $id)
    // {
    //     $user = $request->user();
    //     Log::info('update() called in SingleItineraryController', ['user' => $user, 'singleItineraryId' => $id]);
    //     if (!$user) {
    //         Log::warning('Unauthorized access attempt in update()');
    //         return response()->json(['message' => 'Unauthorized.'], 401);
    //     }

    //     $singleItinerary = SingleItineraryData::find($id);

    //     if (!$singleItinerary) {
    //         Log::warning('SingleItinerary not found in update()', ['id' => $id]);
    //         return response()->json(['message' => 'SingleItinerary not found.'], 404);
    //     }

    //     if ($singleItinerary->userId !== $user->userId) {
    //         Log::warning('Unauthorized update attempt on SingleItinerary', [
    //             'userId' => $user->userId,
    //             'ownerUserId' => $singleItinerary->userId
    //         ]);
    //         return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
    //     }

    //     $validatedData = $request->validate([
    //         'ItineraryId'     => 'sometimes|integer|exists:itinerarydata,ItineraryId',
    //         'uploadDate'      => 'nullable|date',
    //         'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    //         'approvelStatus'  => 'nullable|string|max:255',
    //         'emissionOffset'  => 'nullable|numeric',
    //         'treesPlanted'    => 'nullable|integer',
    //         'projectTypes'    => 'nullable|string|max:255',
    //         'comments'        => 'nullable|string|max:1000',
    //     ]);
    //     Log::info('Validated data in update()', ['validatedData' => $validatedData]);

    //     if (isset($validatedData['ItineraryId'])) {
    //         $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])
    //             ->where('userId', $user->userId)
    //             ->first();
    //         if (!$itinerary) {
    //             Log::warning('Unauthorized ItineraryId in update()', [
    //                 'userId' => $user->userId,
    //                 'ItineraryId' => $validatedData['ItineraryId']
    //             ]);
    //             return response()->json(['message' => 'Unauthorized: ItineraryId does not belong to the authenticated user.'], 403);
    //         }
    //     }

    //     if ($request->hasFile('certificateFile')) {
    //         if ($singleItinerary->certificateFile && Storage::disk('public')->exists($singleItinerary->certificateFile)) {
    //             Storage::disk('public')->delete($singleItinerary->certificateFile);
    //             Log::info('Old certificateFile deleted in update()', ['previous' => $singleItinerary->certificateFile]);
    //         }

    //         $file = $request->file('certificateFile');
    //         $path = $file->store('certificates', 'public');
    //         $validatedData['certificateFile'] = $path;
    //         Log::info('Certificate file uploaded in update()', ['path' => $path]);
    //     } else {
    //         unset($validatedData['certificateFile']);
    //     }

    //     $singleItinerary->update($validatedData);
    //     Log::info('SingleItinerary updated', [
    //         'singleItineraryId' => $id,
    //         'updatedFields' => $validatedData
    //     ]);

    //     return response()->json([
    //         'message' => 'SingleItinerary updated successfully.',
    //         'data' => $singleItinerary
    //     ]);
    // }


// public function update(Request $request, $id)
// {
//     $authUser = $request->user();

//     Log::info('update() called in SingleItineraryController', [
//         'auth_id' => optional($authUser)->id,
//         'singleItineraryId' => $id
//     ]);

//     if (!$authUser) {
//         return response()->json(['message' => 'Unauthorized'], 401);
//     }

//     // Check Admin
//     $isAdmin = AdminData::where('id', $authUser->id)->exists();

//     // Find Single Itinerary
//     $singleItinerary = SingleItineraryData::find($id);
//     if (!$singleItinerary) {
//         return response()->json(['message' => 'SingleItinerary not found'], 404);
//     }

//     // USER authorization
//     if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
//         return response()->json(['message' => 'Unauthorized access'], 403);
//     }

//     // Validate request
//     $validatedData = $request->validate([
//         'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
//         'approvelStatus'  => 'nullable|string|max:255',
//         'emissionOffset'  => 'required|numeric|min:0',
//         'treesPlanted'    => 'required|integer|min:0',
//     ]);

//     // Fetch Itinerary
//     $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
//     if (!$itinerary) {
//         return response()->json(['message' => 'Itinerary not found'], 404);
//     }

//     // USER restriction for itinerary
//     if (!$isAdmin && $itinerary->userId !== $authUser->userId) {
//         return response()->json(['message' => 'Unauthorized itinerary access'], 403);
//     }

//     DB::transaction(function () use ($validatedData, $singleItinerary, $itinerary) {

//         // STEP 1: Store old values
//         $oldOffset = $singleItinerary->emissionOffset ?? 0;
//         $oldTrees  = $singleItinerary->treesPlanted ?? 0;

//         // STEP 2: Update SingleItinerary
//         $singleItinerary->update([
//             'approvelStatus' => $validatedData['approvelStatus'] ?? $singleItinerary->approvelStatus,
//             'emissionOffset' => $validatedData['emissionOffset'],
//             'treesPlanted'   => $validatedData['treesPlanted'],
//         ]);

//         // STEP 3: Update Itinerary totals (NO double counting)
//         $newOffsetAmount = ($itinerary->offsetAmount ?? 0) - $oldOffset + $validatedData['emissionOffset'];
//         $newTreeCount    = ($itinerary->numberOfTrees ?? 0) - $oldTrees + $validatedData['treesPlanted'];

//         // STEP 4: Calculate offset percentage
//         $offsetPercentage = 0;
//         if ($itinerary->emission > 0) {
//             $offsetPercentage = round(($newOffsetAmount / $itinerary->emission) * 100, 2);
//         }

//         // STEP 5: Determine status
//         if ($offsetPercentage == 0) {
//             $status = 'pending';
//         } elseif ($offsetPercentage < 100) {
//             $status = 'partial';
//         } else {
//             $status = 'completed';
//         }

//         // STEP 6: Update Itinerary
//         $itinerary->update([
//             'offsetAmount'     => $newOffsetAmount,
//             'offsetPercentage' => $offsetPercentage,
//             'numberOfTrees'    => $newTreeCount,
//             'status'           => $status,
//         ]);
//     });

//     Log::info('SingleItinerary & Itinerary updated successfully', [
//         'singleItineraryId' => $id,
//         'itineraryId' => $validatedData['ItineraryId']
//     ]);

//     return response()->json([
//         'status' => true,
//         'message' => 'Single itinerary and itinerary updated successfully',
//         'data' => $singleItinerary
//     ]);
// }


public function update(Request $request, $id)
{
    $authUser = $request->user();

    Log::info('update() called in SingleItineraryController', [
        'auth_id' => optional($authUser)->id,
        'singleItineraryId' => $id
    ]);

    if (!$authUser) {
        Log::warning('Unauthorized: No auth user in update()', [
            'singleItineraryId' => $id
        ]);
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Check admin
    $isAdmin = AdminData::where('id', $authUser->id)->exists();
    Log::info('Admin check in update()', [
        'user_id' => $authUser->id,
        'isAdmin' => $isAdmin
    ]);

    // Fetch Single Itinerary
    $singleItinerary = SingleItineraryData::find($id);
    if (!$singleItinerary) {
        Log::warning('SingleItinerary not found in update()', [
            'singleItineraryId' => $id
        ]);
        return response()->json(['message' => 'SingleItinerary not found'], 404);
    }

    // Authorization
    if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
        Log::warning('Unauthorized access to SingleItinerary update', [
            'requester_id' => $authUser->id,
            'target_userId' => $singleItinerary->userId
        ]);
        return response()->json(['message' => 'Unauthorized access'], 403);
    }

    /* -------------------------------------------------
       STEP 1: CONDITIONAL VALIDATION
    ------------------------------------------------- */

    $approvalStatus = $request->input('approvelStatus');
    Log::info('ApprovelStatus checked', [
        'approvalStatus' => $approvalStatus
    ]);

    if ($approvalStatus === 'Completed') {

        $validatedData = $request->validate([
            'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
            'approvelStatus' => 'required|string|in:Completed',
            'emissionOffset' => 'required|numeric|min:0',
            'treesPlanted'   => 'required|integer|min:0',
            'count'          => 'required|integer|min:0',
        ]);
        Log::info('Validation passed for Completed status', ['validatedData' => $validatedData]);

    } else {

        $validatedData = $request->validate([
            'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
            'approvelStatus' => 'required|string|in:Rejected',
            'note'           => 'required|string|max:1000',
            'count'          => 'required|integer|min:0',
        ]);
        Log::info('Validation passed for Rejected status', ['validatedData' => $validatedData]);
    }

    /* -------------------------------------------------
       STEP 2: FETCH ITINERARY
    ------------------------------------------------- */

    $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
    if (!$itinerary) {
        Log::warning('Itinerary not found in update()', [
            'ItineraryId' => $validatedData['ItineraryId']
        ]);
        return response()->json(['message' => 'Itinerary not found'], 404);
    }

    if (!$isAdmin && $itinerary->userId !== $authUser->userId) {
        Log::warning('Unauthorized itinerary access in update()', [
            'requester_id' => $authUser->id,
            'itinerary_userId' => $itinerary->userId
        ]);
        return response()->json(['message' => 'Unauthorized itinerary access'], 403);
    }

    /* -------------------------------------------------
       STEP 3: TRANSACTION
    ------------------------------------------------- */

    DB::transaction(function () use (
        $validatedData,
        $singleItinerary,
        $itinerary,
        $approvalStatus,
        $id // include $id in closure scope for logging
    ) {

        // Always update approval status
        $singleItinerary->approvelStatus = $validatedData['approvelStatus'];
        Log::info('Updating approvelStatus for SingleItinerary', [
            'singleItineraryId' => $singleItinerary->id,
            'approvelStatus' => $validatedData['approvelStatus']
        ]);

        // if (isset($validatedData['note'])) {
        //     $singleItinerary->note = $validatedData['note'];
        // }
        // if (array_key_exists('count', $validatedData)) {
        //     $singleItinerary->count = $validatedData['count'];
        // }

        // COMPLETED → calculations
        if ($approvalStatus === 'Completed') {

            // Old values (to prevent double counting)
            $oldOffset = $singleItinerary->emissionOffset ?? 0;
            $oldTrees  = $singleItinerary->treesPlanted ?? 0;

            Log::info('Old emissionOffset and treesPlanted for SingleItinerary', [
                'oldOffset' => $oldOffset,
                'oldTrees' => $oldTrees
            ]);

            // Update single itinerary
            $singleItinerary->emissionOffset = $validatedData['emissionOffset'];
            $singleItinerary->treesPlanted   = $validatedData['treesPlanted'];
            $singleItinerary->save();

            Log::info('Updated SingleItinerary emissionOffset and treesPlanted', [
                'newEmissionOffset' => $singleItinerary->emissionOffset,
                'newTreesPlanted'   => $singleItinerary->treesPlanted,
            ]);

            // Recalculate itinerary totals
            $newOffsetAmount = ($itinerary->offsetAmount ?? 0)
                                - $oldOffset
                                + $validatedData['emissionOffset'];

            $newTreeCount = ($itinerary->numberOfTrees ?? 0)
                                - $oldTrees
                                + $validatedData['treesPlanted'];

            Log::info('Recalculated itinerary totals', [
                'previousOffsetAmount' => $itinerary->offsetAmount ?? 0,
                'newOffsetAmount' => $newOffsetAmount,
                'previousNumberOfTrees' => $itinerary->numberOfTrees ?? 0,
                'newTreeCount' => $newTreeCount
            ]);

            // Offset percentage
            $offsetPercentage = 0;
            if ($itinerary->emission > 0) {
                $offsetPercentage = min(
                    round(($newOffsetAmount / $itinerary->emission) * 100, 2),
                    100
                );
            }

            Log::info('Calculated offsetPercentage', [
                'emission' => $itinerary->emission,
                'offsetPercentage' => $offsetPercentage
            ]);

            // Status logic
            if ($offsetPercentage == 0) {
                $itineraryStatus = 'pending';
            } elseif ($offsetPercentage < 100) {
                $itineraryStatus = 'partial';
            } else {
                $itineraryStatus = 'completed';
            }

            Log::info('Determined itinerary status', [
                'itineraryStatus' => $itineraryStatus
            ]);

            // Update itinerary
            $itinerary->update([
                'offsetAmount'     => $newOffsetAmount,
                'offsetPercentage' => $offsetPercentage,
                'numberOfTrees'    => $newTreeCount,
                'status'           => $itineraryStatus,
            ]);
            Log::info('Itinerary updated', [
                'ItineraryId' => $itinerary->ItineraryId,
                'updatedFields' => [
                    'offsetAmount'     => $newOffsetAmount,
                    'offsetPercentage' => $offsetPercentage,
                    'numberOfTrees'    => $newTreeCount,
                    'status'           => $itineraryStatus,
                ]
            ]);

        } else {
            // Not completed → only save status/note
            $singleItinerary->save();
            Log::info('SingleItinerary status updated without calculations', [
                'singleItineraryId' => $singleItinerary->id,
                'approvelStatus' => $singleItinerary->approvelStatus,
            ]);
        }
    });

    Log::info('SingleItinerary update completed', [
        'singleItineraryId' => $id,
        'approvelStatus' => $approvalStatus
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Single itinerary updated successfully',
        'data' => $singleItinerary
    ]);
}

    



    // public function destroy(Request $request, $id)
    // {
    //     $user = $request->user();
    //     Log::info('destroy() called in SingleItineraryController', [
    //         'user' => $user,
    //         'singleItineraryId' => $id
    //     ]);
    //     if (!$user) {
    //         Log::warning('Unauthorized access attempt in destroy()');
    //         return response()->json(['message' => 'Unauthorized.'], 401);
    //     }

    //     $singleItinerary = SingleItineraryData::find($id);

    //     if (!$singleItinerary) {
    //         Log::warning('SingleItinerary not found in destroy()', ['id' => $id]);
    //         return response()->json(['message' => 'SingleItinerary not found.'], 404);
    //     }

    //     if ($singleItinerary->userId !== $user->userId) {
    //         Log::warning('Unauthorized delete attempt on SingleItinerary', [
    //             'userId' => $user->userId,
    //             'ownerUserId' => $singleItinerary->userId
    //         ]);
    //         return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
    //     }

    //     if ($singleItinerary->certificateFile && Storage::disk('public')->exists($singleItinerary->certificateFile)) {
    //         Storage::disk('public')->delete($singleItinerary->certificateFile);
    //         Log::info('Certificate file deleted in destroy()', ['certificateFile' => $singleItinerary->certificateFile]);
    //     }

    //     $singleItinerary->delete();
    //     Log::info('SingleItinerary deleted', ['singleItineraryId' => $id]);

    //     return response()->json(['message' => 'SingleItinerary deleted successfully.']);
    // }

    public function destroy(Request $request, $id)
{
    Log::info('Admin destroy() called for SingleItinerary', [
        'admin_auth_id' => optional($request->user())->id,
        'singleItineraryId' => $id
    ]);

    // Get authenticated admin
    $admin = $request->user();

    if (!$admin) {
        Log::warning('Unauthenticated admin access attempt in destroy()');
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    // Verify admin
    if (!AdminData::where('id', $admin->id)->exists()) {
        Log::warning('Non-admin attempted to delete SingleItinerary', [
            'auth_id' => $admin->id
        ]);

        return response()->json([
            'message' => 'Unauthorized - Not an admin'
        ], 403);
    }

    // Find single itinerary by ID
    $singleItinerary = SingleItineraryData::find($id);

    if (!$singleItinerary) {
        Log::warning('SingleItinerary not found for admin delete', [
            'singleItineraryId' => $id
        ]);

        return response()->json([
            'status' => false,
            'message' => 'SingleItinerary not found'
        ], 404);
    }

    // Delete certificate file if exists
    if (
        $singleItinerary->certificateFile &&
        Storage::disk('public')->exists($singleItinerary->certificateFile)
    ) {
        Storage::disk('public')->delete($singleItinerary->certificateFile);

        Log::info('Certificate file deleted by admin', [
            'certificateFile' => $singleItinerary->certificateFile
        ]);
    }

    // Delete record
    $singleItinerary->delete();

    Log::info('SingleItinerary deleted successfully by admin', [
        'admin_id' => $admin->id,
        'singleItineraryId' => $id
    ]);

    return response()->json([
        'status' => true,
        'message' => 'SingleItinerary deleted successfully'
    ]);
}

}
