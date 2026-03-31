<?php

namespace App\Http\Controllers;

use App\Models\ItineraryData;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use App\Models\User;
use App\Models\NotificationsReminder;
use Illuminate\Support\Facades\DB;


class ItineraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    // public function index(Request $request)
    // {
    //     $userId = $request->userId ?? $request->user()->userId;

    //     // Load single itinerary (only to check existence)
    //     $itineraries = ItineraryData::where('userId', $userId)
    //         ->with('singleItinerary')
    //         ->get();

    //     $itineraries->transform(function ($itinerary) {

    //         /** -----------------
    //          * COUNTRY (keep same logic)
    //          * ----------------- */
    //         if (!empty($itinerary->country_id)) {

    //             $country = Country::where('country_id', $itinerary->country_id)
    //                 ->orWhere('country_name', $itinerary->country_id)
    //                 ->first();

    //             $itinerary->country_name = $country?->country_name;
    //             $itinerary->country_id   = $country?->country_id;

    //         } else {
    //             $itinerary->country_name = null;
    //             $itinerary->country_id   = null;
    //         }

    //         /** -----------------
    //          * OFFSET HISTORY FLAG ONLY
    //          * ----------------- */
    //         $itinerary->offset_history = $itinerary->singleItinerary !== null;

    //         // remove relationship from response
    //         unset($itinerary->singleItinerary);

    //         return $itinerary;
    //     });

    //     return response()->json([
    //         'data' => $itineraries
    //     ]);
    // }

    public function index(Request $request)
    {
        $userId = $request->userId ?? $request->user()->userId;

        // Load single itinerary (only to check existence)
        $itineraries = ItineraryData::where('userId', $userId)
            ->with('singleItinerary')
            ->get();

        $itineraries->transform(function ($itinerary) {

            /** -----------------
             * COUNTRY (keep same logic)
             * ----------------- */
            if (!empty($itinerary->country_id)) {

                $country = Country::where('country_id', $itinerary->country_id)
                    ->orWhere('country_name', $itinerary->country_id)
                    ->first();

                $itinerary->country_name = $country?->country_name;
                $itinerary->country_id = $country?->country_id;

            } else {
                $itinerary->country_name = null;
                $itinerary->country_id = null;
            }

            /** -----------------
             * OFFSET HISTORY FLAG ONLY
             * ----------------- */
            $itinerary->offset_history = $itinerary->singleItinerary !== null;

            /** -----------------
             * PENDING FLAG
             * ----------------- */
            $itinerary->is_pending = $itinerary->singleItinerary === null || $itinerary->singleItinerary->approvelStatus === 'pending';

            // remove relationship from response
            unset($itinerary->singleItinerary);

            return $itinerary;
        });

        return response()->json([
            'data' => $itineraries
        ]);
    }




    /**
     * Store a newly created resource in storage.
     */
    // 1. public function store(Request $request)
    // {
    //     Log::info($request->all());

    //     $authUser = $request->user();
    //     if (!$authUser) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     /** ---------------- VALIDATION ---------------- */
    //     $validated = $request->validate([
    //         'userId'       => 'required|integer',
    //         'date'         => 'required|date',

    //         'airline'      => 'required|string|max:255',
    //         'origin'       => 'required|string|max:255',
    //         'destination'  => 'required|string|max:255',
    //         'class'        => 'required|string|max:255',
    //         'passengers'   => 'required|integer',
    //         'tripType'     => 'required|string|max:255',
    //         'distance'     => 'required|string|max:255',

    //         'flightcode'      => 'nullable|string|max:255',
    //         'originCity'      => 'nullable|string|max:255',
    //         'destinationCity' => 'nullable|string|max:255',
    //         'emission'        => 'nullable|numeric',

    //         'country' => [
    //             'nullable',
    //             'string',
    //             'max:255',
    //             function ($attribute, $value, $fail) {
    //                 if (
    //                     $value &&
    //                     !Country::where('country_name', $value)
    //                         ->orWhere('country_id', $value)
    //                         ->exists()
    //                 ) {
    //                     $fail('The selected country is invalid.');
    //                 }
    //             }
    //         ],
    //     ]);

    //     /** ---------------- USER OWNERSHIP ---------------- */
    //     if ($authUser->userId != $validated['userId']) {
    //         return response()->json([
    //             'message' => 'Unauthorized: userId mismatch'
    //         ], 403);
    //     }

    //     /** ---------------- NORMALIZE COUNTRY ---------------- */
    //     if (!empty($validated['country'])) {
    //         $country = Country::where('country_name', $validated['country'])
    //             ->orWhere('country_id', $validated['country'])
    //             ->first();
    //         $validated['country'] = $country ? $country->country_id : $validated['country'];
    //     }

    //     DB::transaction(function () use (&$itinerary, $validated, $authUser) {

    //         /** ---------------- CREATE ITINERARY ---------------- */
    //         $itinerary = ItineraryData::create(array_merge($validated, [
    //             'offsetAmount'     => 0,
    //             'numberOfTrees'    => 0,
    //             'offsetPercentage' => 0,
    //             'status'           => 'pending'
    //         ]));

    //         /** ---------------- USER CREDIT LOCK ---------------- */
    //         $user = User::where('userId', $authUser->userId)
    //             ->lockForUpdate()
    //             ->first();

    //         /** ---------------- APPLY OFFSET CREDIT ONLY ---------------- */
    //         $remainingEmission = max(
    //             ($itinerary->emission ?? 0) - $itinerary->offsetAmount,
    //             0
    //         );

    //         $useOffset = min($user->offsetCredit ?? 0, $remainingEmission);

    //         if ($useOffset > 0) {

    //             /** APPLY OFFSET */
    //             $itinerary->offsetAmount += $useOffset;

    //             /** 🌱 TREE RULE (SINGLE SOURCE OF TRUTH) */
    //             $itinerary->numberOfTrees = intdiv($itinerary->offsetAmount, 50);

    //             /** STATUS UPDATE */
    //             if (($itinerary->emission ?? 0) > 0) {
    //                 $itinerary->offsetPercentage = min(
    //                     round(($itinerary->offsetAmount / $itinerary->emission) * 100, 2),
    //                     100
    //                 );

    //                 $itinerary->status = match (true) {
    //                     $itinerary->offsetPercentage == 0  => 'pending',
    //                     $itinerary->offsetPercentage < 100 => 'partial',
    //                     default                            => 'completed',
    //                 };
    //             }

    //             $itinerary->save();

    //             /** REDUCE USER OFFSET CREDIT */
    //             $user->offsetCredit -= $useOffset;
    //             $user->save();
    //         }
    //     });

    //     /** ---------------- RESPONSE ---------------- */
    //     return response()->json([
    //         'message' => 'Itinerary created successfully. Offset credits applied using 1 tree per 50 rule.',
    //         'data'    => ItineraryData::where('userId', $authUser->userId)->get()
    //     ], 201);
    // }

    //     public function store(Request $request)
