<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SingleItineraryData;
use App\Models\ItineraryData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use App\Models\User;
use App\Models\BackgroundImage;
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


    
    // public function store(Request $request)
// {
//     Log::info('store() called in SingleItineraryController', [
//         'request' => $request->all()
//     ]);

    //     /** ---------------- VALIDATION ---------------- */
//     $validatedData = $request->validate([
//         'ItineraryId'            => 'required|integer|exists:itinerarydata,ItineraryId',
//         'uploadDate'             => 'nullable|date',
//         'certificateFile'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
//         'approvelStatus'         => 'nullable|string', // Pending, Completed, Rejected, Pending Verification
//         'emissionOffset'         => 'nullable|integer|min:0',
//         'projectTypes'           => 'nullable|string|max:255',
//         'projectsContributed'    => 'nullable|string|max:255',
//         'comments'               => 'nullable|string|max:1000',
//         'count'                  => 'nullable|integer|min:0',
//         'note'                   => 'nullable|string|max:1000',
//     ]);

    //     /** ---------------- ITINERARY ---------------- */
//     $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
//     if (!$itinerary) {
//         return response()->json(['status' => false, 'message' => 'Itinerary not found'], 404);
//     }

    //     /** ---------------- FILE UPLOAD ---------------- */
//     if ($request->hasFile('certificateFile')) {
//         $validatedData['certificateFile'] = $request
//             ->file('certificateFile')
//             ->store('certificates', 'public');
//     }

    //     /** ---------------- NORMALIZE STATUS ---------------- */
//     $approvalStatus = $validatedData['approvelStatus'] ?? 'Pending Verification';
//     $validatedData['approvelStatus'] = $approvalStatus;
//     $validatedData['userId'] = $itinerary->userId;

    //     $requestedOffset   = (int) ($validatedData['emissionOffset'] ?? 0);
//     $offsetCreditAdded = 0;
//     $updatedUser       = null;

    //     /** ---------------- TREE OFFSET VALUE (DYNAMIC) ---------------- */
//     $treeOffsetValue = \App\Models\BackgroundImage::where('id', 1)
//         ->value('treeOffsetsValue');

    //     // Safety fallback
//     $treeOffsetValue = $treeOffsetValue > 0 ? (int) $treeOffsetValue : 22;

    //     /** ---------------- TRANSACTION ---------------- */
//     DB::transaction(function () use (
//         $validatedData,
//         $approvalStatus,
//         &$requestedOffset,
//         $itinerary,
//         &$offsetCreditAdded,
//         &$updatedUser,
//         $treeOffsetValue
//     ) {
//         /** ---------------- CREATE SINGLE ITINERARY ---------------- */
//         $singleItinerary = new SingleItineraryData($validatedData);

    //         /** ---------------- FETCH USER ---------------- */
//         $user = User::where('userId', $itinerary->userId)
//             ->lockForUpdate()
//             ->first();

    //         if (!$user) {
//             throw new \Exception('User not found');
//         }

    //         // Default offsets for Pending / Verification / Rejected
//         $appliedOffset = 0;
//         $creditUsed = 0;
//         $extraOffset = 0;

    //         // ---------------- CASE: Completed or Admin-added offset ----------------
//         if (strcasecmp($approvalStatus, 'Completed') === 0 || $requestedOffset > 0) {
//             $userCredit = $user->offsetCredit ?? 0;

    //             // Use available user credit first
//             $creditUsed = min($requestedOffset, $userCredit);
//             $requestedOffset -= $creditUsed;
//             $user->offsetCredit -= $creditUsed;

    //             // Calculate applied offset within itinerary emission limit
//             $emissionLimit = $itinerary->emission;
//             $currentOffset = $itinerary->offsetAmount ?? 0;
//             $remainingEmission = max($emissionLimit - $currentOffset, 0);

    //             $appliedOffset = min($requestedOffset, $remainingEmission);
//             $extraOffset = $requestedOffset - $appliedOffset;

    //             // Update SingleItinerary offsets
