<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItineraryData;
use App\Models\AdminData;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Check admin authentication (copied from HomeManageController)
     */
    private function checkAdmin(Request $request)
    {
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

        return null;
    }

    public function getUserDistributionCharts(Request $request)
    {
        // Authenticate admin before proceeding
        if ($response = $this->checkAdmin($request)) {
            Log::warning('Admin check failed for getUserDistributionCharts');
            return $response;
        }

        // Year from frontend (default current year)
        $year = $request->get('year', now()->year);

        // Base query filtered by year
        $query = ItineraryData::whereYear('created_at', $year);

        // Total itineraries for the year
        $totalItineraries = $query->count();

        // Prepare default response if there are no itineraries
        if ($totalItineraries === 0) {
            return response()->json([
                'year' => (int) $year,
                'data' => [
                    [ "name" => "Fully Offset", "value" => 0 ],
                    [ "name" => "Partial Offset", "value" => 0 ],
                    [ "name" => "No Offset", "value" => 0 ],
                ],
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
        $fullyOffsetPercentage    = round(($completedCount / $totalItineraries) * 100, 2);
        $partialOffsetPercentage  = round(($partialOffsetCount / $totalItineraries) * 100, 2);
        $noOffsetPercentage       = round(($noOffsetCount / $totalItineraries) * 100, 2);

        return response()->json([
            'year' => (int) $year,
            'data' => [
                [ "name" => "Fully Offset",   "value" => $fullyOffsetPercentage ],
                [ "name" => "Partial Offset", "value" => $partialOffsetPercentage ],
                [ "name" => "No Offset",      "value" => $noOffsetPercentage ],
            ],
        ]);
    }
}
