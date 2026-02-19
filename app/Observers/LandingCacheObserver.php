<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LandingCacheObserver
{
    /**
     * Handle the saved event for any of the monitored models.
     */
    public function saved(): void
    {
        $this->clearCache();
    }

    /**
     * Handle the deleted event for any of the monitored models.
     */
    public function deleted(): void
    {
        $this->clearCache();
    }

    /**
     * Centralized cache clearing logic.
     */
    private function clearCache(): void
    {
        Cache::forget('landing_page_content');
        Log::info('Landing page cache cleared via Observer.');
    }
}
