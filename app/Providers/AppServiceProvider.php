<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\HomeCarouselData;
use App\Models\HomeCardData;
use App\Models\HomeFaqData;
use App\Models\FaqVisualSection;
use App\Models\CallToAction;
use App\Models\BackgroundImage;
use App\Models\PrivacyPolicy;
use App\Models\TeamofServices;
use App\Models\ServicesPolicyContent;
use App\Observers\LandingCacheObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        HomeCarouselData::observe(LandingCacheObserver::class);
        HomeCardData::observe(LandingCacheObserver::class);
        HomeFaqData::observe(LandingCacheObserver::class);
        FaqVisualSection::observe(LandingCacheObserver::class);
        CallToAction::observe(LandingCacheObserver::class);
        BackgroundImage::observe(LandingCacheObserver::class);
        PrivacyPolicy::observe(LandingCacheObserver::class);
        TeamofServices::observe(LandingCacheObserver::class);
        ServicesPolicyContent::observe(LandingCacheObserver::class);
    }

}
