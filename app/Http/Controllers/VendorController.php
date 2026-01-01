<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorsData;
use Illuminate\Support\Facades\Log;

class VendorController extends Controller
{
    /**
     * GET /api/vendors
     * List all vendors
     */
    // public function index(Request $request)
    // {
    //     $user = $request->user();
    //     Log::info('Checking user authentication for vendors index', ['user' => $user]);

    //     // Check for admin or user
    //     if (!$user) {
    //         Log::warning('Unauthorized access - no user in request');
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Unauthorized access'
    //         ], 403);
    //     }

    //     $isAdmin = \App\Models\AdminData::where('id', $user->id)->exists();
    //     $isNormalUser = \App\Models\User::where('userId', $user->id)->exists();

    //     Log::info('User role check', [
    //         'user_id' => $user->id,
    //         'isAdmin' => $isAdmin,
    //         'isNormalUser' => $isNormalUser
    //     ]);

    //     if (!$isAdmin && !$isNormalUser) {
    //         Log::warning('Unauthorized access - not admin or user', ['user_id' => $user->id]);
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Unauthorized access'
    //         ], 403);
    //     }

    //     Log::info('VendorsData::all() called by user', ['user_id' => $user->id]);
    //     return response()->json([
    //         'status' => true,
    //         'data' => VendorsData::all()
    //     ]);
    // }

    public function index(Request $request)
    {
        $user = $request->user();
        Log::info('Checking user authentication for vendors index', ['user' => $user]);

        // Check for admin or user
        if (!$user) {
            Log::warning('Unauthorized access - no user in request');
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $isAdmin = \App\Models\AdminData::where('id', $user->id)->exists();
        $isNormalUser = \App\Models\User::where('userId', $user->id)->exists();

        Log::info('User role check', [
            'user_id' => $user->id,
            'isAdmin' => $isAdmin,
            'isNormalUser' => $isNormalUser
        ]);

        if (!$isAdmin && !$isNormalUser) {
            Log::warning('Unauthorized access - not admin or user', ['user_id' => $user->id]);
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        Log::info('VendorsData::all() called by user', ['user_id' => $user->id]);
        
        // Fetch vendors
        $vendors = VendorsData::all();

        // For each vendor, if projects is a JSON string, convert to array before returning
        $transformed = $vendors->map(function($vendor) {
            $vendorArr = $vendor->toArray();

            if (isset($vendorArr['projects']) && is_string($vendorArr['projects'])) {
                $decoded = json_decode($vendorArr['projects'], true);
                $vendorArr['projects'] = is_array($decoded) ? $decoded : $vendorArr['projects'];
            }

            return $vendorArr;
        });

        return response()->json([
            'status' => true,
            'data' => $transformed
        ]);
    }

    /**
     * POST /api/vendors
     * Create a new vendor
     */
    public function store(Request $request)
    {
        $admin = $request->user();
        Log::info('Checking admin for vendor store', ['user' => $admin]);

        if (
            !$admin ||
            !\App\Models\AdminData::where('id', $admin->id)->exists()
        ) {
            Log::warning('Unauthorized admin access in store', ['user' => $admin]);
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access',
            ], 403);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'projects'    => 'nullable|array',
            'status'      => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'projectUrl'  => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'state'       => 'nullable|string|max:255',
            'country'     => 'nullable|string|max:255',
        ]);
        Log::info('Vendor validated for creation', ['admin_id' => $admin->id, 'data' => $request->all()]);

        $data = $request->only([
            'name',
            'projects',
            'status',
            'description',
            'projectUrl',
            'email',
            'state',
            'country'
        ]);

       
        if (isset($data['projects']) && is_array($data['projects'])) {
            $data['projects'] = json_encode($data['projects']);
        }

        $vendor = VendorsData::create($data);

        Log::info('Vendor created', ['vendor_id' => $vendor->id, 'admin_id' => $admin->id]);

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
    public function show(Request $request, $id)
    {
        $admin = $request->user();
        Log::info('Checking admin for show vendor', ['user' => $admin]);

        if (!$admin || !\App\Models\AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Unauthorized admin access in show', ['user' => $admin, 'vendor_id' => $id]);
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access'
            ], 403);
        }

        $vendor = VendorsData::find($id);

        if (!$vendor) {
            Log::warning('Vendor not found', ['vendor_id' => $id]);
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        Log::info('Vendor found', ['vendor_id' => $id]);
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
        $admin = $request->user();
        Log::info('Checking admin for update vendor', ['user' => $admin]);

        if (!$admin || !\App\Models\AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Unauthorized admin access in update', ['user' => $admin, 'vendor_id' => $id]);
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access'
            ], 403);
        }

        $vendor = VendorsData::find($id);

        if (!$vendor) {
            Log::warning('Vendor not found in update', ['vendor_id' => $id]);
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'projects'    => 'nullable|array',
            'status'      => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'projectUrl'  => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'state'       => 'nullable|string|max:255',
            'country'     => 'nullable|string|max:255',
        ]);
        Log::info('Vendor validated for update', ['vendor_id' => $vendor->id, 'admin_id' => $admin->id, 'data' => $request->all()]);

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

        
        if (isset($data['projects']) && is_array($data['projects'])) {
            $data['projects'] = json_encode($data['projects']);
        }

        Log::info('Vendor updated', ['vendor_id' => $vendor->id, 'admin_id' => $admin->id]);

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
    public function destroy(Request $request, $id)
    {
        $admin = $request->user();
        Log::info('Checking admin for destroy vendor', ['user' => $admin]);

        if (!$admin || !\App\Models\AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Unauthorized admin access in destroy', ['user' => $admin, 'vendor_id' => $id]);
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access'
            ], 403);
        }

        $vendor = VendorsData::find($id);

        if (!$vendor) {
            Log::warning('Vendor not found in destroy', ['vendor_id' => $id]);
            return response()->json([
                'status' => false,
                'message' => 'Vendor not found'
            ], 404);
        }

        $vendor->delete();
        Log::info('Vendor deleted', ['vendor_id' => $id, 'admin_id' => $admin->id]);

        return response()->json([
            'status' => true,
            'message' => 'Vendor deleted successfully'
        ], 200);
    }
}
