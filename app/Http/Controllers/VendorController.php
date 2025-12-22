<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorsData;

class VendorController extends Controller
{
    /**
     * GET /api/vendors
     * List all vendors
     */
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => VendorsData::all()
        ]);
    }

    /**
     * POST /api/vendors
     * Create a new vendor
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'projects'    => 'nullable|string|max:255',
            'status'      => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'projectUrl'  => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'state'       => 'nullable|string|max:255',
            'country'     => 'nullable|string|max:255',
        ]);

        $vendor = VendorsData::create($request->only([
            'name',
            'projects',
            'status',
            'description',
            'projectUrl',
            'email',
            'state',
            'country'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Vendor created successfully',
            'data' => $vendor
        ], 201);
    }

    /**
     * GET /api/vendors/{id}
     * Get vendor by ID
     */
    public function show($id)
    {
        $vendor = VendorsData::find($id);

        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $vendor
        ], 200);
    }

    /**
     * PUT /api/vendors/{id}
     * Update vendor
     */
    public function update(Request $request, $id)
    {
        $vendor = VendorsData::find($id);

        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'projects'    => 'nullable|string|max:255',
            'status'      => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'projectUrl'  => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'state'       => 'nullable|string|max:255',
            'country'     => 'nullable|string|max:255',
        ]);

        $vendor->update($request->only([
            'name',
            'projects',
            'status',
            'description',
            'projectUrl',
            'email',
            'state',
            'country'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Vendor updated successfully',
            'data' => $vendor
        ], 200);
    }

    /**
     * DELETE /api/vendors/{id}
     * Delete vendor
     */
    public function destroy($id)
    {
        $vendor = VendorsData::find($id);

        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        $vendor->delete();

        return response()->json([
            'status' => true,
            'message' => 'Vendor deleted successfully'
        ], 200);
    }
}