// {
//     Log::info($request->all());

    //     $authUser = $request->user();
//     if (!$authUser) {
//         return response()->json(['message' => 'Unauthorized'], 401);
//     }

    //     /** ---------------- VALIDATION ---------------- */
//     $validated = $request->validate([
//         'userId'       => 'required|integer',
//         'date'         => 'required|date',

    //         'airline'      => 'required|string|max:255',
//         'origin'       => 'required|string|max:255',
//         'destination'  => 'required|string|max:255',
//         'class'        => 'required|string|max:255',
//         'passengers'   => 'required|integer|min:1',
//         'tripType'     => 'required|string|max:255',
//         'distance'     => 'required|string|max:255',

    //         'flightcode'      => 'nullable|string|max:255',
//         'originCity'      => 'nullable|string|max:255',
//         'destinationCity' => 'nullable|string|max:255',
//         'emission'        => 'nullable|numeric|min:0',

    //         /** FRONTEND VALUE */
//         'totalTrees'      => 'nullable|integer|min:0',

    //         'country' => [
//             'nullable','string','max:255',
//             function ($attribute, $value, $fail) {
//                 if (
//                     $value &&
//                     !Country::where('country_name', $value)
//                         ->orWhere('country_id', $value)
//                         ->exists()
//                 ) {
//                     $fail('The selected country is invalid.');
//                 }
//             }
//         ],
//     ]);

    //     /** ---------------- USER OWNERSHIP ---------------- */
