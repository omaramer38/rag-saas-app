<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuidePage;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Cache;
use Illuminate\Support\Str;

class GuideController extends Controller
{
    public function index()
    {
        $pages = GuidePage::with('creator')->ordered()->get();

        return view('admin.guide.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.guide.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:guide_pages',
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
            'is_published' => 'boolean',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['created_by'] = auth()->id();

        $page = GuidePage::create($validated);

        ActivityLog::log('guide_created', $page);
        self::clearGuideCache();

        return redirect()->route('admin.guide.index')
            ->with('success', 'Guide page created successfully.');
    }

    public function edit(GuidePage $guide)
    {
        return view('admin.guide.edit', compact('guide'));
    }

    public function update(Request $request, GuidePage $guide)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "nullable|string|max:255|unique:guide_pages,slug,{$guide->id}",
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
            'is_published' => 'boolean',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $guide->update($validated);

        ActivityLog::log('guide_updated', $guide);
        self::clearGuideCache();

        return redirect()->route('admin.guide.index')
            ->with('success', 'Guide page updated successfully.');
    }

    public function destroy(GuidePage $guide)
    {
        ActivityLog::log('guide_deleted', $guide);
        $guide->delete();
        self::clearGuideCache();

        return redirect()->route('admin.guide.index')
            ->with('success', 'Guide page deleted successfully.');
    }

    private static function clearGuideCache(): void
    {
        Cache::forget('published_guides');
        Cache::forget('published_guide_pages');
        Cache::forget('guide_categories');
    }
}
