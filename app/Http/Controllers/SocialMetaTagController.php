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

        Log::info('Storing new user share', [
            'social_meta_tag_id' => $request->social_meta_tag_id,
            'has_image' => $request->hasFile('image'),
            'shared_url' => $request->shared_url
        ]);

        // Store the image and use the path that works on your server
        $path = $request->file('image')->store('shares', 'public');
        $imageUrl = asset('storage/app/public/' . $path);

        $userShare = UserShare::create([
            'social_meta_tag_id' => $request->social_meta_tag_id,
            'image_path' => $imageUrl,
            'shared_url' => $request->shared_url,
        ]);

        // Load relationship to include meta data in response
        $userShare->load('metaTag');

        return response()->json([
            'status' => true,
            'message' => 'User share stored successfully.',
            'share_url' => url('/share/' . $userShare->id), // This is the link to share on social media
            'data' => [
                'share_id' => $userShare->id,
                'title' => $userShare->metaTag->title ?? '',
                'description' => $userShare->metaTag->description ?? '',
                'image_url' => $userShare->image_path
            ]
        ]);
    }

    /**
     * Handle social media sharing with bot detection and redirection.
     */
    public function share($id)
    {
        $userShare = UserShare::with('metaTag')->find($id);

        if (!$userShare || !$userShare->metaTag) {
            abort(404);
        }

        $title = $userShare->metaTag->title;
        $description = $userShare->metaTag->description;
        $image = $userShare->image_path;
        $redirectUrl = $userShare->shared_url ?? 'https://jayamdesigners.co.in/green-flyers16';

        $userAgent = request()->header('User-Agent');

        // Detect social media bots
        $isBot = preg_match('/facebook|twitter|whatsapp|linkedin|telegram|discord|slack|messenger|googlebot|bingbot/i', $userAgent);

        Log::info('Social share access', [
            'share_id' => $id,
            'is_bot' => $isBot,
            'user_agent' => $userAgent,
            'meta_data' => ['title' => $title],
            'redirecting_to' => $isBot ? 'Preview HTML' : $redirectUrl
        ]);

        if ($isBot) {
            return response("
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>{$title}</title>
                <meta property='og:title' content='{$title}' />
                <meta property='og:description' content='{$description}' />
                <meta property='og:image' content='{$image}' />
                <meta property='og:url' content='" . url()->current() . "' />
                <meta property='og:type' content='website' />

                <meta name='twitter:card' content='summary_large_image'>
                <meta name='twitter:title' content='{$title}'>
                <meta name='twitter:description' content='{$description}'>
                <meta name='twitter:image' content='{$image}'>
            </head>
            <body>
                <h1>{$title}</h1>
                <img src='{$image}'>
                <p>{$description}</p>
            </body>
            </html>
            ", 200)->header('Content-Type', 'text/html');
        }

        // Real users will be redirected to the actual frontend page
        return redirect($redirectUrl);
    }
}
