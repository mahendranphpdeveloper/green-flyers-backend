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
            'status' => true,
            'message' => 'No records found'
        ]);
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

    // 1. public function store(Request $request)
    // {
    //     $authUser = $request->user();
    //     Log::info('store() called in SingleItineraryController', ['user' => $authUser, 'request' => $request->all()]);

    //     if (!$authUser) {
    //         Log::warning('Unauthorized access attempt in store()');
    //         return response()->json(['status' => false, 'message' => 'Unauthorized.'], 401);
    //     }

    //     // Check if admin
    //     $isAdmin = \App\Models\AdminData::where('id', $authUser->id)->exists();

    //     // Validate input
    //     $validatedData = $request->validate([
    //         'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
    //         'uploadDate'      => 'nullable|date',
    //         'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    //         'approvelStatus'  => 'nullable|string|max:255',
    //         'emissionOffset'  => 'nullable|numeric',
    //         'treesPlanted'    => 'nullable|integer',
    //         'projectTypes'    => 'nullable|string|max:255',
    //         'comments'        => 'nullable|string|max:1000',
    //     ]);
    //     Log::info('Validated data in store()', ['validatedData' => $validatedData]);

    //     // Get the itinerary
    //     $itinerary = \App\Models\ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();

    //     if (!$itinerary) {
    //         Log::warning('Itinerary not found in store()', [
    //             'ItineraryId' => $validatedData['ItineraryId']
    //         ]);
    //         return response()->json(['status' => false, 'message' => 'Itinerary not found.'], 404);
    //     }

    //     // Only allow non-admins to add for their own itinerary
    //     if (!$isAdmin && $itinerary->userId !== $authUser->userId) {
    //         Log::warning('Attempt to store SingleItinerary with unauthorized ItineraryId', [
    //             'userId' => $authUser->userId,
    //             'ItineraryId' => $validatedData['ItineraryId']
    //         ]);
    //         return response()->json(['status' => false, 'message' => 'Unauthorized: ItineraryId does not belong to this user.'], 403);
    //     }

    //     if ($request->hasFile('certificateFile')) {
    //         $file = $request->file('certificateFile');
    //         $path = $file->store('certificates', 'public');
    //         $validatedData['certificateFile'] = $path;
    //         Log::info('Certificate file uploaded in store()', ['path' => $path]);
    //     } else {
    //         unset($validatedData['certificateFile']);
    //     }

    //     // For admins, allow specifying any userId, else enforce their own userId
    //     $validatedData['userId'] = $isAdmin && isset($itinerary->userId)
    //         ? $itinerary->userId
    //         : $authUser->userId;

    //     $singleItinerary = \App\Models\SingleItineraryData::create($validatedData);

    //     Log::info('SingleItinerary created', ['singleItinerary' => $singleItinerary]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'SingleItinerary created successfully.',
    //         'data' => $singleItinerary
    //     ]);
    // }

    // 2. public function store(Request $request)
    // {
    //     $authUser = $request->user();
    
    //     Log::info('store() called in SingleItineraryController', [
    //         'user'    => $authUser,
    //         'request' => $request->all()
    //     ]);
    
    //     if (!$authUser) {
    //         Log::warning('Unauthorized access attempt in store()');
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Unauthorized.'
    //         ], 401);
    //     }
    
    //     /** ---------------- ADMIN CHECK ---------------- */
    //     $isAdmin = AdminData::where('id', $authUser->id)->exists();
    
    //     /** ---------------- VALIDATION ---------------- */
    //     $validatedData = $request->validate([
    //         'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
    //         'uploadDate'      => 'nullable|date',
    //         'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    //         'approvelStatus'  => 'nullable|string', // Removed `in:` rule
    //         'emissionOffset'  => 'nullable|integer|min:0',
    //         'projectTypes'    => 'nullable|string|max:255',
    //         'comments'        => 'nullable|string|max:1000',
    //         'count'           => 'nullable|integer|min:0',
    //         'note'            => 'nullable|string|max:1000',
    //     ]);
    
    //     Log::info('Validated data in store()', $validatedData);
    
    //     /** ---------------- ITINERARY ---------------- */
    //     $itinerary = ItineraryData::where(
    //         'ItineraryId',
    //         $validatedData['ItineraryId']
    //     )->first();
    
    //     if (!$itinerary) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Itinerary not found.'
    //         ], 404);
    //     }
    
    //     /** ---------------- OWNERSHIP CHECK ---------------- */
    //     if (!$isAdmin && $itinerary->userId !== $authUser->userId) {
    //         Log::warning('Unauthorized itinerary access', [
    //             'userId'      => $authUser->userId,
    //             'ItineraryId' => $validatedData['ItineraryId']
    //         ]);
    
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Unauthorized: Itinerary does not belong to this user.'
    //         ], 403);
    //     }
    
    //     /** ---------------- FILE UPLOAD ---------------- */
    //     if ($request->hasFile('certificateFile')) {
    //         $path = $request->file('certificateFile')
    //             ->store('certificates', 'public');
    
    //         $validatedData['certificateFile'] = $path;
    //     }
    
    //     /** ---------------- USER ID ---------------- */
    //     $validatedData['userId'] = $isAdmin
    //         ? $itinerary->userId
    //         : $authUser->userId;
    
    //     /** ---------------- DEFAULTS ---------------- */
    //     $approvalStatus   = $validatedData['approvelStatus'] ?? null;
    //     $requestedOffset  = (int) ($validatedData['emissionOffset'] ?? 0);
    //     $offsetCreditAdded = 0;
    
    //     DB::transaction(function () use (
    //         $validatedData,
    //         $approvalStatus,
    //         $requestedOffset,
    //         $itinerary,
    //         &$offsetCreditAdded
    //     ) {
    
    //         /** ---------------- CREATE SINGLE ITINERARY ---------------- */
    //         $singleItinerary = new SingleItineraryData($validatedData);
    
    //         /** If not completed → just save */
    //         if ($approvalStatus !== 'Completed') {
    //             $singleItinerary->save();
    //             return;
    //         }
    
    //         /** ---------------- EMISSION LIMITS ---------------- */
    //         $emissionLimit = $itinerary->emission;
    //         $currentOffset = $itinerary->offsetAmount ?? 0;
    
    //         $remainingEmission = max(
    //             $emissionLimit - $currentOffset,
    //             0
    //         );
    
    //         /** ---------------- APPLY OFFSET ---------------- */
    //         $appliedOffset = min($requestedOffset, $remainingEmission);
    //         $extraOffset   = $requestedOffset - $appliedOffset;
    
    //         /** ---------------- SAVE SINGLE ITINERARY ---------------- */
    //         $singleItinerary->emissionOffset = $appliedOffset;
    //         $singleItinerary->treesPlanted   = intdiv($appliedOffset, 50);
    //         $singleItinerary->save();
    
    //         /** ---------------- UPDATE MASTER ITINERARY ---------------- */
    //         $newOffset = $currentOffset + $appliedOffset;
    //         $newTrees  = intdiv($newOffset, 50);
    
    //         $offsetPercentage = $emissionLimit > 0
    //             ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
    //             : 0;
    
    //         $status = match (true) {
    //             $offsetPercentage == 0  => 'pending',
    //             $offsetPercentage < 100 => 'partial',
    //             default                 => 'completed',
    //         };
    
    //         $itinerary->update([
    //             'offsetAmount'     => $newOffset,
    //             'numberOfTrees'    => $newTrees,
    //             'offsetPercentage' => $offsetPercentage,
    //             'status'           => $status
    //         ]);
    
    //         /** ---------------- USER CREDIT ---------------- */
    //         if ($extraOffset > 0) {
    //             $user = User::where('userId', $itinerary->userId)
    //                 ->lockForUpdate()
    //                 ->first();
    
    //             $user->offsetCredit += $extraOffset;
    //             $user->save();
    
    //             $offsetCreditAdded = $user->offsetCredit;
    //         }
    //     });
    
    //     return response()->json([
    //         'status'            => true,
    //         'message'           => 'SingleItinerary created successfully.',
    //         'offsetCreditAdded' => $offsetCreditAdded,
    //         'info'              => 'Extra offset stored as user credit (if any)'
    //     ]);
    // }
    
