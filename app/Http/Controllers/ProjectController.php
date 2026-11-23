<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\ProjectStatus;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $projects = Project::query()
            ->with(['client:id,name,company', 'employees:id,name'])
            ->withCount('tasks')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when(in_array($status, array_column(ProjectStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('projects.index', compact('projects', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('projects.form', $this->formData(new Project));
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->safe()->except('employee_ids'));
        $project->employees()->sync($request->input('employee_ids', []));
        ActivityLog::create(['user_id' => $request->user()->id, 'subject_type' => Project::class, 'subject_id' => $project->id, 'action' => 'created', 'description' => __('app.activity.project_created', ['name' => $project->name])]);

        return redirect()->route('projects.show', $project)->with('success', __('app.saved'));
    }

    public function show(Project $project): View
    {
        $project->load(['client', 'employees', 'tasks' => fn ($query) => $query->with('assignee')->orderBy('due_date'), 'invoices.payments']);

        return view('projects.show', compact('project'));
    }

    public function edit(Request $request, Project $project): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $project->load('employees');

        return view('projects.form', $this->formData($project));
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->safe()->except('employee_ids'));
        $project->employees()->sync($request->input('employee_ids', []));

        return redirect()->route('projects.show', $project)->with('success', __('app.saved'));
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $project->delete();

        return redirect()->route('projects.index')->with('success', __('app.deleted'));
    }

    /** @return array<string, mixed> */
    private function formData(Project $project): array
    {
        return [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'company']),
            'employees' => User::where('role', UserRole::Employee)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => ProjectStatus::cases(),
        ];
    }
}
