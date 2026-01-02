<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorsData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    /**
     * GET /api/vendors
     * List all vendors
     */
    public function index(Request $request)
    {
        $authUser = $request->user(); // Sanctum resolves automatically

        Log::info('Auth check for vendors index', [
            'authUser' => $authUser,
            'model' => $authUser ? get_class($authUser) : null
        ]);

        if (!$authUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        $isAdmin = $authUser instanceof \App\Models\AdminData;
        $isUser  = $authUser instanceof \App\Models\User;

        Log::info('Role resolved', [
            'isAdmin' => $isAdmin,
            'isUser' => $isUser
        ]);

        if (!$isAdmin && !$isUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Fetch vendors
        $vendors = VendorsData::all();

        $transformed = $vendors->map(function ($vendor) {
            $vendorArr = $vendor->toArray();

            if (isset($vendorArr['projects']) && is_string($vendorArr['projects'])) {
                $decoded = json_decode($vendorArr['projects'], true);
                $vendorArr['projects'] = is_array($decoded) ? $decoded : [];
            }

            // Attach logo URL if exists
            if (!empty($vendorArr['logo'])) {
                // Return a storage URL or null if empty
                $vendorArr['logo_url'] = Storage::disk('public')->exists($vendorArr['logo'])
                    ? Storage::url($vendorArr['logo'])
                    : null;
            } else {
                $vendorArr['logo_url'] = null;
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

        // Fix incoming projects and logo data before validation

        // Handle: If projects sent as JSON string (from e.g. JS clients), decode to array
        if ($request->has('projects')) {
            $projects = $request->input('projects');
            if (is_string($projects)) {
                $decoded = json_decode($projects, true);
                if (is_array($decoded)) {
                    // Overwrite the input value with the array (important for Validator)
                    $request->merge(['projects' => $decoded]);
                }
            }
        }

        // Handle: If logo is sent as empty object, treat as "no file"
        if ($request->has('logo') && is_array($request->input('logo')) && empty($request->input('logo'))) {
            // Remove logo from request if it's an empty object (i.e., `{}`)
            $request->request->remove('logo');
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
            'logo'        => 'nullable|file|image|max:4096', // max 4MB, adjust as needed
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
            // 'logo' will be handled separately
        ]);

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            // Store the file in the "public/vendors" directory
            $path = $request->file('logo')->store('vendors', 'public');
            $data['logo'] = $path;
        }

        // Always ensure projects is JSON or NULL in DB
        if (isset($data['projects'])) {
            // After validation, always projects must be array or null
            $data['projects'] = is_array($data['projects'])
                ? json_encode($data['projects'])
                : json_encode([]);
        }

        $vendor = VendorsData::create($data);

        Log::info('Vendor created', ['vendor_id' => $vendor->id, 'admin_id' => $admin->id]);

        // Attach logo url to response if exists
        $vendorArr = $vendor->toArray();
        // Always decode "projects" as array for output
        if (isset($vendorArr['projects']) && !is_array($vendorArr['projects'])) {
            $decoded = json_decode($vendorArr['projects'], true);
            $vendorArr['projects'] = is_array($decoded) ? $decoded : [];
        } elseif (!isset($vendorArr['projects'])) {
            $vendorArr['projects'] = [];
        }
        if (!empty($vendorArr['logo'])) {
            $vendorArr['logo'] = Storage::url($vendorArr['logo']);
        } else {
            $vendorArr['logo'] = null;
        }

        return response()->json([
            'status' => true,
            'message' => 'Vendor created successfully',
            'data' => $vendorArr
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

        // Always decode projects field for single show as an array
        $vendorArr = $vendor->toArray();
        if (isset($vendorArr['projects']) && !is_array($vendorArr['projects'])) {
            $decoded = json_decode($vendorArr['projects'], true);
            $vendorArr['projects'] = is_array($decoded) ? $decoded : [];
        } elseif (!isset($vendorArr['projects'])) {
            $vendorArr['projects'] = [];
        }
        // Attach logo URL if exists
        if (!empty($vendorArr['logo'])) {
            $vendorArr['logo'] = Storage::disk('public')->exists($vendorArr['logo'])
                ? Storage::url($vendorArr['logo'])
                : null;
        } else {
            $vendorArr['logo'] = null;
        }

        Log::info('Vendor found', ['vendor_id' => $id]);
        return response()->json([
            'status' => true,
            'data' => $vendorArr
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

        // Fix incoming projects and logo data before validation (same logic as store)
        if ($request->has('projects')) {
            $projects = $request->input('projects');
            if (is_string($projects)) {
                $decoded = json_decode($projects, true);
                if (is_array($decoded)) {
                    $request->merge(['projects' => $decoded]);
                }
            }
        }
        if ($request->has('logo') && is_array($request->input('logo')) && empty($request->input('logo'))) {
            $request->request->remove('logo');
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
            'logo'        => 'nullable|file|image|max:4096',
        ]);
        Log::info('Vendor validated for update', ['vendor_id' => $vendor->id, 'admin_id' => $admin->id, 'data' => $request->all()]);

        $data = $request->only([
            'name',
            'projects',
            'status',
            'description',
            'projectUrl',
            'email',
            'state',
            'country'
            // 'logo' handled separately
        ]);

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            // Remove old logo if present
            if ($vendor->logo) {
                Storage::disk('public')->delete($vendor->logo);
            }
            $path = $request->file('logo')->store('vendors', 'public');
            $data['logo'] = $path;
        }

        // Always ensure projects is JSON or NULL in DB
        if (isset($data['projects'])) {
            $data['projects'] = is_array($data['projects'])
                ? json_encode($data['projects'])
                : json_encode([]);
        }

        $vendor->update($data);

        Log::info('Vendor updated', ['vendor_id' => $vendor->id, 'admin_id' => $admin->id]);

        $vendorArr = $vendor->toArray();
        // Always decode projects as array for API response
        if (isset($vendorArr['projects']) && !is_array($vendorArr['projects'])) {
            $decoded = json_decode($vendorArr['projects'], true);
            $vendorArr['projects'] = is_array($decoded) ? $decoded : [];
        } elseif (!isset($vendorArr['projects'])) {
            $vendorArr['projects'] = [];
        }
        if (!empty($vendorArr['logo'])) {
            $vendorArr['logo'] = Storage::url($vendorArr['logo']);
        } else {
            $vendorArr['logo'] = null;
        }

        return response()->json([
            'status' => true,
            'message' => 'Vendor updated successfully',
            'data' => $vendorArr
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

        // Delete logo from storage
        if ($vendor->logo) {
            Storage::disk('public')->delete($vendor->logo);
        }

        $vendor->delete();
        Log::info('Vendor deleted', ['vendor_id' => $id, 'admin_id' => $admin->id]);

        return response()->json([
            'status' => true,
            'message' => 'Vendor deleted successfully'
        ], 200);
    }
}
