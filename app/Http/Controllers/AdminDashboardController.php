<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItineraryData; // adjust model name if different
use Carbon\Carbon;
use App\Models\AdminData;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function getUserDistributionCharts(Request $request)
    {
        // --- Admin check same as HomeManageController checkAdmin (18-34) ---
        $admin = $request->user();

        if (!$admin || !AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Unauthorized admin access attempt.', [
                'admin_id' => $admin ? $admin->id : null,
                'ip' => $request->ip()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access'
            ], 403);
        }
        // ---------------------------------------------------------

        // Year from frontend (default current year)
        $year = $request->get('year', now()->year);

        // Base query filtered by year
        $query = ItineraryData::whereYear('created_at', $year);

        // Total itineraries for the year
        $totalItineraries = $query->count();

        if ($totalItineraries === 0) {
            return response()->json([
                'year' => (int)$year,
                'fully_offset'   => 0,
                'partial_offset' => 0,
                'no_offset'      => 0,
            ]);
        }

        // Fully Offset → completed
        $completedCount = ItineraryData::whereYear('created_at', $year)
            ->where('status', 'completed')
            ->count();

        // Partial Offset → pending
        $partialOffsetCount = ItineraryData::whereYear('created_at', $year)
            ->where('status', 'pending')
            ->count();

        // No Offset → pending (same status, different frontend meaning)
        $noOffsetCount = $partialOffsetCount;

        // Percentage calculations
        $fullyOffsetPercentage = round(($completedCount / $totalItineraries) * 100, 2);
        $partialOffsetPercentage = round(($partialOffsetCount / $totalItineraries) * 100, 2);
        $noOffsetPercentage = round(($noOffsetCount / $totalItineraries) * 100, 2);

        return response()->json([
            'year' => (int) $year,
            'total_itineraries' => $totalItineraries,
            'fully_offset' => $fullyOffsetPercentage,
            'partial_offset' => $partialOffsetPercentage,
            'no_offset' => $noOffsetPercentage,
        ]);
    }
}
