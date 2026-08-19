<?php

namespace App\Http\Controllers;

use App\Models\GuidePage;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LandingController extends Controller
{
    public function index()
    {
        $plans = Cache::remember('active_plans', 3600, function () {
            return SubscriptionPlan::active()->ordered()->get();
        });

        $guides = Cache::remember('published_guides', 3600, function () {
            return GuidePage::published()->ordered()->limit(6)->get();
        });

        return view('landing.index', compact('plans', 'guides'));
    }
}