//    3. public function store(Request $request)
// {
//     $authUser = $request->user();

//     Log::info('store() called in SingleItineraryController', [
//         'user'    => $authUser,
//         'request' => $request->all()
//     ]);

//     if (!$authUser) {
//         Log::warning('Unauthorized access attempt in store()');
//         return response()->json([
//             'status'  => false,
//             'message' => 'Unauthorized.'
//         ], 401);
//     }

//     /** ---------------- ADMIN CHECK ---------------- */
//     $isAdmin = AdminData::where('id', $authUser->id)->exists();

//     /** ---------------- VALIDATION ---------------- */
//     $validatedData = $request->validate([
//         'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
//         'uploadDate'      => 'nullable|date',
//         'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
//         'approvelStatus'  => 'nullable|string', // Removed `in:` rule
//         'emissionOffset'  => 'nullable|integer|min:0',
//         'projectTypes'    => 'nullable|string|max:255',
//         'comments'        => 'nullable|string|max:1000',
//         'count'           => 'nullable|integer|min:0',
//         'note'            => 'nullable|string|max:1000',
//     ]);

//     Log::info('Validated data in store()', $validatedData);

//     /** ---------------- ITINERARY ---------------- */
//     $itinerary = ItineraryData::where(
//         'ItineraryId',
//         $validatedData['ItineraryId']
//     )->first();

//     if (!$itinerary) {
//         return response()->json([
//             'status'  => false,
//             'message' => 'Itinerary not found.'
//         ], 404);
//     }

//     /** ---------------- OWNERSHIP CHECK ---------------- */
//     if (!$isAdmin && $itinerary->userId !== $authUser->userId) {
//         Log::warning('Unauthorized itinerary access', [
//             'userId'      => $authUser->userId,
//             'ItineraryId' => $validatedData['ItineraryId']
//         ]);

//         return response()->json([
//             'status'  => false,
//             'message' => 'Unauthorized: Itinerary does not belong to this user.'
//         ], 403);
//     }

//     /** ---------------- FILE UPLOAD ---------------- */
//     if ($request->hasFile('certificateFile')) {
//         $path = $request->file('certificateFile')
//             ->store('certificates', 'public');

//         $validatedData['certificateFile'] = $path;
//     }

//     /** ---------------- USER ID ---------------- */
//     $validatedData['userId'] = $isAdmin
//         ? $itinerary->userId
//         : $authUser->userId;

//     /** ---------------- DEFAULTS ---------------- */
//     $approvalStatus   = $validatedData['approvelStatus'] ?? null;
//     $requestedOffset  = (int) ($validatedData['emissionOffset'] ?? 0);
//     $offsetCreditAdded = 0;

