<?php

namespace App\Http\Controllers;

use App\Models\GuidePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GuidePageController extends Controller
{
    public function index()
    {
        $pages = Cache::remember('published_guide_pages', 3600, function () {
            return GuidePage::published()->ordered()->get();
        });

        $categories = Cache::remember('guide_categories', 3600, function () use ($pages) {
            return $pages->pluck('category')->filter()->unique();
        });

        return view('guide.index', compact('pages', 'categories'));
    }

    public function show(string $slug)
    {
        $page = Cache::remember("guide_page_{$slug}", 3600, function () use ($slug) {
            return GuidePage::published()->where('slug', $slug)->firstOrFail();
        });

        return view('guide.show', compact('page'));
    }
}
