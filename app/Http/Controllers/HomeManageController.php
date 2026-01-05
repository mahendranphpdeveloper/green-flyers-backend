<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use App\Models\HomeCarouselData;
use App\Models\HomeCardData;

class HomeManageController extends Controller
{
    private function checkAdmin(Request $request)
    {
        $admin = $request->user();

        if (!$admin || !AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Unauthorized admin access attempt.', [
                'admin_id' => $admin ? $admin->id : null,
                'ip' => $request->ip()
            ]);
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
        Log::info('Home carousel fetched.', [
            'admin_id' => $request->user()->id ?? null,
            'count' => $carousel->count()
        ]);

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
            'image' => 'required|image|max:10240',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|boolean'
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
            'isActive' => $request->has('isActive')
                ? $request->boolean('isActive')
                : true,
        ]);

        Log::info('Carousel item added.', [
            'admin_id' => $request->user()->id ?? null,
            'carousel_id' => $carousel->id,
            'title' => $request->title
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
            Log::warning('Attempt to update non-existent carousel item.', [
                'admin_id' => $request->user()->id ?? null,
                'carousel_id' => $id
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Carousel item not found'
            ], 404);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:10240',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|boolean'
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

        // Prepare data for update (without isActive for now)
        $updateData = $request->only([
            'title',
            'subtitle',
            'order'
        ]);

        $carousel->update($updateData);

        // Handle isActive update
        if ($request->has('isActive')) {
            $carousel->isActive = $request->boolean('isActive');
            $carousel->save();
        }

        Log::info('Carousel item updated.', [
            'admin_id' => $request->user()->id ?? null,
            'carousel_id' => $carousel->id
        ]);

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
            Log::warning('Attempt to delete non-existent carousel item.', [
                'admin_id' => $request->user()->id ?? null,
                'carousel_id' => $id
            ]);
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

        Log::info('Carousel item deleted.', [
            'admin_id' => $request->user()->id ?? null,
            'carousel_id' => $id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Carousel item deleted successfully'
        ]);
    }
    //get home-manage cards

    public function getHomeCards(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $cards = HomeCardData::orderBy('order')->get();

        Log::info('Home cards fetched', [
            'count' => $cards->count()
        ]);

        return response()->json([
            'status' => true,
            'data' => $cards
        ]);
    }

    //save home-manage cards
    public function addHomeCards(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'icon' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'gradient' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|boolean'
        ]);

        // Convert isActive to boolean and fallback to true if not provided
        $isActive = $request->has('isActive') ? (bool)$request->isActive : true;

        $card = HomeCardData::create([
            'icon' => $request->icon,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'gradient' => $request->gradient,
            'order' => $request->order ?? 0,
            'isActive' => $isActive
        ]);

        Log::info('Home card created', [
            'card_id' => $card->id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Card added successfully',
            'data' => $card
        ], 201);
    }

    //update home-manage cards
    public function updateHomeCards(Request $request, $id)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $card = HomeCardData::find($id);

        if (!$card) {
            Log::warning('Card update failed - not found', ['card_id' => $id]);

            return response()->json([
                'status' => false,
                'message' => 'Card not found'
            ], 404);
        }

        $request->validate([
            'icon' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'gradient' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|boolean'
        ]);

        // Prepare data for update, coerce isActive to boolean if present
        $updateData = $request->only([
            'icon',
            'title',
            'subtitle',
            'gradient',
            'order'
        ]);
        if ($request->has('isActive')) {
            $updateData['isActive'] = (bool)$request->isActive;
        }

        $card->update($updateData);

        Log::info('Home card updated', [
            'card_id' => $card->id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Card updated successfully',
            'data' => $card
        ]);
    }

    //delete home-manage cards
    public function deleteHomeCards(Request $request, $id)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $card = HomeCardData::find($id);

        if (!$card) {
            Log::warning('Card delete failed - not found', ['card_id' => $id]);

            return response()->json([
                'status' => false,
                'message' => 'Card not found'
            ], 404);
        }

        $card->delete();

        Log::info('Home card deleted', [
            'card_id' => $id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Card deleted successfully'
        ]);
    }

}
