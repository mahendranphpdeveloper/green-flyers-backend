<?php

namespace App\Http\Controllers;

use App\Models\SocialMetaTag;
use App\Models\UserShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SocialMetaTagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $metaTags = SocialMetaTag::all();
        return response()->json([
            'status' => true,
            'data' => $metaTags
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $metaTag = SocialMetaTag::create($request->only(['title', 'description']));

        return response()->json([
            'status' => true,
            'message' => 'Social meta tags created successfully.',
            'data' => $metaTag
        ]);
    }

    /**
     * Display the specified resource by ID.
     */
    public function show($id)
    {
        $metaTag = SocialMetaTag::find($id);

        if (!$metaTag) {
            return response()->json([
                'status' => false,
                'message' => 'Meta tags not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $metaTag
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $metaTag = SocialMetaTag::find($id);

        if (!$metaTag) {
            return response()->json([
                'status' => false,
                'message' => 'Meta tags not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $metaTag->update($request->only(['title', 'description']));

        return response()->json([
            'status' => true,
            'message' => 'Social meta tags updated successfully.',
            'data' => $metaTag
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $metaTag = SocialMetaTag::find($id);

        if (!$metaTag) {
            return response()->json([
                'status' => false,
                'message' => 'Meta tags not found.'
            ], 404);
        }

        $metaTag->delete();

        return response()->json([
            'status' => true,
            'message' => 'Meta tags deleted successfully.'
        ]);
    }

    /**
     * Store a unique user share with a screenshot.
     */
    public function storeUserShare(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:userdata,userId', // Associated with userdata table
            'social_meta_tag_id' => 'required|exists:social_meta_tags,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB
            'shared_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('User share storage validation failed', ['errors' => $validator->errors(), 'request' => $request->all()]);
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        Log::info('Storing user share', [
            'user_id' => $request->user_id,
            'social_meta_tag_id' => $request->social_meta_tag_id,
            'has_image' => $request->hasFile('image'),
            'shared_url' => $request->shared_url
        ]);

        // Check if a share already exists for this user and meta tag
        $userShare = UserShare::where('user_id', $request->user_id)
                             ->where('social_meta_tag_id', $request->social_meta_tag_id)
                             ->first();

        // Delete old image if it exists
        if ($userShare && $userShare->image_path) {
            // Extract the relative path from the URL (ends with shares/filename)
            $oldFilename = basename($userShare->image_path);
            $oldRelativePath = 'shares/' . $oldFilename;
            
            if (Storage::disk('public')->exists($oldRelativePath)) {
                Storage::disk('public')->delete($oldRelativePath);
                Log::info('Deleted old user share image', ['path' => $oldRelativePath]);
            }
        }

        // Store the new image
        $path = $request->file('image')->store('shares', 'public');
        $imageUrl = asset('storage/app/public/' . $path);

        if ($userShare) {
            // Update existing record
            $userShare->update([
                'image_path' => $imageUrl,
                'shared_url' => $request->shared_url,
            ]);
            Log::info('Updated existing user share', ['share_id' => $userShare->id]);
        } else {
            // Create new record
            $userShare = UserShare::create([
                'user_id' => $request->user_id,
                'social_meta_tag_id' => $request->social_meta_tag_id,
                'image_path' => $imageUrl,
                'shared_url' => $request->shared_url,
            ]);
            Log::info('Created new user share', ['share_id' => $userShare->id]);
        }

        // Load relationship to include meta data in response
        $userShare->load('metaTag');

        return response()->json([
            'status' => true,
            'data' => [
                'shared_url' => url('/share/'.$userShare->id),
            ]
        ]);
    }

    /**
     * Handle social media sharing 
     */
  

    public function share($id)
{
    $userShare = UserShare::with('metaTag')->find($id);

    if (!$userShare || !$userShare->metaTag) {
        abort(404);
    }

    // ✅ escaped values
    $title = e($userShare->metaTag->title ?? '');
    
    // 🔥 BULLETPROOF DESCRIPTION FIX
    $rawDescription = $userShare->metaTag->description ?? '';
    
    // Debug log to check raw data
    \Log::info('DESC CHECK', [
        'raw' => $rawDescription
    ]);

    $description = strip_tags($rawDescription);      // remove HTML
    $description = html_entity_decode($description); // decode entities
    $description = trim($description);               // remove whitespace
    $description = substr($description, 0, 200);     // limit length for LinkedIn
    $description = e($description);                  // final escape for HTML

    // ✅ Make sure image is absolute URL
    $image = $userShare->image_path;
    if (!str_starts_with($image, 'http')) {
        $image = asset($image);
    }

    $redirectUrl = $userShare->shared_url ?? 'https://jayamdesigners.co.in/green-flyers16';

    $userAgent = request()->header('User-Agent') ?? '';

    // ✅ Improved bot detection (Explicitly check LinkedIn)
    $isBot = preg_match('/facebookexternalhit|Facebot|Twitterbot|LinkedInBot|WhatsApp|TelegramBot|Slackbot|Discordbot|Googlebot|bingbot/i', $userAgent);
    $isLinkedIn = strpos($userAgent, 'LinkedInBot') !== false;

    \Log::info('Social share access', [
        'share_id' => $id,
        'is_bot' => (bool)$isBot,
        'is_linkedin' => $isLinkedIn,
        'user_agent' => $userAgent,
        'title' => $title,
        'description' => $description,
    ]);

    // ✅ BOT: Return metadata page
    if ($isBot) {

        $html = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>

            <title>{$title}</title>

            <!-- Primary Meta -->
            <meta name='title' content='{$title}'>
            <meta name='description' content='{$description}'>

            <!-- Open Graph / Facebook / LinkedIn -->
            <meta property='og:type' content='website'>
            <meta property='og:url' content='" . url()->current() . "'>
            <meta property='og:title' content='{$title}'>
            <meta property='og:description' content='{$description}'>
            <meta property='og:image' content='{$image}'>
            <meta property='og:site_name' content='Green Flyers'>

            <!-- Twitter -->
            <meta name='twitter:card' content='summary_large_image'>
            <meta name='twitter:url' content='" . url()->current() . "'>
            <meta name='twitter:title' content='{$title}'>
            <meta name='twitter:description' content='{$description}'>
            <meta name='twitter:image' content='{$image}'>
        </head>
        <body>
            <h1>{$title}</h1>
            <p>{$description}</p>
            <img src='{$image}' alt='preview'>
        </body>
        </html>
        ";

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->header('Accept-Ranges', 'none'); // 🔥 prevents 206 issue
    }

    // ✅ REAL USER: redirect
    return redirect()->away($redirectUrl);
}
}
