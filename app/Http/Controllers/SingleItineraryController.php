<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SingleItineraryData;
use App\Models\ItineraryData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;

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

    /**
     * Get SingleItinerary records for a given userId and ItineraryId.
     * @param Request $request
     * @param int $userId
     * @param int $ItineraryId
     * @return \Illuminate\Http\JsonResponse
     */
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


    public function update(Request $request, $id)
    {
        $user = $request->user();
        Log::info('update() called in SingleItineraryController', ['user' => $user, 'singleItineraryId' => $id]);
        if (!$user) {
            Log::warning('Unauthorized access attempt in update()');
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            Log::warning('SingleItinerary not found in update()', ['id' => $id]);
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        if ($singleItinerary->userId !== $user->userId) {
            Log::warning('Unauthorized update attempt on SingleItinerary', [
                'userId' => $user->userId,
                'ownerUserId' => $singleItinerary->userId
            ]);
            return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
        }

        $validatedData = $request->validate([
            'ItineraryId'     => 'sometimes|integer|exists:itinerarydata,ItineraryId',
            'uploadDate'      => 'nullable|date',
            'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|numeric',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
            'comments'        => 'nullable|string|max:1000',
        ]);
        Log::info('Validated data in update()', ['validatedData' => $validatedData]);

        if (isset($validatedData['ItineraryId'])) {
            $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])
                ->where('userId', $user->userId)
                ->first();
            if (!$itinerary) {
                Log::warning('Unauthorized ItineraryId in update()', [
                    'userId' => $user->userId,
                    'ItineraryId' => $validatedData['ItineraryId']
                ]);
                return response()->json(['message' => 'Unauthorized: ItineraryId does not belong to the authenticated user.'], 403);
            }
        }

        if ($request->hasFile('certificateFile')) {
            if ($singleItinerary->certificateFile && Storage::disk('public')->exists($singleItinerary->certificateFile)) {
                Storage::disk('public')->delete($singleItinerary->certificateFile);
                Log::info('Old certificateFile deleted in update()', ['previous' => $singleItinerary->certificateFile]);
            }

            $file = $request->file('certificateFile');
            $path = $file->store('certificates', 'public');
            $validatedData['certificateFile'] = $path;
            Log::info('Certificate file uploaded in update()', ['path' => $path]);
        } else {
            unset($validatedData['certificateFile']);
        }

        $singleItinerary->update($validatedData);
        Log::info('SingleItinerary updated', [
            'singleItineraryId' => $id,
            'updatedFields' => $validatedData
        ]);

        return response()->json([
            'message' => 'SingleItinerary updated successfully.',
            'data' => $singleItinerary
        ]);
    }


    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        Log::info('destroy() called in SingleItineraryController', [
            'user' => $user,
            'singleItineraryId' => $id
        ]);
        if (!$user) {
            Log::warning('Unauthorized access attempt in destroy()');
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            Log::warning('SingleItinerary not found in destroy()', ['id' => $id]);
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        if ($singleItinerary->userId !== $user->userId) {
            Log::warning('Unauthorized delete attempt on SingleItinerary', [
                'userId' => $user->userId,
                'ownerUserId' => $singleItinerary->userId
            ]);
            return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
        }

        if ($singleItinerary->certificateFile && Storage::disk('public')->exists($singleItinerary->certificateFile)) {
            Storage::disk('public')->delete($singleItinerary->certificateFile);
            Log::info('Certificate file deleted in destroy()', ['certificateFile' => $singleItinerary->certificateFile]);
        }

        $singleItinerary->delete();
        Log::info('SingleItinerary deleted', ['singleItineraryId' => $id]);

        return response()->json(['message' => 'SingleItinerary deleted successfully.']);
    }
}
