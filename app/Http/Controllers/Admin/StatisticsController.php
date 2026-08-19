<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;

class StatisticsController extends Controller
{
    public function index()
    {
        $stats = app(StatisticsService::class)->getDashboardStats();

        return view('admin.statistics', compact('stats'));
    }
}
