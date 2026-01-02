<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeManageController extends Controller
{
    /**
     * GET /api/admin/manage-home
     * Example method for returning home carousel data
     */
    public function getHomeCarousel(Request $request)
    {
        // Typically you'd fetch this from a database, for now return a static example
        return response()->json([
            'status' => true,
            'data' => [
                [
                    'id' => 1,
                    'image' => 'carousel1.png',
                    'text' => 'Welcome to the home page!',
                ],
                [
                    'id' => 2,
                    'image' => 'carousel2.png',
                    'text' => 'Discover amazing offsets.',
                ],
            ]
        ]);
    }
}
