<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\ActivityLog;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Cache;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::ordered()->withCount('subscriptions')->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Convert features string to array
        if (!empty($validated['features'])) {
            $validated['features'] = array_map('trim', explode("\n", $validated['features']));
        }

        $plan = SubscriptionPlan::create($validated);

        ActivityLog::log('plan_created', $plan);
        Cache::forget('active_plans');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(SubscriptionPlan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if (!empty($validated['features'])) {
            $validated['features'] = array_map('trim', explode("\n", $validated['features']));
        }

        $plan->update($validated);

        ActivityLog::log('plan_updated', $plan);
        Cache::forget('active_plans');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return redirect()->route('admin.plans.index')
                ->with('error', 'Cannot delete a plan with existing subscriptions.');
        }

        ActivityLog::log('plan_deleted', $plan);
        $plan->delete();
        Cache::forget('active_plans');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}
