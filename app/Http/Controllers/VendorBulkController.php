<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\VendorsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\VendorsData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VendorBulkController extends Controller
{
    /**
     * POST /api/vendors/import
     * Bulk import vendors via Excel/CSV
     */
    public function bulkUpload(Request $request)
    {
        $admin = $request->user();

        // Check if admin
        if (!$admin || !\App\Models\AdminData::where('id', $admin->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access',
            ], 403);
        }

        // Validate file upload
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // max 5MB
        ]);

        try {
            // Import vendors using your VendorsImport class
            Excel::import(new VendorsImport, $request->file('file'));

            // Fetch all vendors after import for API response
            $vendors = VendorsData::all()->map(function ($vendor) {
                $vendorArr = $vendor->toArray();

                // Decode projects JSON
                $vendorArr['projects'] = isset($vendorArr['projects'])
                    ? json_decode($vendorArr['projects'], true)
                    : [];

                // Decode projectUrl JSON if multiple URLs
                if (!empty($vendorArr['projectUrl']) && str_starts_with($vendorArr['projectUrl'], '[')) {
                    $decodedUrls = json_decode($vendorArr['projectUrl'], true);
                    $vendorArr['projectUrl'] = is_array($decodedUrls) ? $decodedUrls : $vendorArr['projectUrl'];
                }

                // Attach logo URL if exists
                $vendorArr['logo'] = !empty($vendorArr['logo']) && Storage::disk('public')->exists($vendorArr['logo'])
                    ? Storage::url($vendorArr['logo'])
                    : null;

                return $vendorArr;
            });

            return response()->json([
                'status' => true,
                'message' => 'Vendors imported successfully',
                'data' => $vendors
            ], 201);

        } catch (\Exception $e) {
            Log::error('Vendor bulk import failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Vendor import failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
