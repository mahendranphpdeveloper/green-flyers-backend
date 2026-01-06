<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use App\Models\HomeCarouselData;
use App\Models\HomeCardData;
use App\Models\HomeFaqData;

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
            'isActive' => 'nullable|string' // <--- changed to string
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
                ? (string)$request->isActive
                : 'true',
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
            'isActive' => 'nullable|string' // <--- changed to string
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

        // Handle isActive update - now as string
        if ($request->has('isActive')) {
            $carousel->isActive = (string)$request->isActive;
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
            'icon' => 'nullable|string|max:10240', // Icon sent as text, not a file. Limit length if needed.
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'gradient' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|string'
        ]);

        // Icon is provided as a text (e.g., icon class or base64 string)
        $iconValue = $request->icon ?? null;

        // isActive as string, fallback to 'true' if not provided
        $isActive = $request->has('isActive') ? (string)$request->isActive : 'true';

        $card = HomeCardData::create([
            'icon' => $iconValue,
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
            'icon' => 'nullable|string|max:10240', // Icon sent as text, not a file.
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'gradient' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|string'
        ]);

        // Prepare data for update, isActive as string if present
        $updateData = $request->only([
            'title',
            'subtitle',
            'gradient',
            'order'
        ]);

        if ($request->has('isActive')) {
            $updateData['isActive'] = (string)$request->isActive;
        }
        if ($request->has('icon')) {
            $updateData['icon'] = $request->icon;
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

        // No file deletion needed, icon is not stored in storage

        $card->delete();

        Log::info('Home card deleted', [
            'card_id' => $id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Card deleted successfully'
        ]);
    }

    //get faq 
    public function getHomeFAQ(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $faqs = HomeFaqData::orderBy('order')->get();

        // Ensure isActive is returned as a string in the response
        $faqs->transform(function ($faq) {
            if (isset($faq->isActive)) {
                $faq->isActive = (string)$faq->isActive;
            } else {
                $faq->isActive = 'true'; // fallback if not set
            }
            return $faq;
        });

        Log::info('FAQ list fetched', [
            'count' => $faqs->count()
        ]);

        return response()->json([
            'status' => true,
            'data' => $faqs
        ]);
    }

    //update faq
    public function updateHomeFAQ(Request $request, $id)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $faq = HomeFaqData::find($id);

        if (!$faq) {
            Log::warning('FAQ update failed - not found', ['faq_id' => $id]);

            return response()->json([
                'status' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        $request->validate([
            'question' => 'nullable|string|max:255',
            'answer' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|string'
        ]);

        $updateData = $request->only([
            'question',
            'answer',
            'order'
        ]);

        // If isActive present, update and make sure it is string
        if ($request->has('isActive')) {
            $updateData['isActive'] = (string)$request->isActive;
        }

        $faq->update($updateData);

        // Ensure isActive is string for response
        $faq->isActive = isset($faq->isActive) ? (string)$faq->isActive : 'true';

        Log::info('FAQ updated', [
            'faq_id' => $faq->id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faq
        ]);
    }

    //delete faq
    public function deleteHomeFAQ(Request $request, $id)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $faq = HomeFaqData::find($id);

        if (!$faq) {
            Log::warning('FAQ delete failed - not found', ['faq_id' => $id]);

            return response()->json([
                'status' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        $faq->delete();

        Log::info('FAQ deleted', [
            'faq_id' => $id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FAQ deleted successfully'
        ]);
    }
}
