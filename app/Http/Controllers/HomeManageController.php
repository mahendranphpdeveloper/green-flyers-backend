<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminData;
use App\Models\HomeCarouselData;

class HomeManageController extends Controller
{
    private function checkAdmin(Request $request)
    {
        $admin = $request->user();

        if (!$admin || !AdminData::where('id', $admin->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized admin access'
            ], 403);
        }

        return null;
    }

    /**
     * GET /api/admin/carousel
     */
    public function getHomeCarousel(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $carousel = HomeCarouselData::orderBy('order')->get();

        return response()->json([
            'status' => true,
            'data' => $carousel
        ]);
    }

    /**
     * POST /api/admin/carousel
     */
    public function addHomeCarousel(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|string'
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('hero_page', 'public');
        }

        $carousel = HomeCarouselData::create([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'image' => $imagePath,
            'order' => $request->order ?? 0,
            'isActive' => $request->isActive ?? '1'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Carousel item added successfully',
            'data' => $carousel
        ], 201);
    }

    /**
     * PUT /api/admin/carousel/{id}
     */
    public function updateHomeCarousel(Request $request, $id)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $carousel = HomeCarouselData::find($id);

        if (!$carousel) {
            return response()->json([
                'status' => false,
                'message' => 'Carousel item not found'
            ], 404);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|string'
        ]);

        // Image replace
        if ($request->hasFile('image')) {

            // Delete old image
            if ($carousel->image && Storage::disk('public')->exists($carousel->image)) {
                Storage::disk('public')->delete($carousel->image);
            }

            $carousel->image = $request->file('image')
                ->store('hero_page', 'public');
        }

        $carousel->update($request->only([
            'title',
            'subtitle',
            'order',
            'isActive'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Carousel item updated successfully',
            'data' => $carousel
        ]);
    }

    /**
     * DELETE /api/admin/carousel/{id}
     */
    public function deleteHomeCarousel(Request $request, $id)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $carousel = HomeCarouselData::find($id);

        if (!$carousel) {
            return response()->json([
                'status' => false,
                'message' => 'Carousel item not found'
            ], 404);
        }

        // Delete image from storage
        if ($carousel->image && Storage::disk('public')->exists($carousel->image)) {
            Storage::disk('public')->delete($carousel->image);
        }

        $carousel->delete();

        return response()->json([
            'status' => true,
            'message' => 'Carousel item deleted successfully'
        ]);
    }
}
