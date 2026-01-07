<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\VendorsImport;
use Illuminate\Support\Facades\Validator;

class VendorBulkController extends Controller
{
    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $import = new VendorsImport();

        Excel::import($import, $request->file('file'));

        return response()->json([
            'successCount' => $import->successCount,
            'errorCount'   => count($import->errors),
            'errors'       => $import->errors,
        ], 200);
    }
}
