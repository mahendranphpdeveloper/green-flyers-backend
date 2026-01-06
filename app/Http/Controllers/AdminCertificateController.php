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

    if (!AdminData::where('id', $admin->id)->exists()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $filePath = 'certificates/' . $path; // adjust folder

    if (!Storage::disk('public')->exists($filePath)) {
        return response()->json(['message' => 'File not found'], 404);
    }

    return response()->download(Storage::disk('public')->path($filePath));
}

}