//     if ($authUser->userId !== (int)$validated['userId']) {
//         return response()->json([
//             'message' => 'Unauthorized: userId mismatch'
//         ], 403);
//     }

    //     /** ---------------- NORMALIZE COUNTRY ---------------- */
//     if (!empty($validated['country'])) {
//         $country = Country::where('country_name', $validated['country'])
//             ->orWhere('country_id', $validated['country'])
//             ->first();

    //         $validated['country'] = $country?->country_id;
//     }

    //     DB::transaction(function () use (&$itinerary, $validated, $authUser) {

    //         /** ---------------- CREATE ITINERARY ---------------- */
//         $itinerary = ItineraryData::create([
//             ...$validated,
//             'offsetAmount'     => 0,
//             'numberOfTrees'    => 0,
//             'offsetPercentage' => 0,
//             'status'           => 'pending'
//         ]);

    //         /** ---------------- LOCK USER ---------------- */
//         $user = User::where('userId', $authUser->userId)
//             ->lockForUpdate()
//             ->first();

    //         /** ---------------- APPLY OFFSET CREDIT ---------------- */
//         $remainingEmission = max(
//             ($itinerary->emission ?? 0) - $itinerary->offsetAmount,
//             0
//         );

    //         $useOffset = min($user->offsetCredit ?? 0, $remainingEmission);

    //         if ($useOffset > 0) {

    //             /** APPLY OFFSET */
//             $itinerary->offsetAmount += $useOffset;

    //             /** 🌱 SINGLE SOURCE OF TRUTH */
//             $itinerary->numberOfTrees = intdiv(
//                 $itinerary->offsetAmount,
//                 50
//             );

    //             /** STATUS & PERCENTAGE */
//             if (($itinerary->emission ?? 0) > 0) {

    //                 $itinerary->offsetPercentage = min(
//                     round(($itinerary->offsetAmount / $itinerary->emission) * 100, 2),
//                     100
//                 );

    //                 $itinerary->status = match (true) {
//                     $itinerary->offsetPercentage == 0  => 'pending',
//                     $itinerary->offsetPercentage < 100 => 'partial',
//                     default                            => 'completed',
//                 };
//             }

    //             $itinerary->save();

    //             /** DEDUCT USER CREDIT */
//             $user->offsetCredit -= $useOffset;
//             $user->save();
//         }
//     });

    //     /** ---------------- RESPONSE ---------------- */
//     return response()->json([
//         'status'  => true,
//         'message' => 'Itinerary created successfully. Trees derived using 1 tree per 50 offset.',
//         'data'    => ItineraryData::where('userId', $authUser->userId)->get()
//     ], 201);
// }

    // public function store(Request $request)
// {
//     Log::info($request->all());

    //     $authUser = $request->user();
//     if (!$authUser) {
//         return response()->json(['message' => 'Unauthorized'], 401);
//     }

    //     /** ---------------- VALIDATION ---------------- */
//     $validated = $request->validate([
//         'userId'       => 'required|integer',
//         'date'         => 'required|date',

    //         'airline'      => 'required|string|max:255',
//         'origin'       => 'required|string|max:255',
//         'destination'  => 'required|string|max:255',
//         'class'        => 'required|string|max:255',
//         'passengers'   => 'required|integer|min:1',
//         'tripType'     => 'required|string|max:255',
//         'distance'     => 'required|string|max:255',

    //         'flightcode'      => 'nullable|string|max:255',
//         'originCity'      => 'nullable|string|max:255',
//         'destinationCity' => 'nullable|string|max:255',
//         'emission'        => 'nullable|numeric|min:0',

    //         /** FRONTEND VALUE */
//         'totalTrees'      => 'nullable|integer|min:0',

    //         'country' => [
