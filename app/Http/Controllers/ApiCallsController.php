<?php

namespace App\Http\Controllers;

use App\Models\ApiCall;
use App\Models\FromDb;
use App\Models\ItineraryData;
use App\Models\Country;
use App\Models\NotificationsReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiCallsController extends Controller
{

    public function apiCallsDashboardStats()
    {
        $freshApiCalls = ApiCall::count();
        $reusedCalls   = FromDb::count();
    
        $totalRequests = $freshApiCalls + $reusedCalls;
    
        $apiCallsSavedPercentage = $totalRequests > 0
            ? round(($reusedCalls / $totalRequests) * 100)
            : 0;
    
        return response()->json([
            'total_requests'   => $totalRequests,
            'fresh_api_calls'  => $freshApiCalls,
            'reused_calls'     => $reusedCalls,
            'api_calls_saved'  => $apiCallsSavedPercentage . '%',
        ]);
    }

//     public function getEmissionDetails(Request $request)
// {
//     $validated = $request->validate([
//         'origin'      => 'required|string|size:3',
//         'destination' => 'required|string|size:3',
//         'date'        => 'required|date',
//         'class'       => 'required|string',
//     ]);

//     $apiCall = ApiCall::where([
//         'origin'      => $validated['origin'],
//         'destination' => $validated['destination'],
//         'travel_date' => $validated['date'],
//         'cabin_class' => $validated['class'],
//     ])->first();

//     if (!$apiCall) {
//         return response()->json([
//             'status'  => false,
//             'message' => 'No emission record found'
//         ], 404);
//     }

//     // Decode reuse history JSON
//     $reuseHistory = $apiCall->reuse_history
//         ? json_decode($apiCall->reuse_history, true)
//         : [];

//     return response()->json([
//         'status' => true,
//         'data' => [
//             'api_call_id' => 'api_' . $apiCall->id,
//             'route' => "{$apiCall->origin} → {$apiCall->destination}",
//             'travel_date' => $apiCall->travel_date,
//             'cabin_class' => $apiCall->cabin_class,
//             'co2_per_passenger' => $apiCall->co2_per_passenger,
//             'co2_display' => [
//                 'tonnes' => round($apiCall->co2_per_passenger / 1000, 3),
//                 'kg'     => round($apiCall->co2_per_passenger, 2),
//             ],
//             'source' => $apiCall->source, // tim / db
//             'calculated_on' => $apiCall->created_at,
//             'reuse_history' => $reuseHistory,
//             'total_reuses'  => count($reuseHistory),
//         ]
//     ]);
// }

public function getApiCallDetails()
{
    $apiCalls = ApiCall::orderBy('created_at', 'desc')->get();

    $data = $apiCalls->map(function ($apiCall) {
        return [
            'id' => $apiCall->id,
            'api_call_id' => 'api_' . $apiCall->id,

            'origin' => $apiCall->origin,
            'destination' => $apiCall->destination,
            'route' => "{$apiCall->origin} → {$apiCall->destination}",

            'travel_date' => $apiCall->travel_date,
            'cabin_class' => $apiCall->cabin_class,

            'co2_per_passenger' => round($apiCall->co2_per_passenger, 2),
            'co2_kg' => round($apiCall->co2_per_passenger, 2),
            'co2_tonnes' => round($apiCall->co2_per_passenger / 1000, 3),

            'source' => $apiCall->source, // db / tim

            'reuse_history' => $apiCall->reuse_history, // JSON as-is

            'created_at' => optional($apiCall->created_at)->toDateTimeString(),
            'updated_at' => optional($apiCall->updated_at)->toDateTimeString(),
        ];
    });

    return response()->json([
        'status' => true,
        'count'  => $data->count(),
        'data'   => $data,
    ]);
}

public function getApiCallById($api_call_id)
{
    // Extract numeric ID from api_x format
    if (!preg_match('/^api_(\d+)$/', $api_call_id, $matches)) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid API Call ID format'
        ], 400);
    }

    $id = $matches[1];

    // Fetch the API call with user relationship
    $apiCall = \App\Models\ApiCall::with('user')->find($id);

    if (!$apiCall) {
        return response()->json([
            'status'  => false,
            'message' => 'API Call not found'
        ], 404);
    }

    // Format response: include origin and destination
    $data = [
        'id' => $apiCall->id,
        'api_call_id' => 'api_' . $apiCall->id,

        'origin' => $apiCall->origin,
        'destination' => $apiCall->destination,
        'originCity' => $apiCall->originCity ?? $apiCall->origin,        
        'destinationCity' => $apiCall->destinationCity ?? $apiCall->destination,  
        'route' => "{$apiCall->origin} → {$apiCall->destination}",

        'travel_date' => $apiCall->travel_date,
        'cabin_class' => $apiCall->cabin_class,

        'co2_per_passenger' => round($apiCall->co2_per_passenger, 2),
        'co2_kg' => round($apiCall->co2_per_passenger, 2),
        'co2_tonnes' => round($apiCall->co2_per_passenger / 1000, 3),

        'source' => $apiCall->source, // db / tim
        'reuse_history' => $apiCall->reuse_history, // JSON as-is

        'username' => $apiCall->user ? $apiCall->user->name : null, // from user table

        'created_at' => optional($apiCall->created_at)->toDateTimeString(),
        'updated_at' => optional($apiCall->updated_at)->toDateTimeString(),
    ];

    return response()->json([
        'status' => true,
        'data'   => $data,
    ]);
}


