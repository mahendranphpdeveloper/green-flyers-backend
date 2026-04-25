<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShareController extends Controller
{
    /**
     * Store a new share (API endpoint)
     * Disabled for static testing
     */
    public function store(Request $request)
    {
        return response()->json([
            'status' => true,
            'id' => rand(1, 100),
            'message' => 'Static mode: Data not saved to database'
        ], 200);
    }

    /**
     * Show the share page (Web route with Bot Detection - STATIC VERSION)
     */
    public function show($id)
    {
        // Static dummy data for testing without database
        $data = (object)[
            'title' => "My Green Journey - Shared Content #$id",
            'description' => "I just offset my carbon emissions with Green Flyers! Join me in making the world a greener place.",
            'image_url' => "https://greenflyers.com/assets/images/share-preview.png" 
        ];

        $userAgent = request()->header('User-Agent');
        $isBot = preg_match('/facebook|twitter|whatsapp|linkedin|telegram|bot|crawl|slurp|spider/i', $userAgent);

        if ($isBot) {
            return response("
            <!DOCTYPE html>
            <html lang=\"en\">
              <head>
                <meta charset=\"UTF-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                
                <!-- Open Graph -->
                <meta property=\"og:title\" content=\"{$data->title}\" />
                <meta property=\"og:description\" content=\"{$data->description}\" />
                <meta property=\"og:image\" content=\"{$data->image_url}\" />
                <meta property=\"og:type\" content=\"website\" />
                <meta property=\"og:url\" content=\"" . url()->current() . "\" />
                
                <!-- Twitter Tags -->
                <meta name=\"twitter:card\" content=\"summary_large_image\">
                <meta name=\"twitter:title\" content=\"{$data->title}\">
                <meta name=\"twitter:description\" content=\"{$data->description}\">
                <meta name=\"twitter:image\" content=\"{$data->image_url}\">
                
                <title>{$data->title}</title>
              </head>
              <body>
                <h1>{$data->title}</h1>
                <p>{$data->description}</p>
              </body>
            </html>
            ")->header('Content-Type', 'text/html');
        }

        // Human users are redirected to the frontend dashboard with the share_id
        return redirect("/dashboard?share_id=$id");
    }
}