//             'nullable','string','max:255',
//             function ($attribute, $value, $fail) {
//                 if (
//                     $value &&
//                     !Country::where('country_name', $value)
//                         ->orWhere('country_id', $value)
//                         ->exists()
//                 ) {
//                     $fail('The selected country is invalid.');
//                 }
//             }
//         ],
//     ]);

    //     /** ---------------- USER OWNERSHIP ---------------- */
//     if ($authUser->userId !== (int)$validated['userId']) {
//         return response()->json([
//             'message' => 'Unauthorized: userId mismatch'
//         ], 403);
//     }

    //     /** ---------------- CHECK PENDING ITINERARIES LIMIT ---------------- */
//     $reminderSettings = NotificationsReminder::first();

    //     if ($reminderSettings) {
//         $pendingCount = ItineraryData::where('userId', $authUser->userId)
//             ->where('status', 'pending')
//             ->count();

    //         if ($pendingCount >= $reminderSettings->limite_itineraries) {
//             return response()->json([
//                 'status'  => false,
//                 'message' => 'Please complete any of your previous pending offsets before adding a new itinerary.',
//                 'pendingCount' => $pendingCount,
//                 'limit'        => $reminderSettings->limite_itineraries
//             ], 400);
//         }
//     }

    //     /** ---------------- NORMALIZE COUNTRY ---------------- */
//     if (!empty($validated['country'])) {
//         $country = Country::where('country_name', $validated['country'])
//             ->orWhere('country_id', $validated['country'])
//             ->first();

    //         $validated['country'] = $country?->country_id;
//     }

    //     DB::transaction(function () use (&$itinerary, $validated, $authUser) {

    //         /** ---------------- CREATE ITINERARY ---------------- */
//         $itinerary = ItineraryData::create([
//             ...$validated,
//             'offsetAmount'     => 0,
//             'numberOfTrees'    => 0,
//             'offsetPercentage' => 0,
//             'status'           => 'pending'
//         ]);

    //         /** ---------------- LOCK USER ---------------- */
//         $user = User::where('userId', $authUser->userId)
//             ->lockForUpdate()
//             ->first();

    //         /** ---------------- APPLY OFFSET CREDIT ---------------- */
//         $remainingEmission = max(
//             ($itinerary->emission ?? 0) - $itinerary->offsetAmount,
//             0
//         );

    //         $useOffset = min($user->offsetCredit ?? 0, $remainingEmission);

    //         if ($useOffset > 0) {

    //             /** APPLY OFFSET */
//             $itinerary->offsetAmount += $useOffset;

    //             /** 🌱 SINGLE SOURCE OF TRUTH */
//             $itinerary->numberOfTrees = intdiv(
//                 $itinerary->offsetAmount,
//                 50
//             );

    //             /** STATUS & PERCENTAGE */
//             if (($itinerary->emission ?? 0) > 0) {

    //                 $itinerary->offsetPercentage = min(
//                     round(($itinerary->offsetAmount / $itinerary->emission) * 100, 2),
//                     100
//                 );

    //                 $itinerary->status = match (true) {
//                     $itinerary->offsetPercentage == 0  => 'pending',
//                     $itinerary->offsetPercentage < 100 => 'partial',
//                     default                            => 'completed',
//                 };
//             }

    //             $itinerary->save();

    //             /** DEDUCT USER CREDIT */
//             $user->offsetCredit -= $useOffset;
//             $user->save();
//         }
//     });

    //     /** ---------------- RESPONSE ---------------- */