// public function getAllFromDb()
// {
//     // Fetch all records from from_db table
//     $records = FromDb::orderBy('id', 'desc')->get();   

//     // Group records by api_call_id
//     $grouped = $records->groupBy('api_call_id')->map(function ($group, $api_call_id) {
//         // get all unique origins, destinations, and travel_dates for this group
//         $origins = $group->pluck('origin')->unique()->values();
//         $destinations = $group->pluck('destination')->unique()->values();
//         $travel_dates = $group->pluck('travel_date')->unique()->values();

//         return [
//             'api_call_id' => 'api_' . $api_call_id,
//             'reusable_count' => $group->count(),
//             'origin' => $origins,      // Array of unique origins
//             'destination' => $destinations, // Array of unique destinations
//             'travel_date' => $travel_dates, // Array of unique travel_dates
//             'data' => $group->map(function ($record) {
//                 return [
//                     'id' => $record->id,
//                     'origin' => $record->origin,
//                     'destination' => $record->destination,
//                     'travel_date' => $record->travel_date,
//                     'cabin_class' => $record->cabin_class,
//                     'co2_per_passenger' => $record->co2_per_passenger,
//                     'co2_kg' => round($record->co2_per_passenger, 2),
//                     'co2_tonnes' => round($record->co2_per_passenger / 1000, 3),
//                     'used_at' => $record->used_at,
//                     'used_by_user' => $record->used_by_user,
//                     'passengers' => $record->passengers,
//                 ];
//             })->values(), // reset keys
//         ];
//     })->values(); // reset keys

//     return response()->json([
//         'status' => true,
//         'count' => $grouped->count(),
//         'data' => $grouped,
//     ]);
// }

public function getAllFromDb()
{
    // Fetch all records and eager load username (from UserData) via used_by_user
    $records = FromDb::orderBy('id', 'desc')->get();

    // Collect all user ids from used_by_user to minimize DB queries
    $userIds = $records->pluck('used_by_user')->unique()->filter();
    // Fetch username indexed by userId from UserData
    $usernames = \App\Models\UserData::whereIn('userId', $userIds)
        ->pluck('userName', 'userId');

    // Group records by api_call_id
    $grouped = $records->groupBy('api_call_id')->map(function ($group, $api_call_id) use ($usernames) {

        // Unique values for group-level info
        $originCities = $group->pluck('originCity')->unique()->values();
        $destinationCities = $group->pluck('destinationCity')->unique()->values();
        $origins = $group->pluck('origin')->unique()->values();
        $destinations = $group->pluck('destination')->unique()->values();
        $travel_dates = $group->pluck('travel_date')->unique()->values();

        return [
            'api_call_id' => 'api_' . $api_call_id,
            'reusable_count' => $group->count(),

            // City names, and separately IATA/ICAO codes
            'originCity' => $originCities,
            'destinationCity' => $destinationCities,
            'origin' => $origins,
            'destination' => $destinations,

            'travel_date' => $travel_dates,

            'data' => $group->map(function ($record) use ($usernames) {
                return [
                    'id' => $record->id,

                    // city names
                    'originCity' => $record->originCity,
                    'destinationCity' => $record->destinationCity,

                    // IATA or ICAO codes (or raw code value)
                    'origin' => $record->origin,
                    'destination' => $record->destination,

                    'travel_date' => $record->travel_date,
                    'cabin_class' => $record->cabin_class,

                    'co2_per_passenger' => round($record->co2_per_passenger, 2),
                    'co2_kg' => round($record->co2_per_passenger, 2),
                    'co2_tonnes' => round($record->co2_per_passenger / 1000, 3),

                    'used_at' => $record->used_at,
                    'passengers' => $record->passengers,

                    // The user ID is stored in used_by_user, so get username from cache
                    'username' => $record->used_by_user && isset($usernames[$record->used_by_user])
                        ? $usernames[$record->used_by_user]
                        : null,
                ];
            })->values(),
        ];
    })->values();

    return response()->json([
        'status' => true,
        'count'  => $grouped->count(),
        'data'   => $grouped,
    ]);
}



