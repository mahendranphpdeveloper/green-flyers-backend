<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminData;

class AdminCertificateController extends Controller
{


public function download(Request $request, $path)
{
    $admin = $request->user();

    // Check if authenticated & is admin
    if (!$admin || !AdminData::where('id', $admin->id)->exists()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // The SingleItineraryController.php (store) stores files using:
    // $path = $file->store('certificates', 'public');
    // So $path is relative to 'public' disk, in 'certificates'
    $filePath = "certificates/{$path}";

    if (!Storage::disk('public')->exists($filePath)) {
        return response()->json(['message' => 'File not found'], 404);
    }

    // Make sure the file is a certificate (avoid arbitrary path traversal)
    $realPath = Storage::disk('public')->path($filePath);
    $certDir = Storage::disk('public')->path('certificates');
    if (strpos(realpath($realPath), realpath($certDir)) !== 0) {
        return response()->json(['message' => 'Invalid file path'], 400);
    }

    return response()->download($realPath);
}

}