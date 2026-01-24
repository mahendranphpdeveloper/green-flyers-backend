<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;
use App\Models\HomeCarouselData;
use App\Models\HomeCardData;
use App\Models\HomeFaqData;
use App\Models\FaqVisualSection;
use App\Models\CallToAction;
use App\Models\BackgroundImage;
use App\Models\PrivacyPolicy;
use App\Models\TeamofServices;

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
    public function getHomeCarousel()
    {
        $carousel = HomeCarouselData::orderBy('order')->get();
        Log::info('Home carousel fetched.', [
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
            'title' => 'required|string',
            'subtitle' => 'nullable|string',
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
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
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

    public function getHomeCards()
    {
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
            'title' => 'required|string',
            'subtitle' => 'nullable|string',
            'gradient' => 'nullable|string',
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
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'gradient' => 'nullable|string',
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
    public function getHomeFAQ()
    {
        $faqs = HomeFaqData::orderBy('order')->get()->map(function ($faq) {
            $faq->isActive = $faq->isActive === 'true';
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

    //store faq
    public function storeHomeFAQ(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'question' => 'required|string',
            'answer'   => 'required|string',
            'order'    => 'nullable|integer',
            'isActive' => 'nullable|boolean'
        ]);

        $storeData = [
            'question' => $request->question,
            'answer'   => $request->answer,
        ];

        if ($request->has('order')) {
            $storeData['order'] = $request->order;
        }

        // Boolean in → VARCHAR stored
        if ($request->has('isActive')) {
            $storeData['isActive'] = $request->boolean('isActive')
                ? 'true'
                : 'false';
        } else {
            // Optional default
            $storeData['isActive'] = 'true';
        }

        $faq = HomeFaqData::create($storeData);

        // Convert back to boolean for response
        $faq->isActive = $faq->isActive === 'true';

        Log::info('FAQ created', [
            'faq_id' => $faq->id
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'FAQ created successfully',
            'data'    => $faq
        ], 201);
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
            'question' => 'nullable|string',
            'answer' => 'nullable|string',
            'order' => 'nullable|integer',
            'isActive' => 'nullable|boolean'
        ]);

        $updateData = [];

        if ($request->has('question')) {
            $updateData['question'] = $request->question;
        }

        if ($request->has('answer')) {
            $updateData['answer'] = $request->answer;
        }

        if ($request->has('order')) {
            $updateData['order'] = $request->order;
        }

        //  Boolean in → VARCHAR stored
        if ($request->has('isActive')) {
            $updateData['isActive'] = $request->boolean('isActive')
                ? 'true'
                : 'false';
        }

        $faq->update($updateData);

        // Convert back to boolean for response
        $faq->isActive = $faq->isActive === 'true';

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

    /**
     * GET /api/admin/faq/visual-section
     */
    public function getHomeVisualSection()
    {
        $section = FaqVisualSection::first();

        if ($section) {
            $data = $section->toArray();

            // Convert JSON fields back to arrays for response if needed
            if (isset($data['floating_cards']) && is_string($data['floating_cards'])) {
                $data['floating_cards'] = json_decode($data['floating_cards'], true);
            }
            if (isset($data['quick_stats']) && is_string($data['quick_stats'])) {
                $data['quick_stats'] = json_decode($data['quick_stats'], true);
            }
        } else {
            $data = null;
        }

        Log::info('FAQ visual section fetched');

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * PUT /api/admin/faq/visual-section
     */
    public function updateHomeVisualSection(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        //  Convert JSON strings → arrays (ONLY for multipart/form-data)
        if (is_string($request->floatingCards)) {
            $request->merge([
                'floatingCards' => json_decode($request->floatingCards, true)
            ]);
        }

        if (is_string($request->quickStats)) {
            $request->merge([
                'quickStats' => json_decode($request->quickStats, true)
            ]);
        }

        //  Now validation will PASS
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'floatingCards' => 'required|array',
            'quickStats' => 'required|array'
        ]);

        $section = FaqVisualSection::first();

        // Image handling
        if ($request->hasFile('image')) {

            if ($section && $section->image && Storage::disk('public')->exists($section->image)) {
                Storage::disk('public')->delete($section->image);
            }

            $imagePath = $request->file('image')->store('faq_visual', 'public');
        } else {
            $imagePath = $section->image ?? null;
        }

        $data = FaqVisualSection::updateOrCreate(
            ['id' => $section->id ?? 1],
            [
                'image' => $imagePath,
                'floating_cards' => $request->floatingCards,
                'quick_stats' => $request->quickStats
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'FAQ visual section updated successfully',
            'data' => $data
        ]);
    }

    /**
     * GET CTA 1 (id = 1)
     */
    public function getHomeCallToAction1()
    {
        $cta = CallToAction::find(1);

        Log::info('CTA 1 fetched');

        return response()->json([
            'status' => true,
            'data' => $cta
        ]);
    }

    /**
     * PUT CTA 1 (id = 1)
     */
    public function updateHomeCallToAction1(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $cta = CallToAction::updateOrCreate(
            ['id' => 1],
            [
                'title' => $request->title,
                'description' => $request->description
            ]
        );

        Log::info('CTA 1 updated', ['id' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Call To Action 1 updated successfully',
            'data' => $cta
        ]);
    }

    /**
     * GET CTA 2 (id = 2)
     */
    public function getHomeCallToAction2()
    {
        $cta = CallToAction::find(2);

        Log::info('CTA 2 fetched');

        return response()->json([
            'status' => true,
            'data' => $cta
        ]);
    }

    /**
     * PUT CTA 2 (id = 2)
     */
    public function updateHomeCallToAction2(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $cta = CallToAction::updateOrCreate(
            ['id' => 2],
            [
                'title' => $request->title,
                'description' => $request->description
            ]
        );

        Log::info('CTA 2 updated', ['id' => 2]);

        return response()->json([
            'status' => true,
            'message' => 'Call To Action 2 updated successfully',
            'data' => $cta
        ]);
    }

    /**
     * GET /api/admin/bgimage
     */
    public function getLoginBackgroundImage()
    {
        $bgImage = BackgroundImage::find(1);

        Log::info('Login background image fetched');

        return response()->json([
            'status' => true,
            'data' => $bgImage
        ]);
    }

    /**
     * PUT /api/admin/bgimage
     */
    public function updateLoginBackgroundImage(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $request->validate([
            'background_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120'
        ]);

        $record = BackgroundImage::find(1);

        // Delete old image if exists
        if ($record && $record->background_image && Storage::disk('public')->exists($record->background_image)) {
            Storage::disk('public')->delete($record->background_image);
        }

        // Store new image
        $path = $request->file('background_image')
            ->store('login_background', 'public');

        $bgImage = BackgroundImage::updateOrCreate(
            ['id' => 1],
            ['background_image' => $path]
        );

        Log::info('Login background image updated', [
            'path' => $path
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Background image updated successfully',
            'data' => $bgImage
        ]);
    }

    public function updateTreeOffsetValue(Request $request)
    {
        if ($response = $this->checkAdmin($request)) {
            Log::warning('Admin check failed for updateTreeOffsetValue');
            return $response;
        }

        // VALIDATION
        $validated = $request->validate([
            'treeOffsetsValue' => 'required|integer|min:1'
        ]);

        // FETCH RECORD
        $backgroundImage = BackgroundImage::first();

        if (!$backgroundImage) {
            Log::warning('Background image record not found in updateTreeOffsetValue');
            return response()->json([
                'status' => false,
                'message' => 'Background image record not found'
            ], 404);
        }

        // UPDATE VALUE
        $oldValue = $backgroundImage->treeOffsetsValue;
        $backgroundImage->treeOffsetsValue = $validated['treeOffsetsValue'];
        $backgroundImage->save();

        // LOGGING
        Log::info('Tree offset value updated', [
            'admin_id' => optional($request->user())->id,
            'old_treeOffsetsValue' => $oldValue,
            'new_treeOffsetsValue' => $backgroundImage->treeOffsetsValue
        ]);

        // RESPONSE
        return response()->json([
            'status' => true,
            'message' => 'Tree offset value updated successfully',
            'data' => [
                'treeOffsetsValue' => $backgroundImage->treeOffsetsValue
            ]
        ]);
    }

    // public function getTreeOffsetValue(Request $request)
    // {
    //     if ($response = $this->checkAdmin($request)) {
    //         Log::warning('Admin check failed for getTreeOffsetValue');
    //         return $response;
    //     }

    //     $backgroundImage = BackgroundImage::select('treeOffsetsValue')->first();

    //     if (!$backgroundImage) {
    //         Log::warning('Background image record not found in getTreeOffsetValue');
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Background image record not found'
    //         ], 404);
    //     }

    //     Log::info('Tree offset value fetched', [
    //         'admin_id' => optional($request->user())->id,
    //         'treeOffsetsValue' => $backgroundImage->treeOffsetsValue
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'treeOffsetsValue' => (int) $backgroundImage->treeOffsetsValue
    //     ]);
    // }

    public function getTreeOffsetValue(Request $request)
    {
        $backgroundImage = BackgroundImage::select('treeOffsetsValue')->first();

        if (!$backgroundImage) {
            Log::warning('Background image record not found in getTreeOffsetValue');
            return response()->json([
                'status' => false,
                'message' => 'Background image record not found'
            ], 404);
        }

        Log::info('Tree offset value fetched', [
            'treeOffsetsValue' => $backgroundImage->treeOffsetsValue
        ]);

        return response()->json([
            'status' => true,
            'treeOffsetsValue' => (int) $backgroundImage->treeOffsetsValue
        ]);
    }


    //get terms and conditions
     public function getHomeTerms()
    {
        $services = TeamOfServices::orderBy('order')->get()->map(function ($service) {
        $service->isActive = $service->isActive === 'true';
        $service->content  = json_decode($service->content, true);
        return $service;
    });

    Log::info('Team of services fetched', [
        'count' => $services->count()
    ]);

    return response()->json([
        'status' => true,
        'data'   => $services
    ]);
    }

    //store terms and conditions
    // public function storeHomeTerms(Request $request)
    // {
    //      if ($response = $this->checkAdmin($request)) {
    //     return $response;
    //    }

    // $request->validate([
    //     'title'    => 'required|string',
    //     'content'  => 'required|array',
    //     'order'    => 'nullable|integer',
    //     'isActive' => 'nullable|boolean'
    // ]);

    // $storeData = [
    //     'title'   => $request->title,
    //     'content' => json_encode($request->content),
    //     'order'   => $request->order ?? 0,
    //     'isActive'=> $request->has('isActive') && $request->boolean('isActive')
    //                     ? 'true'
    //                     : 'false'
    // ];

    // $service = TeamOfServices::create($storeData);

    // // Convert to boolean for response
    // $service->isActive = $service->isActive === 'true';

    // Log::info('Team of service created', [
    //     'service_id' => $service->id
    // ]);

    // return response()->json([
    //     'status'  => true,
    //     'message' => 'Team of service created successfully',
    //     'data'    => $service
    // ], 201);
    // }

    public function storeHomeTerms(Request $request)
{
    if ($response = $this->checkAdmin($request)) {
        return $response;
    }

    $validated = $request->validate([
        'title'       => 'required|string|max:255',
        'content'     => 'required|array',
        'content.*'   => 'required|string',
        'order'       => 'nullable|integer',
        'isActive'    => 'nullable|boolean',
    ]);

    $service = TeamOfServices::create([
        'title'    => $validated['title'],
        'content'  => $validated['content'], 
        'order'    => $validated['order'] ?? 0,
        'isActive' => $request->boolean('isActive'),
    ]);

    Log::info('Team of service created', [
        'service_id' => $service->id
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Team of service created successfully',
        'data'    => $service
    ], 201);
}




    //update terms and conditions
    // public function updateHomeTerms(Request $request, $id)
    // {
    //     if ($response = $this->checkAdmin($request)) {
    //     return $response;
    // }

    // $service = TeamOfServices::find($id);

    // if (!$service) {
    //     Log::warning('Team of service update failed - not found', ['id' => $id]);

    //     return response()->json([
    //         'status'  => false,
    //         'message' => 'Team of service not found'
    //     ], 404);
    // }

    // $request->validate([
    //     'title'    => 'nullable|string',
    //     'content'  => 'nullable|array',
    //     'order'    => 'nullable|integer',
    //     'isActive' => 'nullable|boolean'
    // ]);

    // $updateData = [];

    // if ($request->has('title')) {
    //     $updateData['title'] = $request->title;
    // }

    // if ($request->has('content')) {
    //     $updateData['content'] = $request->content;
    // }

    // if ($request->has('order')) {
    //     $updateData['order'] = $request->order;
    // }

    // if ($request->has('isActive')) {
    //     $updateData['isActive'] = $request->boolean('isActive')
    //         ? 'true'
    //         : 'false';
    // }

    // $service->update($updateData);

    // $service->isActive = $service->isActive === 'true';

    // Log::info('Team of service updated', [
    //     'service_id' => $service->id
    // ]);

    // return response()->json([
    //     'status'  => true,
    //     'message' => 'Team of service updated successfully',
    //     'data'    => $service
    // ]);
    // }

    public function updateHomeTerms(Request $request, $id)
{
    if ($response = $this->checkAdmin($request)) {
        return $response;
    }

    $service = TeamOfServices::find($id);

    if (!$service) {
        Log::warning('Team of service update failed - not found', ['id' => $id]);

        return response()->json([
            'status'  => false,
            'message' => 'Team of service not found'
        ], 404);
    }

    $validated = $request->validate([
        'title'       => 'nullable|string|max:255',
        'content'     => 'nullable|array',
        'content.*'   => 'required|string',
        'order'       => 'nullable|integer',
        'isActive'    => 'nullable|boolean',
    ]);

    $service->update([
        'title'    => $validated['title']   ?? $service->title,
        'content'  => $validated['content'] ?? $service->content,
        'order'    => $validated['order']   ?? $service->order,
        'isActive' => $request->has('isActive')
                        ? $request->boolean('isActive')
                        : $service->isActive,
    ]);

    Log::info('Team of service updated', [
        'service_id' => $service->id
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Team of service updated successfully',
        'data'    => $service
    ]);
}



    //delete Terms and conditions
    public function deleteHomeTerms(Request $request, $id)
    {
       if ($response = $this->checkAdmin($request)) {
        return $response;
    }

    $service = TeamOfServices::find($id);

    if (!$service) {
        Log::warning('Team of service delete failed - not found', ['id' => $id]);

        return response()->json([
            'status'  => false,
            'message' => 'Team of service not found'
        ], 404);
    }

    $service->delete();

    Log::info('Team of service deleted', [
        'service_id' => $id
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'Team of service deleted successfully'
    ]);
    }

}
