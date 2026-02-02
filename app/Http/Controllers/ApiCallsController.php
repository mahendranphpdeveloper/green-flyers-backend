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

public function getAllFromDb()
{
    // Use Eloquent model for from_db table access
    $records = \App\Models\FromDb::all();

    // Format each record if needed
    $formatted = $records->map(function ($record) {
        return [
            'id' => $record->id,
            'api_call_id' => 'api_' . $record->api_call_id,
            'origin' => $record->origin,
            'destination' => $record->destination,
            'travel_date' => $record->travel_date,
            'cabin_class' => $record->cabin_class,
            'co2_per_passenger' => $record->co2_per_passenger,
            'co2_tonnes' => round($record->co2_per_passenger / 1000, 3),
            'co2_kg' => round($record->co2_per_passenger, 2),
            'used_at' => $record->used_at,
            'used_by_user' => $record->used_by_user,
            'passengers' => $record->passengers,
        ];
    });

    return response()->json([
        'status' => true,
        'data' => $formatted,
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
