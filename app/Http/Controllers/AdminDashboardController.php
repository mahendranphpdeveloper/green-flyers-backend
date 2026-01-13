<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItineraryData;
use App\Models\SingleItineraryData;
use App\Models\AdminData;
use App\Models\User;
use App\Models\VendorsData;
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

    //Dashboard stats
    
    // public function getAdminDashboardStats(Request $request)
    // {
    //     // ------------------ ADMIN AUTH ------------------
    //     if ($response = $this->checkAdmin($request)) {
    //         Log::warning('Admin check failed for getAdminDashboardStats');
    //         return $response;
    //     }

    //     $activeMonths   = 6;
    //     $activeFromDate = Carbon::now()->subMonths($activeMonths);

    //     $totalEnrolledUsers = ItineraryData::select('userId')
    //         ->selectRaw('MAX(GREATEST(created_at, updated_at)) as last_activity')
    //         ->groupBy('userId')
    //         ->having('last_activity', '>=', $activeFromDate)
    //         ->count();

    //     $totalOffsetTonnes = round(
    //         ((float) ItineraryData::sum('offsetAmount') +
    //             (float) User::sum('offsetCredit')) / 1000,
    //         2
    //     );
    //     $totalTreesPlanted = (int) ItineraryData::sum('numberOfTrees');

    //     $vendorsProjects = VendorsData::whereNotNull('projects')->pluck('projects');

    //     $uniqueProjects = [];

    //     foreach ($vendorsProjects as $projectsJson) {
    //         $projects = json_decode($projectsJson, true);

    //         if (!is_array($projects)) continue;

    //         foreach ($projects as $projectName) {
    //             if (!is_string($projectName)) continue;

    //             $normalized = strtolower(trim($projectName));
    //             if ($normalized !== '') {
    //                 $uniqueProjects[$normalized] = true;
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'active_enrolled_users'  => $totalEnrolledUsers,
    //         'total_emissions_offset' => $totalOffsetTonnes,
    //         'total_trees_planted'    => $totalTreesPlanted,
    //         'total_projects'         => count($uniqueProjects),
    //         'active_user_window'     => "{$activeMonths} months"
    //     ]);
    // }
    public function getAdminDashboardStats(Request $request)
    {
        // ------------------ ADMIN AUTH ------------------
        if ($response = $this->checkAdmin($request)) {
            Log::warning('Admin check failed for getAdminDashboardStats');
            return $response;
        }

        $activeMonths   = 6;
        $activeFromDate = Carbon::now()->subMonths($activeMonths);

        $totalEnrolledUsers = ItineraryData::select('userId')
            ->selectRaw('MAX(GREATEST(created_at, updated_at)) as last_activity')
            ->groupBy('userId')
            ->having('last_activity', '>=', $activeFromDate)
            ->count();

        $totalOffsetTonnes = round(
            ((float) ItineraryData::sum('offsetAmount') +
                (float) User::sum('offsetCredit')) / 1000,
            2
        );

        $totalTreesPlanted = (int) ItineraryData::sum('numberOfTrees');

        $vendorsProjects = VendorsData::whereNotNull('projects')->pluck('projects');

        $uniqueProjects = [];

        foreach ($vendorsProjects as $projectsJson) {
            $projects = json_decode($projectsJson, true);

            if (!is_array($projects)) continue;

            foreach ($projects as $projectName) {
                if (!is_string($projectName)) continue;

                $normalized = strtolower(trim($projectName));
                if ($normalized !== '') {
                    $uniqueProjects[$normalized] = true;
                }
            }
        }

        // Add total users count
        $totalUsers = User::count();

        return response()->json([
            'active_enrolled_users'  => $totalEnrolledUsers,
            'total_users'            => $totalUsers,
            'total_emissions_offset' => $totalOffsetTonnes,
            'total_trees_planted'    => $totalTreesPlanted,
            'total_projects'         => count($uniqueProjects),
            'active_user_window'     => "{$activeMonths} months"
        ]);
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

    //get total tree planted values 

    public function getMonthlyTreesPlantedChart(Request $request)
    {
        // Authenticate admin
        if ($response = $this->checkAdmin($request)) {
            Log::warning('Admin check failed for getMonthlyTreesPlantedChart');
            return $response;
        }

        $year = (int) $request->get('year', now()->year);
        $previousYear = $year - 1;

        // ---------- Current Year Monthly Data ----------
        $currentYearData = array_fill(1, 12, 0);

        $currentTrees = ItineraryData::selectRaw(
            'MONTH(created_at) as month, SUM(numberOfTrees) as total'
        )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        foreach ($currentTrees as $month => $totalTrees) {
            $currentYearData[$month] = (int) $totalTrees;
        }

        $currentYearTotal = array_sum($currentYearData);

        // ---------- Previous Year Total ----------
        $previousYearTotal = ItineraryData::whereYear('created_at', $previousYear)
            ->sum('numberOfTrees');

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
            'total_trees_planted' => $currentYearTotal,
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
    //get carbon offset chart values 
    public function getMonthlyCarbonOffsetChart(Request $request)
    {
        // Authenticate admin
        if ($response = $this->checkAdmin($request)) {
            Log::warning('Admin check failed for getMonthlyCarbonOffsetChart');
            return $response;
        }

        $year = (int) $request->get('year', now()->year);
        $previousYear = $year - 1;

        // ---------- Current Year Monthly Data ----------
        $monthlyRawData = array_fill(1, 12, 0);

        $currentOffsets = ItineraryData::selectRaw(
            'MONTH(created_at) as month, SUM(COALESCE(offsetAmount,0)) as total'
        )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        foreach ($currentOffsets as $month => $offsetTotal) {
            $monthlyRawData[$month] = (float) $offsetTotal;
        }

        // ---------- Convert Monthly Values to Percentage ----------
        $maxMonthValue = max($monthlyRawData);

        $monthlyPercentageData = [];

        foreach ($monthlyRawData as $value) {
            if ($maxMonthValue > 0) {
                $monthlyPercentageData[] = round(($value / $maxMonthValue) * 100, 2);
            } else {
                $monthlyPercentageData[] = 0;
            }
        }

        // ---------- Yearly Totals ----------
        $currentYearTotal = array_sum($monthlyRawData);

        $previousYearTotal = ItineraryData::whereYear('created_at', $previousYear)
            ->sum('offsetAmount');

        // ---------- Growth / Decrease ----------
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

            // Progress value (top right: 99%)
            'progress_percentage' => $maxMonthValue > 0
                ? round((end($monthlyRawData) / $maxMonthValue) * 100, 2)
                : 0,

            'total_carbon_offset' => round($currentYearTotal, 2),
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

            // THIS IS WHAT THE CHART USES
            'data' => $monthlyPercentageData
        ]);
    }

    public function getAdminEmissionOffsetChart(Request $request)
    {
        // Authenticate admin
        if ($response = $this->checkAdmin($request)) {
            Log::warning('Admin check failed for getAdminEmissionOffsetChart');
            return $response;
        }

        $year         = (int) $request->get('year', now()->year);
        $previousYear = $year - 1;

        // ------------------ Monthly defaults ------------------
        $emissionsData = array_fill(1, 12, 0);
        $offsetsData   = array_fill(1, 12, 0);

        // ------------------ CURRENT YEAR: Itinerary sums ------------------
        $currentItineraries = ItineraryData::selectRaw('
            MONTH(created_at) as month,
            SUM(COALESCE(emission,0)) as total_emission,
            SUM(COALESCE(offsetAmount,0)) as total_offset
        ')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->get();

        foreach ($currentItineraries as $row) {
            $emissionsData[$row->month] = (float) $row->total_emission;
            $offsetsData[$row->month]   = (float) $row->total_offset;
        }

        // ------------------ CURRENT YEAR: Add offsetCredit ------------------
        $currentCredits = User::where('offsetCredit', '>', 0)
            ->whereYear('updated_at', $year)
            ->get(['offsetCredit', 'updated_at']);

        foreach ($currentCredits as $user) {
            $month = Carbon::parse($user->updated_at)->month;
            $offsetsData[$month] += (float) $user->offsetCredit;
        }

        // ------------------ CURRENT YEAR TOTALS ------------------
        $currentEmissionTotal = array_sum($emissionsData);
        $currentOffsetTotal   = array_sum($offsetsData);
        // ------------------ Previous year itinerary sums ------------------
        $previousEmissionTotal = (float) ItineraryData::whereYear('created_at', $previousYear)
            ->sum('emission');

        $previousOffsetTotal = (float) ItineraryData::whereYear('created_at', $previousYear)
            ->sum('offsetAmount');

        // ------------------ Previous year offsetCredit ------------------
        $previousCredits = (float) User::where('offsetCredit', '>', 0)
            ->whereYear('updated_at', $previousYear)
            ->sum('offsetCredit');

        $previousOffsetTotal += $previousCredits;


        $emissionGrowthPercentage = $previousEmissionTotal > 0
            ? round((($currentEmissionTotal - $previousEmissionTotal) / $previousEmissionTotal) * 100, 2)
            : 0;

        $offsetGrowthPercentage = $previousOffsetTotal > 0
            ? round((($currentOffsetTotal - $previousOffsetTotal) / $previousOffsetTotal) * 100, 2)
            : 0;

        return response()->json([
            'year' => $year,

            // Chart Data
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
            'emissions' => array_values($emissionsData),
            'offsets'   => array_values($offsetsData),

            // Totals
            'total_emission' => round($currentEmissionTotal, 2),
            'total_offset'   => round($currentOffsetTotal, 2),

            // Growth (SEPARATE)
            'emission_growth_percentage' => $emissionGrowthPercentage,
            'emission_growth_type'       => $emissionGrowthPercentage >= 0 ? 'increase' : 'decrease',

            'offset_growth_percentage'   => $offsetGrowthPercentage,
            'offset_growth_type'         => $offsetGrowthPercentage >= 0 ? 'increase' : 'decrease',
        ]);
    }

    //Pending verfication data 
    public function getPendingVerificationOffsets(Request $request)
    {
        Log::info('Admin Pending Verification SingleItinerary called');

        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $singleItineraries = SingleItineraryData::where('approvelStatus', 'Pending Verification')
            ->orderBy('id', 'desc')
            ->get();

        if ($singleItineraries->isEmpty()) {
            return response()->json([
                'status'  => true,
                'message' => 'No pending verification records found'
            ]);
        }

        $singleItineraries->transform(function ($single) {
            $single->user = User::where('userId', $single->userId)->first();
            $single->itinerary = ItineraryData::where(
                'ItineraryId',
                $single->ItineraryId
            )->first();

            return $single;
        });

        return response()->json([
            'status' => true,
            'count'  => $singleItineraries->count(),
            'data'   => $singleItineraries
        ]);
    }
}
