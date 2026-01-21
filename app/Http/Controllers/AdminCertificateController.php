<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminData;

class AdminCertificateController extends Controller
{


    public function download(Request $request, $path)
    {
        // $path already includes "certificates/..."
        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Security check (prevent path traversal)
        $realPath = Storage::disk('public')->path($path);
        $certDir  = Storage::disk('public')->path('certificates');

        if (strpos(realpath($realPath), realpath($certDir)) !== 0) {
            return response()->json(['message' => 'Invalid file path'], 400);
        }

        // Force download with a user-friendly filename (if available)
        $filename = basename($realPath);

        return response()->download($realPath, $filename, [
            'Content-Type' => mime_content_type($realPath),
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
    

}