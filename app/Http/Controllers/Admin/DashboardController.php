<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RagService;
use App\Services\StatisticsService;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = app(StatisticsService::class)->getDashboardStats();

        // Get RAG system stats
        $ragService = app(RagService::class);
        $ragStats = $ragService->getStats();
        $ragHealth = $ragService->healthCheck();

        return view('admin.dashboard', compact('stats', 'ragStats', 'ragHealth'));
    }
}