public function getFromDbByApiCallId($api_call_id)
{
    // Extract numeric ID from api_x format
    if (!preg_match('/^api_(\d+)$/', $api_call_id, $matches)) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid API Call ID format'
        ], 400);
    }

    $id = $matches[1];

    // Fetch all records from from_db table with user relation
    $records = \App\Models\FromDb::with('user')
        ->where('api_call_id', $id)
        ->orderBy('id', 'desc')
        ->get();

    if ($records->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No records found for this API call'
        ], 404);
    }

    // Use the first record for global origin/destination values
    $firstRecord = $records->first();
    $globalOrigin = $firstRecord ? $firstRecord->origin : null;
    $globalDestination = $firstRecord ? $firstRecord->destination : null;

    // Format the grouped response
    $data = [
        'api_call_id' => 'api_' . $id,
        'origin' => $globalOrigin,
        'destination' => $globalDestination,
        'reusable_count' => $records->count(),
        'data' => $records->map(function ($record) {
            return [
                'id' => $record->id,
                'originCity' => $record->origin,
                'destinationCity' => $record->destination,
                'origin' => $record->origin,           // Added as per instruction
                'destination' => $record->destination, // Added as per instruction
                'travel_date' => $record->travel_date,
                'cabin_class' => $record->cabin_class,
                'co2_per_passenger' => round($record->co2_per_passenger, 2),
                'co2_kg' => round($record->co2_per_passenger, 2),
                'co2_tonnes' => round($record->co2_per_passenger / 1000, 3),
                'used_at' => $record->used_at,
                'passengers' => $record->passengers,
                'username' => $record->user ? $record->user->name : null,
            ];
        })->values(), // reset keys
    ];

    return response()->json([
        'status' => true,
        'data'   => $data,
    ]);
}

    /**
     * VERIFY API — Check if emission exists in api_calls
     * POST /api/emission/verify
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'origin'       => 'required|string|size:3',
            'destination'  => 'required|string|size:3',
            'date'         => 'required|date',
            'class'        => 'required|string',
        ]);

        $apiData = ApiCall::where([
            'origin'      => $validated['origin'],
            'destination' => $validated['destination'],
            'travel_date' => $validated['date'],
            'cabin_class' => $validated['class'],
        ])->first();

        if ($apiData) {
            return response()->json([
                'isApiCall'         => true,
                'co2_per_passenger' => $apiData->co2_per_passenger,
                'source'            => 'db'
            ]);
        }

        return response()->json([
            'isApiCall' => false
        ]);
    }

    /**
     * STORE ITINERARY AND EMISSION
     */
    // public function storeEmission(Request $request)
    // {
    //     Log::info('storeEmission called', $request->all());

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
    //         'totalTrees'      => 'nullable|integer|min:0',
    //         'co2_per_passenger'=> 'nullable|numeric|min:0',
    //         'country' => [
    //             'nullable', 'string', 'max:255',
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

    //     // Check pending itineraries against limit
    //     $reminderSettings = NotificationsReminder::first();
    //     if ($reminderSettings) {
    //         $pendingCount = ItineraryData::where('userId', $validated['userId'])
    //             ->where('status', 'pending')
    //             ->count();

    //         if ($pendingCount >= $reminderSettings->limite_itineraries) {
    //             return response()->json([
    //                 'status'       => false,
    //                 'message'      => 'Please complete previous pending offsets before adding a new itinerary.',
    //                 'pendingCount' => $pendingCount,
    //                 'limit'        => $reminderSettings->limite_itineraries
    //             ], 400);
    //         }
    //     }

    //     // Normalize country to country_id
    //     if (!empty($validated['country'])) {
    //         $country = Country::where('country_name', $validated['country'])
    //             ->orWhere('country_id', $validated['country'])
    //             ->first();
    //         $validated['country'] = $country?->country_id;
    //     }

    //     DB::transaction(function () use (&$itinerary, $validated) {

    //         $co2PerPassenger = $validated['co2_per_passenger'] ?? 0;
    //         $totalEmission = $co2PerPassenger * $validated['passengers'];

    //         /** ---------------- STORE OR UPDATE IN api_calls ---------------- */
    //         if ($co2PerPassenger > 0) {
    //             ApiCall::updateOrCreate(
    //                 [
    //                     'origin'      => $validated['origin'],
    //                     'destination' => $validated['destination'],
    //                     'travel_date' => $validated['date'],
    //                     'cabin_class' => $validated['class'],
    //                 ],
    //                 [
    //                     'co2_per_passenger' => $co2PerPassenger,
    //                     'source'            => 'db',
    //                 ]
    //             );
    //         }

    //         /** ---------------- STORE IN itinerarydata ---------------- */
    //         $itinerary = ItineraryData::create([
    //             ...$validated,
    //             'offsetAmount'     => 0,
    //             'numberOfTrees'    => 0,
    //             'offsetPercentage' => 0,
    //             'emission'         => $totalEmission,
    //             'status'           => 'pending',
    //         ]);
    //     });

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Itinerary created successfully. Offset credit is not auto-applied.',
    //         'data'    => ItineraryData::where('userId', $validated['userId'])->get()
    //     ], 201);
    // }

    public function storeEmission(Request $request)
    {
        Log::info('storeEmission called', $request->all());

        $validated = $request->validate([
            'userId'       => 'required|integer',
            'date'         => 'required|date',
            'airline'      => 'required|string|max:255',
            'origin'       => 'required|string|max:255',
            'destination'  => 'required|string|max:255',
            'class'        => 'required|string|max:255',
            'passengers'   => 'required|integer|min:1',
            'tripType'     => 'required|string|max:255',
            'distance'     => 'required|string|max:255',
            'flightcode'      => 'nullable|string|max:255',
            'originCity'      => 'nullable|string|max:255',
            'destinationCity' => 'nullable|string|max:255',
            'emission'        => 'required|numeric|min:0',
            'totalTrees'      => 'nullable|integer|min:0',
            'co2_per_passenger'=> 'nullable|numeric|min:0',
            'country' => [
                'nullable', 'string', 'max:255',
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
            'status' => 'required|string|max:255',
        ]);

        // Check pending itineraries against limit
        $reminderSettings = NotificationsReminder::first();
        if ($reminderSettings) {
            $pendingCount = ItineraryData::where('userId', $validated['userId'])
                ->where('status', 'pending')
                ->count();

            if ($pendingCount >= $reminderSettings->limite_itineraries) {
                return response()->json([
                    'status'       => false,
                    'message'      => 'Please complete previous pending offsets before adding a new itinerary.',
                    'pendingCount' => $pendingCount,
                    'limit'        => $reminderSettings->limite_itineraries
                ], 400);
            }
        }

        // Normalize country to country_id
        if (!empty($validated['country'])) {
            $country = Country::where('country_name', $validated['country'])
                ->orWhere('country_id', $validated['country'])
                ->first();
            $validated['country'] = $country?->country_id;
        }

        DB::transaction(function () use (&$itinerary, $validated) {

            $co2PerPassenger = $validated['co2_per_passenger'] ?? 0;
            $totalEmission   = $co2PerPassenger * $validated['passengers'];

            /** ---------------- Check if value exists in api_calls ---------------- */
            $apiCall = \App\Models\ApiCall::where([
                'origin'      => $validated['origin'],
                'destination' => $validated['destination'],
                'travel_date' => $validated['date'],
                'cabin_class' => $validated['class'],
            ])->first();

            if ($apiCall) {
                // ---------------- EXISTING DB VALUE → store in from_db ----------------
                \App\Models\FromDb::create([
                    'api_call_id'        => $apiCall->id,
                    'origin'             => $validated['origin'],
                    'destination'        => $validated['destination'],
                    'travel_date'        => $validated['date'],
                    'cabin_class'        => $validated['class'],
                    'co2_per_passenger'  => $co2PerPassenger,
                    'used_at'            => now()->toDateTimeString(),
                    'used_by_user'       => $validated['userId'],
                    'passengers'         => $validated['passengers'],
                ]);
            } else {
                // ---------------- FIRST TIME TIM API → store in api_calls ----------------
                $apiCall = \App\Models\ApiCall::create([
                    'origin'              => $validated['origin'],
                    'destination'         => $validated['destination'],
                    'travel_date'         => $validated['date'],
                    'cabin_class'         => $validated['class'],
                    'co2_per_passenger'   => $co2PerPassenger,
                    'source'              => 'tim',
                    'reuse_history'       => json_encode([]),
                ]);
            }

            // ---------------- Always store in itinerarydata ----------------
            $itinerary = ItineraryData::create([
                ...$validated,
                'offsetAmount'     => 0,
                'numberOfTrees'    => 0,
                'offsetPercentage' => 0,
                'emission'         => $totalEmission,
                'status'           => 'pending',
                // optionally store reference to api_call or from_db entry
                // 'api_call_id'      => $apiCall->id,
            ]);
        });

        return response()->json([
            'status'  => true,
            'message' => 'Itinerary created successfully. Offset credit is not auto-applied.',
            'data'    => ItineraryData::where('userId', $validated['userId'])->get()
        ], 201);
    }

    
}