//     return response()->json([
//         'status'  => true,
//         'message' => 'Itinerary created successfully. Trees derived using 1 tree per 50 offset.',
//         'data'    => ItineraryData::where('userId', $authUser->userId)->get()
//     ], 201);
// }

    public function store(Request $request)
    {
        Log::info($request->all());

        $authUser = $request->user();
        if (!$authUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        /** ---------------- VALIDATION ---------------- */
        $validated = $request->validate([
            'userId' => 'required|integer',
            'date' => 'required|date',

            'airline' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'class' => 'required|string|max:255',
            'passengers' => 'required|integer|min:1',
            'tripType' => 'required|string|max:255',
            'distance' => 'required|string|max:255',

            'flightcode' => 'nullable|string|max:255',
            'originCity' => 'nullable|string|max:255',
            'destinationCity' => 'nullable|string|max:255',
            'emission' => 'nullable|numeric|min:0',

            /** FRONTEND VALUE */
            'totalTrees' => 'nullable|integer|min:0',

            'country' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (
                        $value &&
                        !Country::where('country_name', $value)
                            ->orWhere('country_id', $value)
                            ->exists()
                    ) {
                        $fail('The selected country is invalid.');
                    }
                }
            ],
        ]);

        /** ---------------- USER OWNERSHIP ---------------- */
        if ($authUser->userId !== (int) $validated['userId']) {
            return response()->json([
                'message' => 'Unauthorized: userId mismatch'
            ], 403);
        }

        /** ---------------- CHECK PENDING ITINERARIES LIMIT ---------------- */
        $reminderSettings = NotificationsReminder::first();

        if ($reminderSettings) {
            $pendingCount = ItineraryData::where('userId', $authUser->userId)
                ->where('status', 'pending')
                ->count();

            if ($pendingCount >= $reminderSettings->limite_itineraries) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete any of your previous pending offsets before adding a new itinerary.',
                    'pendingCount' => $pendingCount,
                    'limit' => $reminderSettings->limite_itineraries
                ], 400);
            }
        }

        /** ---------------- NORMALIZE COUNTRY ---------------- */
        if (!empty($validated['country'])) {
            $country = Country::where('country_name', $validated['country'])
                ->orWhere('country_id', $validated['country'])
                ->first();

            $validated['country'] = $country?->country_id;
        }

        DB::transaction(function () use (&$itinerary, $validated) {

            /** ---------------- CREATE ITINERARY (NO AUTO OFFSET) ---------------- */
            $itinerary = ItineraryData::create([
                ...$validated,
                'offsetAmount' => 0,
                'numberOfTrees' => 0,
                'offsetPercentage' => 0,
                'status' => 'pending'
            ]);

        });

        /** ---------------- RESPONSE ---------------- */
        return response()->json([
            'status' => true,
            'message' => 'Itinerary created successfully. Offset credit is not auto-applied.',
            'data' => ItineraryData::where('userId', $authUser->userId)->get()
        ], 201);
    }



    /**
     * Display the specified resource.
     */

    public function show(Request $request, string $userId)
    {
        Log::info('Admin user itineraries list called', [
            'admin_auth_id' => optional($request->user())->id,
            'passed_user_id' => $userId,
        ]);

        // Get authenticated admin
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Verify admin
        if (!AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Non-admin attempted itinerary access', [
                'auth_id' => $admin->id,
            ]);

            return response()->json([
                'message' => 'Unauthorized - Not an admin'
            ], 403);
        }

        // Fetch all itineraries for that user
        $itineraries = ItineraryData::where('userId', $userId)
            ->orderBy('userId', 'desc')
            ->get();

        //  Attach country name & id
        $itineraries->transform(function ($itinerary) {
            if (!empty($itinerary->country)) {
                $country = Country::where('country_id', $itinerary->country)
                    ->orWhere('country_name', $itinerary->country)
                    ->first();

                $itinerary->country_name = $country?->country_name;
                $itinerary->country_id = $country?->country_id;
            } else {
                $itinerary->country_name = null;
                $itinerary->country_id = null;
            }
            return $itinerary;
        });

        Log::info('Admin itineraries fetched successfully', [
            'admin_id' => $admin->id,
            'user_id' => $userId,
            'count' => $itineraries->count(),
        ]);

        return response()->json([
            'status' => true,
            'data' => $itineraries
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     // Find the itinerary
    //     $itinerary = ItineraryData::find($id);

    //     if (!$itinerary) {
    //         return response()->json([
    //             'message' => 'Itinerary not found'
    //         ], 404);
    //     }

    //     // Only allow update if this itinerary belongs to the authenticated user
    //     if ($itinerary->userId != $request->user()->userId) {
    //         return response()->json([
    //             'message' => 'Unauthorized: You do not have permission to update this itinerary.'
    //         ], 403);
    //     }

    //     // Validate the request based on allowed updatable fields
    //     $validated = $request->validate([
    //         'date'               => 'sometimes|date',
    //         'airline'            => 'sometimes|string|max:255',
    //         'origin'             => 'sometimes|string|max:255',
    //         'destination'        => 'sometimes|string|max:255',
    //         'class'              => 'sometimes|string|max:255',
    //         'passengers'         => 'sometimes|integer',
    //         'tripType'           => 'sometimes|string|max:255',
    //         'distance'           => 'sometimes|string|max:255',
    //         'flightcode'         => 'nullable|string|max:255',
    //         'originCity'         => 'nullable|string|max:255',
    //         'destinationCity'    => 'nullable|string|max:255',
    //         'emission'           => 'nullable|numeric',
    //         'offsetAmount'       => 'nullable|integer',
    //         'offsetPercentage'   => 'nullable|integer',
    //         'numberOfTrees'      => 'nullable|integer',
    //         'country' => [
    //             'nullable',
    //             'string',
    //             'max:255',
    //             function ($attribute, $value, $fail) {
    //                 if (
    //                     $value &&
    //                     !Country::where('country_name', $value)
    //                         ->orWhere('country_id', $value)
    //                         ->exists()
    //                 ) {
    //                     $fail('The selected country is invalid.');
    //                 }
    //             }
    //         ],

    //         'status'             => 'nullable|string|max:255',
    //         'approvelStatus'     => 'nullable|string|max:255',
    //     ]);

    //     // Convert country to country_id if needed
    //     if (!empty($validated['country'])) {
    //         $country = Country::where('country_name', $validated['country'])
    //             ->orWhere('country_id', $validated['country'])
    //             ->first();
    //         $validated['country'] = $country ? $country->country_id : $validated['country'];
    //     }

    //     $itinerary->update($validated);

    //     // Attach country name and id after update
    //     if ($itinerary->country) {
    //         $country = Country::where('country_id', $itinerary->country)
    //             ->orWhere('country_name', $itinerary->country)
    //             ->first();
    //         $itinerary->country_name = $country ? $country->country_name : null;
    //         $itinerary->country_id = $country ? $country->country_id : null;
    //     } else {
    //         $itinerary->country_name = null;
    //         $itinerary->country_id = null;
    //     }

    //     return response()->json([
    //         'message' => 'Itinerary updated successfully',
    //         'data' => $itinerary
    //     ]);
    // }

    public function update(Request $request, string $id)
    {
        Log::info('Admin itinerary update() called', [
            'admin_auth_id' => optional($request->user())->id,
            'itinerary_id' => $id
        ]);

        // Get authenticated admin
        $admin = $request->user();

        if (!$admin) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Verify admin
        if (!AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Non-admin attempted itinerary update', [
                'auth_id' => $admin->id
            ]);

            return response()->json([
                'message' => 'Unauthorized - Not an admin'
            ], 403);
        }

        // Find the itinerary
        $itinerary = ItineraryData::find($id);

        if (!$itinerary) {
            return response()->json([
                'message' => 'Itinerary not found'
            ], 404);
        }

        // Validation (same as before)
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'airline' => 'sometimes|string|max:255',
            'origin' => 'sometimes|string|max:255',
            'destination' => 'sometimes|string|max:255',
            'class' => 'sometimes|string|max:255',
            'passengers' => 'sometimes|integer',
            'tripType' => 'sometimes|string|max:255',
            'distance' => 'sometimes|string|max:255',
            'flightcode' => 'nullable|string|max:255',
            'originCity' => 'nullable|string|max:255',
            'destinationCity' => 'nullable|string|max:255',
            'emission' => 'nullable|numeric',
            'offsetAmount' => 'nullable|integer',
            'offsetPercentage' => 'nullable|integer',
            'numberOfTrees' => 'nullable|integer',
            'country' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (
                        $value &&
                        !Country::where('country_name', $value)
                            ->orWhere('country_id', $value)
                            ->exists()
                    ) {
                        $fail('The selected country is invalid.');
                    }
                }
            ],
            'status' => 'nullable|string|max:255',
            'approvelStatus' => 'nullable|string|max:255',
        ]);

        // Convert country to country_id if needed
        if (!empty($validated['country'])) {
            $country = Country::where('country_name', $validated['country'])
                ->orWhere('country_id', $validated['country'])
                ->first();

            $validated['country'] = $country
                ? $country->country_id
                : $validated['country'];
        }

        // Update itinerary
        $itinerary->update($validated);

        // Attach country name and id after update
        if ($itinerary->country) {
            $country = Country::where('country_id', $itinerary->country)
                ->orWhere('country_name', $itinerary->country)
                ->first();

            $itinerary->country_name = $country?->country_name;
            $itinerary->country_id = $country?->country_id;
        } else {
            $itinerary->country_name = null;
            $itinerary->country_id = null;
        }

        Log::info('Admin itinerary updated successfully', [
            'admin_id' => $admin->id,
            'itinerary_id' => $id
        ]);

        return response()->json([
            'message' => 'Itinerary updated successfully',
            'data' => $itinerary
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
   

//     public function destroy(Request $request, string $userId, string $itineraryId)
// {
//     Log::info('Delete itinerary called', [
//         'passed_user_id' => $userId,
//         'itinerary_id'   => $itineraryId,
//     ]);

//     // Find itinerary belongs to user
//     $itinerary = ItineraryData::where('ItineraryId', $itineraryId)
//         ->where('userId', $userId)
//         ->first();

//     if (!$itinerary) {
//         Log::warning('Itinerary not found for delete', [
//             'user_id'      => $userId,
//             'itinerary_id' => $itineraryId,
//         ]);

//         return response()->json([
//             'status'  => false,
//             'message' => 'Itinerary not found for this user'
//         ], 404);
//     }

//     // Delete itinerary
//     $itinerary->delete();

//     Log::info('Itinerary deleted successfully', [
//         'user_id'      => $userId,
//         'itinerary_id' => $itineraryId,
//     ]);

//     return response()->json([
//         'status'  => true,
//         'message' => 'Itinerary deleted successfully'
//     ]);
// }

 public function destroy(Request $request, string $userId, string $itineraryId)
{
    Log::info('Delete itinerary called', [
        'passed_user_id' => $userId,
        'itinerary_id'   => $itineraryId,
    ]);

    // Find itinerary belongs to user
    $itinerary = ItineraryData::where('ItineraryId', $itineraryId)
        ->where('userId', $userId)
        ->first();

    if (!$itinerary) {
        Log::warning('Itinerary not found for delete', [
            'user_id'      => $userId,
            'itinerary_id' => $itineraryId,
        ]);

        return response()->json([
            'status'  => false,
            'message' => 'Itinerary not found for this user'
        ], 404);
    }

    Log::info('Itinerary deleted successfully', [
        'user_id'      => $userId,
        'itinerary_id' => $itineraryId,
    ]);

    // Track deleted itinerary details
    try {
        $user = \App\Models\User::where('userId', $userId)->first();
        $userName = $user ? ($user->name ?? $user->userName) : null;

        if (!$userName && $userId) {
            $userData = \App\Models\UserData::where('userId', $userId)->first();
            $userName = $userData ? $userData->userName : 'Unknown';
        }

        \App\Models\DeleteItinerary::create([
            'origin' => $itinerary->origin,
            'originCity' => $itinerary->originCity,
            'destination' => $itinerary->destination,
            'destinationCity' => $itinerary->destinationCity,
            'class' => $itinerary->class,
            'userName' => $userName,
            'deleted_date' => now(),
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to create DeleteItinerary record for ItineraryId ' . $itineraryId . ': ' . $e->getMessage());
    }

    // Delete itinerary
    $itinerary->delete();

    return response()->json([
        'status'  => true,
        'message' => 'Itinerary deleted successfully'
    ]);
}
}