//             $singleItinerary->emissionOffset = $appliedOffset + $creditUsed;
//             $singleItinerary->treesPlanted = intdiv(
//                 $singleItinerary->emissionOffset,
//                 $treeOffsetValue
//             );

    //             /** ---------------- UPDATE MASTER ITINERARY ---------------- */
//             $newOffset = $currentOffset + $singleItinerary->emissionOffset;
//             $offsetPercentage = $emissionLimit > 0
//                 ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
//                 : 0;

    //             $itinerary->update([
//                 'offsetAmount'     => $newOffset,
//                 'numberOfTrees'    => intdiv(
//                     $newOffset,
//                     $treeOffsetValue
//                 ),
//                 'offsetPercentage' => $offsetPercentage,
//                 'status' => match (true) {
//                     $newOffset == 0 => 'pending',
//                     $newOffset < $emissionLimit => 'partial',
//                     default => 'completed',
//                 },
//             ]);

    //             // Return extra offset to user if any
//             if ($extraOffset > 0) {
//                 $user->offsetCredit += $extraOffset;
//                 $offsetCreditAdded = $extraOffset;
//             }

    //             // Save user offset changes
//             $user->save();
//         } else {
//             // ---------------- CASE: Pending / Verification / Rejected ----------------
//             $singleItinerary->emissionOffset = 0;
//             $singleItinerary->treesPlanted = 0;
//         }

    //         /** ---------------- SAVE SINGLE ITINERARY ---------------- */
//         $singleItinerary->save();

    //         // Set updated user to return outside transaction
//         $updatedUser = $user;
//     });

    //     /** ---------------- RETURN RESPONSE ---------------- */
