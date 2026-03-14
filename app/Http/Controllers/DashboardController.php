<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Helper;
use App\Models\SalaryPayment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        return $this->helperDashboard($user);
    }

    private function adminDashboard(): Response
    {
        $now = now();

        return Inertia::render('Dashboard', [
            'dashboardData' => [
                'role' => 'admin',
                'totalHelpers' => Helper::count(),
                'recentPayments' => SalaryPayment::query()
                    ->with('helper:id,name')
                    ->latest()
                    ->limit(5)
                    ->get(),
                'unpaidCurrentMonth' => SalaryPayment::query()
                    ->where('month', $now->month)
                    ->where('year', $now->year)
                    ->whereNull('paid_at')
                    ->count(),
                'helpersWithoutPayment' => Helper::query()
                    ->whereDoesntHave('salaryPayments', function ($q) use ($now) {
                        $q->where('month', $now->month)->where('year', $now->year);
                    })
                    ->count(),
                'upcomingAppointments' => Appointment::query()->upcoming()->limit(5)->get(),
                'familyInformation' => Setting::getValue('family_information'),
            ],
        ]);
    }

    private function helperDashboard($user): Response
    {
        $helper = $user->helper;

        return Inertia::render('Dashboard', [
            'dashboardData' => [
                'role' => 'helper',
                'helper' => $helper,
                'recentPayments' => $helper ? SalaryPayment::query()
                    ->where('helper_id', $helper->id)
                    ->latest('year')
                    ->latest('month')
                    ->limit(3)
                    ->get() : [],
                'documentsCount' => $helper?->documents()->count() ?? 0,
                'upcomingAppointments' => Appointment::query()->upcoming()->limit(5)->get(),
                'familyInformation' => Setting::getValue('family_information'),
            ],
        ]);
    }
}