//     DB::transaction(function () use (
//         $validatedData,
//         $approvalStatus,
//         $requestedOffset,
//         $itinerary,
//         &$offsetCreditAdded
//     ) {

//         /** ---------------- CREATE SINGLE ITINERARY ---------------- */
//         $singleItinerary = new SingleItineraryData($validatedData);

//         /** If not completed → just save */
//         if ($approvalStatus !== 'Completed') {
//             $singleItinerary->save();
//             return;
//         }

//         /** ---------------- EMISSION LIMITS ---------------- */
//         $emissionLimit = $itinerary->emission;
//         $currentOffset = $itinerary->offsetAmount ?? 0;

//         $remainingEmission = max(
//             $emissionLimit - $currentOffset,
//             0
//         );

//         /** ---------------- APPLY OFFSET ---------------- */
//         $appliedOffset = min($requestedOffset, $remainingEmission);
//         $extraOffset   = $requestedOffset - $appliedOffset;

//         /** ---------------- SAVE SINGLE ITINERARY ---------------- */
//         $singleItinerary->emissionOffset = $appliedOffset;
//         $singleItinerary->treesPlanted   = intdiv($appliedOffset, 50);
//         $singleItinerary->save();

//         /** ---------------- UPDATE MASTER ITINERARY ---------------- */
//         $newOffset = $currentOffset + $appliedOffset;
//         $newTrees  = intdiv($newOffset, 50);

//         $offsetPercentage = $emissionLimit > 0
//             ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
//             : 0;

//         $status = match (true) {
//             $offsetPercentage == 0  => 'pending',
//             $offsetPercentage < 100 => 'partial',
//             default                 => 'completed',
//         };

//         $itinerary->update([
//             'offsetAmount'     => $newOffset,
//             'numberOfTrees'    => $newTrees,
//             'offsetPercentage' => $offsetPercentage,
//             'status'           => $status
//         ]);

//         /** ---------------- USER CREDIT ---------------- */
//         $user = User::where('userId', $itinerary->userId)
//             ->lockForUpdate()
//             ->first();

//         if ($extraOffset > 0) {
//             // Add extraOffset to user's offsetCredit
//             $user->offsetCredit = ($user->offsetCredit ?? 0) + $extraOffset;
//             $user->save();

//             $offsetCreditAdded = $extraOffset; // only the amount added
//         } else {
//             $offsetCreditAdded = 0;
//         }
//     });

