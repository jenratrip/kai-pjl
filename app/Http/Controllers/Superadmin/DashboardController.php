<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();

        $totalAdmins = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->count();

        $totalPetugas = User::whereHas('role', function ($query) {
            $query->where('name', 'pjl');
        })->count();

        $activitiesToday = ActivityLog::whereDate('created_at', today())->count();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(5)
            ->get();

        return view('superadmin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalPetugas' => $totalPetugas,
            'activitiesToday' => $activitiesToday,
            'recentActivities' => $recentActivities,
        ]);
    }
}