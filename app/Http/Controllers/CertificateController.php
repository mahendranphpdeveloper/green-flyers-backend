<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    /**
     * Check admin authentication (copied from AdminDashboardController)
     */
    // private function checkAdmin(Request $request)
    // {
    //     $admin = $request->user();

    //     if (!$admin || !AdminData::where('id', $admin->id)->exists()) {
    //         Log::warning('Unauthorized admin access attempt.', [
    //             'admin_id' => $admin ? $admin->id : null,
    //             'ip' => $request->ip()
    //         ]);
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Unauthorized admin access'
    //         ], 403);
    //     }

    //     return null;
    // }

   
    public function view(Request $request, $fileName)
    {
        // Full path in storage/app/public/certificates
        $filePath = storage_path('app/public/certificates/' . $fileName);

        // Check if file exists
        if (!file_exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'Certificate not found.'
            ], 404);
        }

        // Return file as response
        return response()->file($filePath, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        ]);
    }
}
