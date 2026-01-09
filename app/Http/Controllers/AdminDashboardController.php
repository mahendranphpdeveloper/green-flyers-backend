<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItineraryData;
use App\Models\SingleItineraryData;
use App\Models\AdminData;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Check admin authentication
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
    // Total users chart
    public function getMonthlyUsersChart(Request $request)
    {
        // Authenticate admin before proceeding
        if ($response = $this->checkAdmin($request)) {
            Log::warning('Admin check failed for getMonthlyUsersChart');
            return $response;
        }

        $year = (int) $request->get('year', now()->year);
        $previousYear = $year - 1;

        // ---------- Current Year Monthly Data ----------
        $currentYearData = array_fill(1, 12, 0);

        $currentUsers = User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        foreach ($currentUsers as $month => $count) {
            $currentYearData[$month] = $count;
        }

        $currentYearTotal = array_sum($currentYearData);

        // ---------- Previous Year Total ----------
        $previousYearTotal = User::whereYear('created_at', $previousYear)->count();

        // ---------- Growth / Decrease Percentage ----------
        if ($previousYearTotal > 0) {
            $growthPercentage = round(
                (($currentYearTotal - $previousYearTotal) / $previousYearTotal) * 100,
                2
            );
        } else {
            $growthPercentage = 0;
        }

        return response()->json([
            'year' => $year,
            'total_users' => $currentYearTotal,
            'growth_percentage' => $growthPercentage,
            'growth_type' => $growthPercentage >= 0 ? 'increase' : 'decrease',

            'months' => [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ],
            'data' => array_values($currentYearData)
        ]);
    }
    // User distribution chart 
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
                    ["name" => "Fully Offset", "value" => 0],
                    ["name" => "Partial Offset", "value" => 0],
                    ["name" => "No Offset", "value" => 0],
                ],
            ]);
        }

        // Fully Offset → completed
        $completedCount = ItineraryData::whereYear('created_at', $year)
            ->where('status', 'completed')
            ->count();

        // Partial Offset → partial 
        $partialOffsetCount = ItineraryData::whereYear('created_at', $year)
            ->where('status', 'partial')
            ->count();



        // No Offset → pending 
        $noOffsetCount = ItineraryData::whereYear('created_at', $year)
            ->where('status', 'pending')
            ->count();

        // Percentage calculations
        $fullyOffsetPercentage    = round(($completedCount / $totalItineraries) * 100, 2);
        $partialOffsetPercentage  = round(($partialOffsetCount / $totalItineraries) * 100, 2);
        $noOffsetPercentage       = round(($noOffsetCount / $totalItineraries) * 100, 2);

        return response()->json([
            'year' => (int) $year,
            'data' => [
                ["name" => "Fully Offset",   "value" => $fullyOffsetPercentage],
                ["name" => "Partial Offset", "value" => $partialOffsetPercentage],
                ["name" => "No Offset",      "value" => $noOffsetPercentage],
            ],
        ]);
    }
    // chart for project types
    public function getProjectTypesChart(Request $request)
    {
        // Admin check
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }
    
        // Year from frontend
        $year = $request->get('year', now()->year);
    
        // Fetch projectTypes column
        $itineraries = SingleItineraryData::whereYear('created_at', $year)
            ->whereNotNull('projectTypes')
            ->pluck('projectTypes');
    
        $projectCounts = [];
        $totalProjectCount = 0;
    
        foreach ($itineraries as $projectTypesString) {
    
            // Convert comma-separated string to array
            $projectTypesArray = array_map(
                'trim',
                explode(',', $projectTypesString)
            );
    
            foreach ($projectTypesArray as $projectType) {
    
                if ($projectType === '') {
                    continue;
                }
    
                if (!isset($projectCounts[$projectType])) {
                    $projectCounts[$projectType] = 0;
                }
    
                $projectCounts[$projectType]++;
                $totalProjectCount++;
            }
        }
    
        // Sort by highest count
        arsort($projectCounts);
    
        // Frontend-required format
        $data = [];
        foreach ($projectCounts as $projectType => $count) {
            $data[] = [
                'project_type' => $projectType,
                'count' => $count
            ];
        }
    
        return response()->json([
            'year' => (int) $year,
            'data' => $data,
            'total_project' => $totalProjectCount
        ]);
    }
}
