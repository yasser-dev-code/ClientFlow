<?php

namespace App\Http\Controllers;

use App\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\ProjectStatus;
use App\TaskStatus;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $payments = Payment::query()
            ->where('paid_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['amount', 'paid_at']);

        $months = collect(range(5, 0))->map(function (int $monthsAgo) use ($payments): array {
            $month = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $month->translatedFormat('M'),
                'value' => round((float) $payments->filter(fn (Payment $payment): bool => $payment->paid_at->isSameMonth($month))->sum('amount'), 2),
            ];
        });

        $statistics = [
            'clients' => Client::count(),
            'active_projects' => Project::whereIn('status', [ProjectStatus::Planned, ProjectStatus::InProgress, ProjectStatus::OnHold])->count(),
            'completed_projects' => Project::where('status', ProjectStatus::Completed)->count(),
            'pending_tasks' => Task::whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])->count(),
            'total_invoiced' => (float) Invoice::sum('total'),
            'total_received' => (float) Payment::sum('amount'),
            'outstanding' => max(0, (float) Invoice::whereNot('status', InvoiceStatus::Draft)->sum('total') - (float) Payment::sum('amount')),
        ];

        $projectStatistics = collect(ProjectStatus::cases())->mapWithKeys(
            fn (ProjectStatus $status): array => [$status->value => Project::where('status', $status)->count()],
        );

        return view('dashboard', [
            'statistics' => $statistics,
            'months' => $months,
            'projectStatistics' => $projectStatistics,
            'recentActivities' => ActivityLog::with('user')->latest()->limit(8)->get(),
            'upcomingTasks' => Task::with(['project', 'assignee'])->whereNot('status', TaskStatus::Completed)->orderBy('due_date')->limit(6)->get(),
        ]);
    }
}
