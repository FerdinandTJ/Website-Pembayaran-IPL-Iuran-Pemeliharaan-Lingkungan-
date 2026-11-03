<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Housing;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'recentHousings' => Housing::withCount(['users as residents_count' => fn ($q) => $q->where('role', 'warga')])->latest()->limit(5)->get(),
            'housingCount' => Housing::count(),
            'activeHousingCount' => Housing::where('is_active', true)->count(),
            'managerCount' => User::where('role', 'pengurus')->count(),
            'residentCount' => User::where('role', 'warga')->count(),
            'recentManagers' => User::with('housing')
                ->where('role', 'pengurus')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