//     return response()->json([
//         'status'            => true,
//         'message'           => 'SingleItinerary created successfully',
//         'offsetCreditAdded' => $offsetCreditAdded,
//         'userOffsetCredit'  => $updatedUser->offsetCredit ?? 0,
//     ]);
// }

    // public function store(Request $request)
    // {
    //     Log::info('store() called in SingleItineraryController', [
    //         'request' => $request->all()
    //     ]);

    //     /** ---------------- VALIDATION ---------------- */
    //     $validatedData = $request->validate([
    //         'ItineraryId' => 'required|integer|exists:itinerarydata,ItineraryId',
    //         'uploadDate' => 'nullable|date',
    //         'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    //         'approvelStatus' => 'nullable|string',
    //         'emissionOffset' => 'nullable|integer|min:0',
    //         'projectTypes' => 'nullable|string|max:255',
    //         'projectsContributed' => 'nullable|string|max:255',
    //         'comments' => 'nullable|string|max:1000',
    //         'count' => 'nullable|integer|min:0',
    //         'note' => 'nullable|string|max:1000',
    //     ]);

    //     /** ---------------- ITINERARY ---------------- */
    //     $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
    //     if (!$itinerary) {
    //         return response()->json(['status' => false, 'message' => 'Itinerary not found'], 404);
    //     }

    //     /** ---------------- FILE UPLOAD ---------------- */
    //     if ($request->hasFile('certificateFile')) {
    //         $validatedData['certificateFile'] = $request
    //             ->file('certificateFile')
    //             ->store('certificates', 'public');
    //     }

    //     /** ---------------- NORMALIZE STATUS ---------------- */
    //     $approvalStatus = $validatedData['approvelStatus'] ?? 'Pending Verification';
    //     $validatedData['approvelStatus'] = $approvalStatus;
    //     $validatedData['userId'] = $itinerary->userId;

    //     $requestedOffset = (int) ($validatedData['emissionOffset'] ?? 0);
    //     $offsetCreditAdded = 0;
    //     $updatedUser = null;

    //     /** ---------------- TREE OFFSET VALUE (DYNAMIC) ---------------- */
    //     $treeOffsetValue = (int) \App\Models\BackgroundImage::where('id', 1)
    //         ->value('treeOffsetsValue');

    //     $treeOffsetValue = $treeOffsetValue > 0 ? $treeOffsetValue : 22;

    //     /** ---------------- TRANSACTION ---------------- */
    //     DB::transaction(function () use ($validatedData, $approvalStatus, &$requestedOffset, $itinerary, &$offsetCreditAdded, &$updatedUser, $treeOffsetValue) {
    //         /** ---------------- CREATE SINGLE ITINERARY ---------------- */
    //         $singleItinerary = new SingleItineraryData($validatedData);

    //         /** ---------------- FETCH USER ---------------- */
    //         $user = User::where('userId', $itinerary->userId)
    //             ->lockForUpdate()
    //             ->first();

    //         if (!$user) {
    //             throw new \Exception('User not found');
    //         }

    //         $appliedOffset = 0;
    //         $creditUsed = 0;
    //         $extraOffset = 0;

    //         if (strcasecmp($approvalStatus, 'Completed') === 0 || $requestedOffset > 0) {
    //             $userCredit = $user->offsetCredit ?? 0;

    //             $creditUsed = min($requestedOffset, $userCredit);
    //             $requestedOffset -= $creditUsed;
    //             $user->offsetCredit -= $creditUsed;

    //             $emissionLimit = $itinerary->emission;
    //             $currentOffset = $itinerary->offsetAmount ?? 0;
    //             $remainingEmission = max($emissionLimit - $currentOffset, 0);

    //             $appliedOffset = min($requestedOffset, $remainingEmission);
    //             $extraOffset = $requestedOffset - $appliedOffset;

    //             $singleItinerary->emissionOffset = $appliedOffset + $creditUsed;
    //             $singleItinerary->treesPlanted = round(
    //                 $singleItinerary->emissionOffset / $treeOffsetValue
    //             );

    //             $newOffset = $currentOffset + $singleItinerary->emissionOffset;
    //             $offsetPercentage = $emissionLimit > 0
    //                 ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
    //                 : 0;

    //             $itinerary->update([
    //                 'offsetAmount' => $newOffset,
    //                 'numberOfTrees' => round($newOffset / $treeOffsetValue),
    //                 'offsetPercentage' => $offsetPercentage,
    //                 'status' => match (true) {
    //                     $newOffset == 0 => 'pending',
    //                     $newOffset < $emissionLimit => 'partial',
    //                     default => 'completed',
    //                 },
    //             ]);

    //             if ($extraOffset > 0) {
    //                 $user->offsetCredit += $extraOffset;
    //             }

    //             $user->save();
    //         } else {
    //             $singleItinerary->emissionOffset = 0;
    //             $singleItinerary->treesPlanted = 0;
    //         }

    //         $singleItinerary->save();
    //         $updatedUser = $user;

    //         /** ✅ CALCULATE TREE COUNT (SAME AS show()) */
    //         if ($treeOffsetValue > 0 && $user->offsetCredit > 0) {
    //             $offsetCreditAdded = round($user->offsetCredit / $treeOffsetValue);
    //         } else {
    //             $offsetCreditAdded = 0;
    //         }
    //     });

    //     /** ---------------- RETURN RESPONSE ---------------- */
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'SingleItinerary created successfully',
    //         'offsetCreditAdded' => $offsetCreditAdded,
    //         'userOffsetCredit' => $treeOffsetValue > 0 && $updatedUser && $updatedUser->offsetCredit > 0
    //             ? round($updatedUser->offsetCredit / $treeOffsetValue)
    //             : 0,
    //     ]);
    // }

    public function store(Request $request)
    {
        Log::info('store() called in SingleItineraryController', [
            'request' => $request->all()
        ]);

        /** ---------------- VALIDATION ---------------- */
        $validatedData = $request->validate([
            'ItineraryId' => 'required|integer|exists:itinerarydata,ItineraryId',
            'uploadDate' => 'nullable|date',
            'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'approvelStatus' => 'nullable|string',
            'emissionOffset' => 'nullable|integer|min:0',
            'projectTypes' => 'nullable|string|max:255',
            'projectsContributed' => 'nullable|string|max:255',
            'comments' => 'nullable|string|max:1000',
            'count' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        /** ---------------- ITINERARY ---------------- */
        $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
        if (!$itinerary) {
            return response()->json(['status' => false, 'message' => 'Itinerary not found'], 404);
        }

        /** ---------------- FILE UPLOAD ---------------- */
        if ($request->hasFile('certificateFile')) {
            $validatedData['certificateFile'] = $request
                ->file('certificateFile')
                ->store('certificates', 'public');
        }

        /** ---------------- NORMALIZE STATUS ---------------- */
        $approvalStatus = $validatedData['approvelStatus'] ?? 'Pending Verification';
        $validatedData['approvelStatus'] = $approvalStatus;
        $validatedData['userId'] = $itinerary->userId;

        $requestedOffset = (int) ($validatedData['emissionOffset'] ?? 0);
        $offsetCreditAdded = 0;
        $updatedUser = null;

        /** ---------------- TREE OFFSET VALUE (DYNAMIC) ---------------- */
        $treeOffsetValue = (int) \App\Models\BackgroundImage::where('id', 1)
            ->value('treeOffsetsValue');

        $treeOffsetValue = $treeOffsetValue > 0 ? $treeOffsetValue : 22;

        /** ---------------- TRANSACTION ---------------- */
        DB::transaction(function () use ($validatedData, $approvalStatus, &$requestedOffset, $itinerary, &$offsetCreditAdded, &$updatedUser, $treeOffsetValue) {
            /** ---------------- CREATE SINGLE ITINERARY ---------------- */
            $singleItinerary = new SingleItineraryData($validatedData);

            /** ---------------- FETCH USER ---------------- */
            $user = User::where('userId', $itinerary->userId)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                throw new \Exception('User not found');
            }

            $appliedOffset = 0;
            $creditUsed = 0;
            $extraOffset = 0;

            if (strcasecmp($approvalStatus, 'Completed') === 0 || $requestedOffset > 0) {
                $userCredit = $user->offsetCredit ?? 0;

                $creditUsed = min($requestedOffset, $userCredit);
                $requestedOffset -= $creditUsed;
                $user->offsetCredit -= $creditUsed;

                $emissionLimit = $itinerary->emission;
                $currentOffset = $itinerary->offsetAmount ?? 0;
                $remainingEmission = max($emissionLimit - $currentOffset, 0);

                $appliedOffset = min($requestedOffset, $remainingEmission);
                $extraOffset = $requestedOffset - $appliedOffset;

                $singleItinerary->emissionOffset = $appliedOffset + $creditUsed;
                $singleItinerary->treesPlanted = round(
                    $singleItinerary->emissionOffset / $treeOffsetValue
                );
                $newOffset = $currentOffset + $singleItinerary->emissionOffset;

                $requiredTrees = (int) ($itinerary->totalTrees ?? 0);
                $plantedTrees = (int) round($newOffset / $treeOffsetValue);

                if ($plantedTrees >= $requiredTrees && $requiredTrees > 0) {
                    $offsetPercentage = 100;
                    $status = 'completed';
                    // align offset to emission
                    $newOffset = $emissionLimit;
                } else {
                    $offsetPercentage = $requiredTrees > 0
                        ? round(($plantedTrees / $requiredTrees) * 100, 2)
                        : 0;

                    $status = $plantedTrees > 0 ? 'partial' : 'pending';
                }

                $itinerary->update([
                    'offsetAmount' => $newOffset,
                    'numberOfTrees' => $plantedTrees,
                    'offsetPercentage' => $offsetPercentage,
                    'status' => $status,
                ]);


                if ($extraOffset > 0) {
                    $user->offsetCredit += $extraOffset;
                }

                $user->save();
            } else {
                $singleItinerary->emissionOffset = 0;
                $singleItinerary->treesPlanted = 0;
            }

            $singleItinerary->save();
            $updatedUser = $user;

            /** ✅ CALCULATE TREE COUNT (SAME AS show()) */
            if ($treeOffsetValue > 0 && $user->offsetCredit > 0) {
                $offsetCreditAdded = round($user->offsetCredit / $treeOffsetValue);
            } else {
                $offsetCreditAdded = 0;
            }
        });

        /** ---------------- RETURN RESPONSE ---------------- */
        return response()->json([
            'status' => true,
            'message' => 'SingleItinerary created successfully',
            'offsetCreditAdded' => $offsetCreditAdded,
            'userOffsetCredit' => $treeOffsetValue > 0 && $updatedUser && $updatedUser->offsetCredit > 0
                ? round($updatedUser->offsetCredit / $treeOffsetValue)
                : 0,
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
            'requestedUserId' => $userId,
            'requestedItineraryId' => $ItineraryId
        ]);

        $authUser = $request->user();

        if (!$authUser) {
            Log::warning('Unauthorized access attempt');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        /** CHECK ADMIN */
        // $isAdmin = AdminData::where('id', $authUser->id)->exists();
         $isAdmin = $authUser instanceof AdminData;

        /** AUTHORIZATION */
        if (!$isAdmin && $authUser->userId != $userId) {
            Log::warning('Access denied', [
                'loginUserId' => $authUser->userId,
                'requestedUserId' => $userId
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
                'userId' => $userId,
                'ItineraryId' => $ItineraryId
            ]);
            return response()->json([
                'status' => true,
                'message' => 'No records found for this user and itinerary.'
            ]);
        }

        Log::info('Records returned successfully', [
            'count' => $singleItineraries->count(),
            'userId' => $userId,
            'ItineraryId' => $ItineraryId
        ]);

        return response()->json([
            'status' => true,
            'data' => $singleItineraries
        ]);
    }



   

    // public function update(Request $request, $id)
    // {
    //     $authUser = $request->user();
    //     if (!$authUser) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     $isAdmin = AdminData::where('id', $authUser->id)->exists();

    //     $singleItinerary = SingleItineraryData::find($id);
    //     if (!$singleItinerary) {
    //         return response()->json(['message' => 'The requested SingleItinerary was previously deleted and is no longer available.'], 404);
    //     }

    //     if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
    //         return response()->json(['message' => 'Unauthorized access'], 403);
    //     }

    //     $approvalStatus = $request->input('approvelStatus');

    //     /** ---------------- VALIDATION ---------------- */
    //     if ($approvalStatus === 'Completed') {
    //         $validatedData = $request->validate([
    //             'ItineraryId' => 'required|integer|exists:itinerarydata,ItineraryId',
    //             'approvelStatus' => 'required|in:Completed',
    //             'emissionOffset' => 'required|integer|min:0',
    //             'count' => 'required|integer|min:0',
    //         ]);
    //     } else {
    //         $validatedData = $request->validate([
    //             'ItineraryId' => 'required|integer|exists:itinerarydata,ItineraryId',
    //             'approvelStatus' => 'required|in:Rejected',
    //             'note' => 'required|string',
    //             'count' => 'required|integer|min:0',
    //         ]);
    //     }

    //     $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
    //     if (!$itinerary) {
    //         return response()->json(['message' => 'Itinerary not found'], 404);
    //     }

    //     /** ---------------- TREE OFFSET VALUE (DYNAMIC) ---------------- */
    //     $treeOffsetValue = (int) \App\Models\BackgroundImage::where('id', 1)
    //         ->value('treeOffsetsValue');

    //     $treeOffsetValue = $treeOffsetValue > 0 ? $treeOffsetValue : 22;

    //     /** Variable to return in response */
    //     $offsetCreditAdded = 0;

    //     DB::transaction(function () use ($validatedData, $singleItinerary, $itinerary, $approvalStatus, &$offsetCreditAdded, $treeOffsetValue) {

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
    //         $extraOffset = $requestedOffset - $appliedOffset;

    //         /** ---------------- SAVE SINGLE ITINERARY ---------------- */
    //         $singleItinerary->update([
    //             'emissionOffset' => $appliedOffset,
    //             'treesPlanted' => round($appliedOffset / $treeOffsetValue),
    //         ]);

    //         /** ---------------- UPDATE MASTER ITINERARY ---------------- */
    //         $newOffset = ($currentOffset - $oldOffset) + $appliedOffset;
    //         $newTrees = round($newOffset / $treeOffsetValue);

    //         $offsetPercentage = $emissionLimit > 0
    //             ? min(round(($newOffset / $emissionLimit) * 100, 2), 100)
    //             : 0;

    //         $status = match (true) {
    //             $offsetPercentage == 0 => 'pending',
    //             $offsetPercentage < 100 => 'partial',
    //             default => 'completed',
    //         };

    //         $itinerary->update([
    //             'offsetAmount' => $newOffset,
    //             'numberOfTrees' => $newTrees,
    //             'offsetPercentage' => $offsetPercentage,
    //             'status' => $status,
    //         ]);

    //         /** ---------------- USER OFFSET CREDIT ---------------- */
    //         $user = User::where('userId', $itinerary->userId)
    //             ->lockForUpdate()
    //             ->first();

    //         $user->offsetCredit += $extraOffset;
    //         $user->save();

    //         /** ✅ RETURN TREE COUNT (SAME AS show()) */
    //         if ($treeOffsetValue > 0 && $user->offsetCredit > 0) {
    //             $offsetCreditAdded = round($user->offsetCredit / $treeOffsetValue);
    //         } else {
    //             $offsetCreditAdded = 0;
    //         }
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Updated successfully',
    //         'offsetCreditAdded' => $offsetCreditAdded,
    //         'info' => 'Extra offset stored as user credit (returned as tree count)',
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
            return response()->json(['message' => 'The requested SingleItinerary was previously deleted and is no longer available.'], 404);
        }

        if (!$isAdmin && $singleItinerary->userId !== $authUser->userId) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $approvalStatus = $request->input('approvelStatus');

        /** ---------------- VALIDATION ---------------- */
        if ($approvalStatus === 'Completed') {
            $validatedData = $request->validate([
                'ItineraryId' => 'required|integer|exists:itinerarydata,ItineraryId',
                'approvelStatus' => 'required|in:Completed',
                'emissionOffset' => 'required|integer|min:0',
                'count' => 'required|integer|min:0',
            ]);
        } else {
            $validatedData = $request->validate([
                'ItineraryId' => 'required|integer|exists:itinerarydata,ItineraryId',
                'approvelStatus' => 'required|in:Rejected',
                'note' => 'required|string',
                'count' => 'required|integer|min:0',
            ]);
        }

        $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])->first();
        if (!$itinerary) {
            return response()->json(['message' => 'Itinerary not found'], 404);
        }

        /** ---------------- TREE OFFSET VALUE (DYNAMIC) ---------------- */
        $treeOffsetValue = (int) \App\Models\BackgroundImage::where('id', 1)
            ->value('treeOffsetsValue');

        $treeOffsetValue = $treeOffsetValue > 0 ? $treeOffsetValue : 22;

        /** Variable to return in response */
        $offsetCreditAdded = 0;

        DB::transaction(function () use ($validatedData, $singleItinerary, $itinerary, $approvalStatus, &$offsetCreditAdded, $treeOffsetValue) {

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
            $extraOffset = $requestedOffset - $appliedOffset;

            /** ---------------- SAVE SINGLE ITINERARY ---------------- */
            $singleItinerary->update([
                'emissionOffset' => $appliedOffset,
                'treesPlanted' => round($appliedOffset / $treeOffsetValue),
            ]);

            /** ---------------- UPDATE MASTER ITINERARY ---------------- */
            $newOffset = ($currentOffset - $oldOffset) + $appliedOffset;
            
            $requiredTrees = (int) ($itinerary->totalTrees ?? 0);
            $plantedTrees = (int) round($newOffset / $treeOffsetValue);

            if ($plantedTrees >= $requiredTrees && $requiredTrees > 0) {
                $offsetPercentage = 100;
                $status = 'completed';
                // align offset to emission
                $newOffset = $emissionLimit;
            } else {
                $offsetPercentage = $requiredTrees > 0
                    ? round(($plantedTrees / $requiredTrees) * 100, 2)
                    : 0;

                $status = $plantedTrees > 0 ? 'partial' : 'pending';
            }

            $itinerary->update([
                'offsetAmount' => $newOffset,
                'numberOfTrees' => $plantedTrees,
                'offsetPercentage' => $offsetPercentage,
                'status' => $status,
            ]);

            /** ---------------- USER OFFSET CREDIT ---------------- */
            $user = User::where('userId', $itinerary->userId)
                ->lockForUpdate()
                ->first();

            $user->offsetCredit += $extraOffset;
            $user->save();

            /** ✅ RETURN TREE COUNT (SAME AS show()) */
            if ($treeOffsetValue > 0 && $user->offsetCredit > 0) {
                $offsetCreditAdded = round($user->offsetCredit / $treeOffsetValue);
            } else {
                $offsetCreditAdded = 0;
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Updated successfully',
            'offsetCreditAdded' => $offsetCreditAdded,
            'info' => 'Extra offset stored as user credit (returned as tree count)',
        ]);
    }

   

     




    // public function destroy(Request $request, $id)
    // {
    //     Log::info('Admin destroy() called for SingleItinerary', [
    //         'admin_auth_id' => optional($request->user())->id,
    //         'singleItineraryId' => $id
    //     ]);

    //     // Get authenticated admin
    //     $admin = $request->user();

    //     if (!$admin) {
    //         Log::warning('Unauthenticated access attempt in destroy()');
    //         return response()->json(['message' => 'Unauthenticated'], 401);
    //     }

    //     // Verify admin
    //     if (!AdminData::where('id', $admin->id)->exists()) {
    //         Log::warning('Unauthorized attempted to delete SingleItinerary', [
    //             'auth_id' => $admin->id
    //         ]);

    //         return response()->json([
    //             'message' => 'Unauthorized'
    //         ], 403);
    //     }

    //     // Find single itinerary by ID
    //     $singleItinerary = SingleItineraryData::find($id);

    //     if (!$singleItinerary) {
    //         Log::warning('SingleItinerary not found for delete', [
    //             'singleItineraryId' => $id
    //         ]);

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'The requested SingleItinerary was previously deleted and is no longer available.'
    //         ], 404);
    //     }

    //     // Delete certificate file if exists
    //     if (
    //         $singleItinerary->certificateFile &&
    //         Storage::disk('public')->exists($singleItinerary->certificateFile)
    //     ) {
    //         Storage::disk('public')->delete($singleItinerary->certificateFile);

    //         Log::info('Certificate file deleted by admin', [
    //             'certificateFile' => $singleItinerary->certificateFile
    //         ]);
    //     }

    //     // Delete record
    //     $singleItinerary->delete();

    //     Log::info('SingleItinerary deleted successfully by admin', [
    //         'admin_id' => $admin->id,
    //         'singleItineraryId' => $id
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'SingleItinerary deleted successfully'
    //     ]);
    // }


    public function destroy($id)
    {
        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            return response()->json([
                'status' => false,
                'message' => 'The requested SingleItinerary was previously deleted and is no longer available.'
            ], 404);
        }

        if ($singleItinerary->certificateFile && Storage::disk('public')->exists($singleItinerary->certificateFile)) {
            Storage::disk('public')->delete($singleItinerary->certificateFile);
        }

        $singleItinerary->delete();

        return response()->json([
            'status' => true,
            'message' => 'SingleItinerary deleted successfully'
        ]);
    }
}