//     return response()->json([
//         'status'            => true,
//         'message'           => 'SingleItinerary created successfully.',
//         'offsetCreditAdded' => $offsetCreditAdded,
//         'info'              => 'Extra offset stored as user credit (if any)'
//     ]);
// }
public function store(Request $request)
{
    $authUser = $request->user();

    Log::info('store() called in SingleItineraryController', [
        'user'    => $authUser,
        'request' => $request->all()
    ]);

    if (!$authUser) {
        Log::warning('Unauthorized access attempt in store()');
        return response()->json([
            'status'  => false,
            'message' => 'Unauthorized.'
        ], 401);
    }

    /** ---------------- ADMIN CHECK ---------------- */
    $isAdmin = AdminData::where('id', $authUser->id)->exists();

    /** ---------------- VALIDATION ---------------- */
    $validatedData = $request->validate([
        'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
        'uploadDate'      => 'nullable|date',
        'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        'approvelStatus'  => 'nullable|string',
        'emissionOffset'  => 'nullable|integer|min:0',
        'projectTypes'    => 'nullable|string|max:255',
        'comments'        => 'nullable|string|max:1000',
        'count'           => 'nullable|integer|min:0',
        'note'            => 'nullable|string|max:1000',
    ]);

    Log::info('Validated data in store()', $validatedData);

    /** ---------------- ITINERARY ---------------- */
    $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();

    if (!$itinerary) {
        return response()->json([
            'status'  => false,
            'message' => 'Itinerary not found.'
        ], 404);
    }

    /** ---------------- OWNERSHIP CHECK ---------------- */
    if (!$isAdmin && $itinerary->userId !== $authUser->userId) {
        Log::warning('Unauthorized itinerary access', [
            'userId'      => $authUser->userId,
            'ItineraryId' => $validatedData['ItineraryId']
        ]);

        return response()->json([
            'status'  => false,
            'message' => 'Unauthorized: Itinerary does not belong to this user.'
        ], 403);
    }

    /** ---------------- FILE UPLOAD (OLD FORMAT) ---------------- */
    if ($request->hasFile('certificateFile')) {
        $path = $request->file('certificateFile')
            ->store('certificates', 'public'); // stored in storage/app/public/certificates

        $validatedData['certificateFile'] = $path;
    }

    /** ---------------- USER ID ---------------- */
    $validatedData['userId'] = $isAdmin ? $itinerary->userId : $authUser->userId;

    /** ---------------- DEFAULTS ---------------- */
    $approvalStatus    = $validatedData['approvelStatus'] ?? null;
    $requestedOffset   = (int) ($validatedData['emissionOffset'] ?? 0);
    $offsetCreditAdded = 0;

    DB::transaction(function () use (
        $validatedData,
        $approvalStatus,
        $requestedOffset,
        $itinerary,
        $isAdmin,
        &$offsetCreditAdded
    ) {

        /** ---------------- CREATE SINGLE ITINERARY ---------------- */
        $singleItinerary = new SingleItineraryData($validatedData);

        /** ---------------- FETCH USER ---------------- */
        $user = User::where('userId', $itinerary->userId)->lockForUpdate()->first();

        /** ---------------- USE USER OFFSET CREDIT ---------------- */
        $userCredit = $user->offsetCredit ?? 0;
        $creditUsed = min($requestedOffset, $userCredit);
        $requestedOffset -= $creditUsed;
        $user->offsetCredit = $userCredit - $creditUsed;
        $user->save();

        /** ---------------- IF NOT COMPLETED ---------------- */
        if ($approvalStatus !== 'Completed') {
            $singleItinerary->save();
            $offsetCreditAdded = 0;
            return;
        }

        /** ---------------- EMISSION LIMITS ---------------- */
        $emissionLimit = $itinerary->emission;
        $currentOffset = $itinerary->offsetAmount ?? 0;
        $remainingEmission = max($emissionLimit - $currentOffset, 0);

        /** ---------------- APPLY OFFSET ---------------- */
        $appliedOffset = min($requestedOffset, $remainingEmission);
        $extraOffset   = $requestedOffset - $appliedOffset;

        /** ---------------- SAVE SINGLE ITINERARY ---------------- */
        $singleItinerary->emissionOffset = $appliedOffset + $creditUsed;
        $singleItinerary->treesPlanted   = intdiv($singleItinerary->emissionOffset, 50);
        $singleItinerary->save();

        /** ---------------- UPDATE MASTER ITINERARY ---------------- */
        $newOffset = $currentOffset + $appliedOffset;
        $newTrees  = intdiv($newOffset, 50);

        $offsetPercentage = $emissionLimit > 0 ? min(round(($newOffset / $emissionLimit) * 100, 2), 100) : 0;

        $status = match (true) {
            $offsetPercentage == 0  => 'pending',
            $offsetPercentage < 100 => 'partial',
            default                 => 'completed',
        };

        $itinerary->update([
            'offsetAmount'     => $newOffset,
            'numberOfTrees'    => $newTrees,
            'offsetPercentage' => $offsetPercentage,
            'status'           => $status
        ]);

        /** ---------------- ADD EXTRA OFFSET TO USER CREDIT ---------------- */
        if ($extraOffset > 0) {
            $user->offsetCredit = ($user->offsetCredit ?? 0) + $extraOffset;
            $user->save();
            $offsetCreditAdded = $extraOffset;
        } else {
            $offsetCreditAdded = 0;
        }
    });

    return response()->json([
        'status'            => true,
        'message'           => 'SingleItinerary created successfully.',
        'offsetCreditAdded' => $offsetCreditAdded,
        'info'              => 'Extra offset stored as user credit (if any)'
    ]);
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

    // public function getByUserAndItinerary(Request $request, $userId, $ItineraryId)
    // {
    //     Log::info('getByUserAndItinerary() called', ['requestedUserId' => $userId, 'requestedItineraryId' => $ItineraryId]);
    //     $user = $request->user();
    //     if (!$user) {
    //         Log::warning('Unauthorized access attempt in getByUserAndItinerary()');
    //         return response()->json(['message' => 'Unauthorized.'], 401);
    //     }
    //     if ($user->userId != $userId) {
    //         Log::warning('Access denied in getByUserAndItinerary()', [
    //             'loginUserId' => $user->userId,
    //             'requestedUserId' => $userId
    //         ]);
    //         return response()->json(['message' => 'Unauthorized: You do not have access to this user\'s resources.'], 403);
    //     }

    //     $singleItineraries = SingleItineraryData::where('userId', $userId)
    //         ->where('ItineraryId', $ItineraryId)
    //         ->get();

    //     if ($singleItineraries->isEmpty()) {
    //         Log::warning("No records found for getByUserAndItinerary()", [
    //             'userId' => $userId,
    //             'ItineraryId' => $ItineraryId
    //         ]);
    //         return response()->json(['message' => 'No records found for this user and itinerary.'], 404);
    //     }

    //     Log::info('getByUserAndItinerary() returning records', [
    //         'count' => $singleItineraries->count(),
    //         'userId' => $userId,
    //         'ItineraryId' => $ItineraryId
    //     ]);
    //     return response()->json($singleItineraries);
    // }

    public function getByUserAndItinerary(Request $request, $userId, $ItineraryId)
{
    Log::info('getByUserAndItinerary() called', [
        'requestedUserId'      => $userId,
        'requestedItineraryId'=> $ItineraryId
    ]);

    $authUser = $request->user();

    if (!$authUser) {
        Log::warning('Unauthorized access attempt');
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    /** CHECK ADMIN */
    $isAdmin = AdminData::where('id', $authUser->id)->exists();

    /** AUTHORIZATION */
    if (!$isAdmin && $authUser->userId != $userId) {
        Log::warning('Access denied', [
            'loginUserId'     => $authUser->userId,
            'requestedUserId'=> $userId
        ]);
        return response()->json([
            'message' => 'Unauthorized: You do not have access to this resource.'
        ], 403);
    }

    /** FETCH DATA */
    $singleItineraries = SingleItineraryData::where('userId', $userId)
        ->where('ItineraryId', $ItineraryId)
        ->get();

    if ($singleItineraries->isEmpty()) {
        Log::warning('No records found', [
            'userId'       => $userId,
            'ItineraryId'  => $ItineraryId
        ]);
        return response()->json([
            'message' => 'No records found for this user and itinerary.'
        ], 404);
    }

    Log::info('Records returned successfully', [
        'count' => $singleItineraries->count(),
        'userId'=> $userId,
        'ItineraryId' => $ItineraryId
    ]);

    return response()->json([
        'status' => true,
        'data'   => $singleItineraries
    ]);
}



// 1. public function update(Request $request, $id)
// {
//     $authUser = $request->user();
//     if (!$authUser) {
//         return response()->json(['message' => 'Unauthorized'], 401);
//     }

//     $isAdmin = AdminData::where('id', $authUser->id)->exists();

//     $singleItinerary = SingleItineraryData::find($id);
//     if (!$singleItinerary) {
//         return response()->json(['message' => 'SingleItinerary not found'], 404);
//     }

//     if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
//         return response()->json(['message' => 'Unauthorized access'], 403);
//     }

//     $approvalStatus = $request->input('approvelStatus');

//     if ($approvalStatus === 'Completed') {
//         $validatedData = $request->validate([
//             'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
//             'approvelStatus' => 'required|in:Completed',
//             'emissionOffset' => 'required|integer|min:0',
//             'treesPlanted'   => 'required|integer|min:0',
//             'count'          => 'required|integer|min:0',
//         ]);
//     } else {
//         $validatedData = $request->validate([
//             'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
//             'approvelStatus' => 'required|in:Rejected',
//             'note'           => 'required|string',
//             'count'          => 'required|integer|min:0',
//         ]);
//     }

//     $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
//     if (!$itinerary) {
//         return response()->json(['message' => 'Itinerary not found'], 404);
//     }

//     $autoApplied = [];

//     DB::transaction(function () use (
//         $validatedData,
//         $singleItinerary,
//         $itinerary,
//         $approvalStatus,
//         $authUser,
//         &$autoApplied // pass by reference to populate array
//     ) {

//         $singleItinerary->approvelStatus = $validatedData['approvelStatus'];
//         $singleItinerary->count          = $validatedData['count'];

//         if (isset($validatedData['note'])) {
//             $singleItinerary->note = $validatedData['note'];
//         }

//         if ($approvalStatus !== 'Completed') {
//             $singleItinerary->save();
//             return;
//         }

//         /** OLD VALUES */
//         $oldOffset = $singleItinerary->emissionOffset ?? 0;
//         $oldTrees  = $singleItinerary->treesPlanted ?? 0;

//         /** REQUEST VALUES */
//         $requestedOffset = (int) $validatedData['emissionOffset'];
//         $requestedTrees  = (int) $validatedData['treesPlanted'];

//         /** ITINERARY LIMITS */
//         $emissionLimit = $itinerary->emission;
//         $treeLimit     = $itinerary->totalTrees;

//         /** CURRENT TOTALS */
//         $currentOffset = $itinerary->offsetAmount ?? 0;
//         $currentTrees  = $itinerary->numberOfTrees ?? 0;

//         /** REMAINING CAPACITY */
//         $remainingEmission = max($emissionLimit - ($currentOffset - $oldOffset), 0);
//         $remainingTrees    = max($treeLimit - ($currentTrees - $oldTrees), 0);

//         /** APPLY DIRECT OFFSET */
//         $appliedOffset = min($requestedOffset, $remainingEmission);
//         $appliedTrees  = min($requestedTrees, $remainingTrees);

//         $extraOffset = $requestedOffset - $appliedOffset;
//         $extraTrees  = $requestedTrees - $appliedTrees;

//         /** SAVE SINGLE ITINERARY */
//         $singleItinerary->update([
//             'emissionOffset' => $appliedOffset,
//             'treesPlanted'   => $appliedTrees
//         ]);

//         /** UPDATE ITINERARY TOTALS */
//         $newOffset = ($currentOffset - $oldOffset) + $appliedOffset;
//         $newTrees  = ($currentTrees - $oldTrees) + $appliedTrees;

//         $offsetPercentage = $emissionLimit > 0
//             ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
//             : 0;

//         $status = match (true) {
//             $offsetPercentage == 0  => 'pending',
//             $offsetPercentage < 100 => 'partial',
//             default                 => 'completed',
//         };

//         $itinerary->update([
//             'offsetAmount'     => $newOffset,
//             'numberOfTrees'    => $newTrees,
//             'offsetPercentage' => $offsetPercentage,
//             'status'           => $status
//         ]);

//         /** STORE CREDIT IN USER */
//         $user = User::where('userId', $itinerary->userId)->lockForUpdate()->first();
//         $user->offsetCredit += $extraOffset;
//         $user->treeCredit  += $extraTrees;
//         $user->save();

//         /** AUTO-ALLOCATE USER CREDIT TO NEXT ITINERARIES (DATE WISE) */
//         $eligibleItineraries = ItineraryData::where('userId', $user->userId)
//             ->whereIn('status', ['pending', 'partial'])
//             ->orderBy('date', 'asc')
//             ->lockForUpdate()
//             ->get();

//         foreach ($eligibleItineraries as $nextItinerary) {

//             if ($user->offsetCredit <= 0 && $user->treeCredit <= 0) {
//                 break;
//             }

//             $remainingEmission = max($nextItinerary->emission - $nextItinerary->offsetAmount, 0);
//             $remainingTrees    = max($nextItinerary->totalTrees - $nextItinerary->numberOfTrees, 0);

//             $useOffset = min($remainingEmission, $user->offsetCredit);
//             $useTrees  = min($remainingTrees, $user->treeCredit);

//             if ($useOffset > 0 || $useTrees > 0) {

//                 $nextItinerary->offsetAmount  += $useOffset;
//                 $nextItinerary->numberOfTrees += $useTrees;

//                 $percent = min(
//                     round(($nextItinerary->offsetAmount / $nextItinerary->emission) * 100, 2),
//                     100
//                 );

//                 $nextItinerary->status = $percent == 100 ? 'completed' : 'partial';
//                 $nextItinerary->offsetPercentage = $percent;
//                 $nextItinerary->save();

//                 $autoApplied[] = [
//                     'itineraryId' => $nextItinerary->ItineraryId,
//                     'offsetUsed'  => $useOffset,
//                     'treesUsed'   => $useTrees,
//                     'date'        => $nextItinerary->date
//                 ];

//                 $user->offsetCredit -= $useOffset;
//                 $user->treeCredit  -= $useTrees;
//             }
//         }

//         $user->save();
//     });

//     return response()->json([
//         'status' => true,
//         'message' => 'Updated successfully',
//         'autoApplied' => $autoApplied,
//         'info' => 'Unused offset credits were automatically applied to earlier itineraries'
//     ]);
// }

// 2. public function update(Request $request, $id)
// {
//     $authUser = $request->user();
//     if (!$authUser) {
//         return response()->json(['message' => 'Unauthorized'], 401);
//     }

//     $isAdmin = AdminData::where('id', $authUser->id)->exists();

//     $singleItinerary = SingleItineraryData::find($id);
//     if (!$singleItinerary) {
//         return response()->json(['message' => 'SingleItinerary not found'], 404);
//     }

//     if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
//         return response()->json(['message' => 'Unauthorized access'], 403);
//     }

//     $approvalStatus = $request->input('approvelStatus');

//     /** ---------------- VALIDATION ---------------- */
//     if ($approvalStatus === 'Completed') {
//         $validatedData = $request->validate([
//             'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
//             'approvelStatus' => 'required|in:Completed',
//             'emissionOffset' => 'required|integer|min:0',
//             'count'          => 'required|integer|min:0',
//         ]);
//     } else {
//         $validatedData = $request->validate([
//             'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
//             'approvelStatus' => 'required|in:Rejected',
//             'note'           => 'required|string',
//             'count'          => 'required|integer|min:0',
//         ]);
//     }

//     $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
//     if (!$itinerary) {
//         return response()->json(['message' => 'Itinerary not found'], 404);
//     }

//     $autoApplied = [];

//     DB::transaction(function () use (
//         $validatedData,
//         $singleItinerary,
//         $itinerary,
//         $approvalStatus,
//         &$autoApplied
//     ) {

//         /** ---------------- BASIC UPDATE ---------------- */
//         $singleItinerary->approvelStatus = $validatedData['approvelStatus'];
//         $singleItinerary->count = $validatedData['count'];

//         if (isset($validatedData['note'])) {
//             $singleItinerary->note = $validatedData['note'];
//         }

//         if ($approvalStatus !== 'Completed') {
//             $singleItinerary->save();
//             return;
//         }

//         /** ---------------- OLD VALUES ---------------- */
//         $oldOffset = $singleItinerary->emissionOffset ?? 0;

//         /** ---------------- REQUEST ---------------- */
//         $requestedOffset = (int) $validatedData['emissionOffset'];

//         /** ---------------- LIMITS ---------------- */
//         $emissionLimit = $itinerary->emission;
//         $currentOffset = $itinerary->offsetAmount ?? 0;

//         /** ---------------- REMAINING ---------------- */
//         $remainingEmission = max(
//             $emissionLimit - ($currentOffset - $oldOffset),
//             0
//         );

//         /** ---------------- APPLY OFFSET ---------------- */
//         $appliedOffset = min($requestedOffset, $remainingEmission);

//         /** 🌱 TREES DERIVED ONLY FROM OFFSET */
//         $appliedTrees = intdiv($appliedOffset, 50);

//         $extraOffset = $requestedOffset - $appliedOffset;

//         /** ---------------- SAVE SINGLE ITINERARY ---------------- */
//         $singleItinerary->update([
//             'emissionOffset' => $appliedOffset,
//             'treesPlanted'   => $appliedTrees
//         ]);

//         /** ---------------- UPDATE MASTER ITINERARY ---------------- */
//         $newOffset = ($currentOffset - $oldOffset) + $appliedOffset;
//         $newTrees  = intdiv($newOffset, 50);

//         $offsetPercentage = $emissionLimit > 0
//             ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
//             : 0;

//         $status = match (true) {
//             $offsetPercentage == 0  => 'pending',
//             $offsetPercentage < 100 => 'partial',
//             default                 => 'completed',
//         };

//         $itinerary->update([
//             'offsetAmount'     => $newOffset,
//             'numberOfTrees'    => $newTrees,
//             'offsetPercentage' => $offsetPercentage,
//             'status'           => $status
//         ]);

//         /** ---------------- USER OFFSET CREDIT ONLY ---------------- */
//         $user = User::where('userId', $itinerary->userId)
//             ->lockForUpdate()
//             ->first();

//         $user->offsetCredit += $extraOffset;
//         $user->save();

//         /** ---------------- AUTO APPLY OFFSET TO OTHER ITINERARIES ---------------- */
//         $eligibleItineraries = ItineraryData::where('userId', $user->userId)
//             ->whereIn('status', ['pending', 'partial'])
//             ->orderBy('date', 'asc')
//             ->lockForUpdate()
//             ->get();

//         foreach ($eligibleItineraries as $nextItinerary) {

//             if ($user->offsetCredit <= 0) {
//                 break;
//             }

//             $remainingEmission = max(
//                 $nextItinerary->emission - $nextItinerary->offsetAmount,
//                 0
//             );

//             $useOffset = min($remainingEmission, $user->offsetCredit);

//             if ($useOffset > 0) {

//                 $useTrees = intdiv($useOffset, 50);

//                 $nextItinerary->offsetAmount  += $useOffset;
//                 $nextItinerary->numberOfTrees += $useTrees;

//                 $percent = min(
//                     round(($nextItinerary->offsetAmount / $nextItinerary->emission) * 100, 2),
//                     100
//                 );

//                 $nextItinerary->status = $percent == 100 ? 'completed' : 'partial';
//                 $nextItinerary->offsetPercentage = $percent;
//                 $nextItinerary->save();

//                 $autoApplied[] = [
//                     'itineraryId' => $nextItinerary->ItineraryId,
//                     'offsetUsed'  => $useOffset,
//                     'treesAdded'  => $useTrees,
//                     'date'        => $nextItinerary->date
//                 ];

//                 $user->offsetCredit -= $useOffset;
//             }
//         }

//         $user->save();
//     });

//     return response()->json([
//         'status' => true,
//         'message' => 'Updated successfully',
//         'autoApplied' => $autoApplied,
//         'info' => 'Trees are auto-calculated as 1 tree per 50 offset'
//     ]);
// }

// public function update(Request $request, $id)
// {
//     $authUser = $request->user();
//     if (!$authUser) {
//         return response()->json(['message' => 'Unauthorized'], 401);
//     }

//     $isAdmin = AdminData::where('id', $authUser->id)->exists();

//     $singleItinerary = SingleItineraryData::find($id);
//     if (!$singleItinerary) {
//         return response()->json(['message' => 'SingleItinerary not found'], 404);
//     }

//     if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
//         return response()->json(['message' => 'Unauthorized access'], 403);
//     }

//     $approvalStatus = $request->input('approvelStatus');

//     /** ---------------- VALIDATION ---------------- */
//     if ($approvalStatus === 'Completed') {
//         $validatedData = $request->validate([
//             'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
//             'approvelStatus' => 'required|in:Completed',
//             'emissionOffset' => 'required|integer|min:0',
//             'count'          => 'required|integer|min:0',
//         ]);
//     } else {
//         $validatedData = $request->validate([
//             'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
//             'approvelStatus' => 'required|in:Rejected',
//             'note'           => 'required|string',
//             'count'          => 'required|integer|min:0',
//         ]);
//     }

//     $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
//     if (!$itinerary) {
//         return response()->json(['message' => 'Itinerary not found'], 404);
//     }

//     $autoApplied = [];

//     DB::transaction(function () use (
//         $validatedData,
//         $singleItinerary,
//         $itinerary,
//         $approvalStatus,
//         &$autoApplied
//     ) {

//         /** ---------------- BASIC UPDATE ---------------- */
//         $singleItinerary->approvelStatus = $validatedData['approvelStatus'];
//         $singleItinerary->count = $validatedData['count'];

//         if (isset($validatedData['note'])) {
//             $singleItinerary->note = $validatedData['note'];
//         }

//         if ($approvalStatus !== 'Completed') {
//             $singleItinerary->save();
//             return;
//         }

//         /** ---------------- OLD VALUES ---------------- */
//         $oldOffset = $singleItinerary->emissionOffset ?? 0;

//         /** ---------------- REQUEST ---------------- */
//         $requestedOffset = (int) $validatedData['emissionOffset'];

//         /** ---------------- LIMITS ---------------- */
//         $emissionLimit = $itinerary->emission;
//         $currentOffset = $itinerary->offsetAmount ?? 0;

//         /** ---------------- REMAINING ---------------- */
//         $remainingEmission = max(
//             $emissionLimit - ($currentOffset - $oldOffset),
//             0
//         );

//         /** ---------------- APPLY OFFSET ---------------- */
//         $appliedOffset = min($requestedOffset, $remainingEmission);
//         $extraOffset   = $requestedOffset - $appliedOffset;

//         /** ---------------- SAVE SINGLE ITINERARY ---------------- */
//         $singleItinerary->update([
//             'emissionOffset' => $appliedOffset,
//             'treesPlanted'   => intdiv($appliedOffset, 50) // informational only
//         ]);

//         /** ---------------- UPDATE MASTER ITINERARY ---------------- */
//         $newOffset = ($currentOffset - $oldOffset) + $appliedOffset;
//         $newTrees  = intdiv($newOffset, 50);

//         $offsetPercentage = $emissionLimit > 0
//             ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
//             : 0;

//         $status = match (true) {
//             $offsetPercentage == 0  => 'pending',
//             $offsetPercentage < 100 => 'partial',
//             default                 => 'completed',
//         };

//         $itinerary->update([
//             'offsetAmount'     => $newOffset,
//             'numberOfTrees'    => $newTrees,
//             'offsetPercentage' => $offsetPercentage,
//             'status'           => $status
//         ]);

//         /** ---------------- USER OFFSET CREDIT ---------------- */
//         $user = User::where('userId', $itinerary->userId)
//             ->lockForUpdate()
//             ->first();

//         $user->offsetCredit += $extraOffset;
//         $user->save();

//         /** ---------------- AUTO APPLY OFFSET (FIXED) ---------------- */
//         $eligibleItineraries = ItineraryData::where('userId', $user->userId)
//             ->whereIn('status', ['pending', 'partial'])
//             ->orderBy('date', 'asc')
//             ->lockForUpdate()
//             ->get();

//         foreach ($eligibleItineraries as $nextItinerary) {

//             if ($user->offsetCredit <= 0) {
//                 break;
//             }

//             $remainingEmission = max(
//                 $nextItinerary->emission - $nextItinerary->offsetAmount,
//                 0
//             );

//             $useOffset = min($remainingEmission, $user->offsetCredit);

//             if ($useOffset > 0) {

//                 $nextItinerary->offsetAmount += $useOffset;

//                 /** 🌱 TREE = TOTAL OFFSET / 50 */
//                 $nextItinerary->numberOfTrees = intdiv(
//                     $nextItinerary->offsetAmount,
//                     50
//                 );

//                 $percent = min(
//                     round(($nextItinerary->offsetAmount / $nextItinerary->emission) * 100, 2),
//                     100
//                 );

//                 $nextItinerary->status = $percent == 100 ? 'completed' : 'partial';
//                 $nextItinerary->offsetPercentage = $percent;
//                 $nextItinerary->save();

//                 $autoApplied[] = [
//                     'itineraryId' => $nextItinerary->ItineraryId,
//                     'offsetUsed'  => $useOffset,
//                     'treesTotal'  => $nextItinerary->numberOfTrees,
//                     'date'        => $nextItinerary->date
//                 ];

//                 $user->offsetCredit -= $useOffset;
//             }
//         }

//         $user->save();
//     });

//     return response()->json([
//         'status' => true,
//         'message' => 'Updated successfully',
//         'autoApplied' => $autoApplied,
//         'info' => 'Trees are derived from total offset (1 tree per 50)'
//     ]);
// }

public function update(Request $request, $id)
{
    $authUser = $request->user();
    if (!$authUser) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $isAdmin = AdminData::where('id', $authUser->id)->exists();

    $singleItinerary = SingleItineraryData::find($id);
    if (!$singleItinerary) {
        return response()->json(['message' => 'SingleItinerary not found'], 404);
    }

    if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
        return response()->json(['message' => 'Unauthorized access'], 403);
    }

    $approvalStatus = $request->input('approvelStatus');

    /** ---------------- VALIDATION ---------------- */
    if ($approvalStatus === 'Completed') {
        $validatedData = $request->validate([
            'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
            'approvelStatus' => 'required|in:Completed',
            'emissionOffset' => 'required|integer|min:0',
            'count'          => 'required|integer|min:0',
        ]);
    } else {
        $validatedData = $request->validate([
            'ItineraryId'    => 'required|integer|exists:itinerarydata,ItineraryId',
            'approvelStatus' => 'required|in:Rejected',
            'note'           => 'required|string',
            'count'          => 'required|integer|min:0',
        ]);
    }

    $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
    if (!$itinerary) {
        return response()->json(['message' => 'Itinerary not found'], 404);
    }

    /**Variable to return in response */
    $offsetCreditAdded = 0;

    DB::transaction(function () use (
        $validatedData,
        $singleItinerary,
        $itinerary,
        $approvalStatus,
        &$offsetCreditAdded
    ) {

        /** ---------------- BASIC UPDATE ---------------- */
        $singleItinerary->approvelStatus = $validatedData['approvelStatus'];
        $singleItinerary->count = $validatedData['count'];

        if (isset($validatedData['note'])) {
            $singleItinerary->note = $validatedData['note'];
        }

        if ($approvalStatus !== 'Completed') {
            $singleItinerary->save();
            return;
        }

        /** ---------------- OLD VALUES ---------------- */
        $oldOffset = $singleItinerary->emissionOffset ?? 0;

        /** ---------------- REQUEST ---------------- */
        $requestedOffset = (int) $validatedData['emissionOffset'];

        /** ---------------- LIMITS ---------------- */
        $emissionLimit = $itinerary->emission;
        $currentOffset = $itinerary->offsetAmount ?? 0;

        /** ---------------- REMAINING ---------------- */
        $remainingEmission = max(
            $emissionLimit - ($currentOffset - $oldOffset),
            0
        );

        /** ---------------- APPLY OFFSET ---------------- */
        $appliedOffset = min($requestedOffset, $remainingEmission);
        $extraOffset   = $requestedOffset - $appliedOffset;

        /** ---------------- SAVE SINGLE ITINERARY ---------------- */
        $singleItinerary->update([
            'emissionOffset' => $appliedOffset,
            'treesPlanted'   => intdiv($appliedOffset, 50)
        ]);

        /** ---------------- UPDATE MASTER ITINERARY ---------------- */
        $newOffset = ($currentOffset - $oldOffset) + $appliedOffset;
        $newTrees  = intdiv($newOffset, 50);

        $offsetPercentage = $emissionLimit > 0
            ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
            : 0;

        $status = match (true) {
            $offsetPercentage == 0  => 'pending',
            $offsetPercentage < 100 => 'partial',
            default                 => 'completed',
        };

        $itinerary->update([
            'offsetAmount'     => $newOffset,
            'numberOfTrees'    => $newTrees,
            'offsetPercentage' => $offsetPercentage,
            'status'           => $status
        ]);

        /** ---------------- USER OFFSET CREDIT ---------------- */
        $user = User::where('userId', $itinerary->userId)
            ->lockForUpdate()
            ->first();

        $user->offsetCredit += $extraOffset;
        $user->save();

        /**store the final total credit for response */
        $offsetCreditAdded = $user->offsetCredit;
    });

    return response()->json([
        'status'            => true,
        'message'           => 'Updated successfully',
        'offsetCreditAdded' => $offsetCreditAdded,
        'info'              => 'Extra offset stored as user credit (not auto-applied)'
    ]);
}





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
