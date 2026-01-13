<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    /**
     * View certificate file
     * @param string $fileName
     */
    public function view($fileName)
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
