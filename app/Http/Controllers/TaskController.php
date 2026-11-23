<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\TaskPriority;
use App\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $tasks = Task::query()
            ->with(['project.client:id,name', 'assignee:id,name'])
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when(in_array($status, array_column(TaskStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($priority, array_column(TaskPriority::cases(), 'value'), true), fn ($query) => $query->where('priority', $priority))
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->paginate(12)
            ->withQueryString();

        return view('tasks.index', compact('tasks', 'status', 'priority'));
    }

    public function create(Request $request): View
    {
        return view('tasks.form', $this->formData($request, new Task));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! $request->user()->isAdmin()) {
            abort_unless($request->user()->projects()->whereKey($data['project_id'])->exists(), 403);
            $data['assigned_to'] = $request->user()->id;
        }
        $data['completed_at'] = $data['status'] === TaskStatus::Completed->value ? now() : null;
        $task = Task::create($data);
        ActivityLog::create(['user_id' => $request->user()->id, 'subject_type' => Task::class, 'subject_id' => $task->id, 'action' => 'created', 'description' => __('app.activity.task_created', ['name' => $task->title])]);

        return redirect()->route('tasks.index')->with('success', __('app.saved'));
    }

    public function show(Task $task): RedirectResponse
    {
        return redirect()->route('tasks.edit', $task);
    }

    public function edit(Request $request, Task $task): View
    {
        $this->authorizeTask($request, $task);

        return view('tasks.form', $this->formData($request, $task));
    }

    public function update(StoreTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($request, $task);
        $data = $request->validated();
        if (! $request->user()->isAdmin()) {
            $data['project_id'] = $task->project_id;
            $data['assigned_to'] = $request->user()->id;
        }
        $data['completed_at'] = $data['status'] === TaskStatus::Completed->value ? ($task->completed_at ?? now()) : null;
        $task->update($data);

        return redirect()->route('tasks.index')->with('success', __('app.saved'));
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeTask($request, $task);
        $task->delete();

        return redirect()->route('tasks.index')->with('success', __('app.deleted'));
    }

    private function authorizeTask(Request $request, Task $task): void
    {
        abort_unless($request->user()->isAdmin() || $task->assigned_to === $request->user()->id, 403);
    }

    /** @return array<string, mixed> */
    private function formData(Request $request, Task $task): array
    {
        $projects = $request->user()->isAdmin()
            ? Project::orderBy('name')->get(['id', 'name'])
            : $request->user()->projects()->orderBy('name')->get(['projects.id', 'projects.name']);

        return [
            'task' => $task,
            'projects' => $projects,
            'employees' => $request->user()->isAdmin() ? User::where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect([$request->user()]),
            'priorities' => TaskPriority::cases(),
            'statuses' => TaskStatus::cases(),
        ];
    }
}
