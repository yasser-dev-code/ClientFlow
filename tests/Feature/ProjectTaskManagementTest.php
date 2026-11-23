<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\ProjectStatus;
use App\TaskPriority;
use App\TaskStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProjectTaskManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_project_and_assign_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin)->post(route('projects.store'), [
            'client_id' => $client->id, 'name' => 'Portail partenaire', 'status' => ProjectStatus::InProgress->value,
            'progress' => 25, 'budget' => 45000, 'start_date' => '2026-11-01', 'deadline' => '2027-02-01', 'employee_ids' => [$employee->id],
        ])->assertRedirect();

        $project = Project::where('name', 'Portail partenaire')->firstOrFail();
        $this->assertTrue($project->employees()->whereKey($employee->id)->exists());
    }

    public function test_employee_can_manage_own_task_but_not_another_employees_task(): void
    {
        $employee = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create();
        $project->employees()->attach($employee);
        $ownTask = Task::factory()->for($project)->create(['assigned_to' => $employee->id]);
        $otherTask = Task::factory()->for($project)->create(['assigned_to' => $other->id]);
        $payload = ['project_id' => $project->id, 'assigned_to' => $other->id, 'title' => 'Updated task', 'priority' => TaskPriority::High->value, 'status' => TaskStatus::Completed->value, 'due_date' => '2026-12-10'];

        $this->actingAs($employee)->put(route('tasks.update', $ownTask), $payload)->assertRedirect(route('tasks.index'));
        $ownTask->refresh();
        $this->assertSame($employee->id, $ownTask->assigned_to);
        $this->assertSame(TaskStatus::Completed, $ownTask->status);

        $this->actingAs($employee)->put(route('tasks.update', $otherTask), $payload)->assertForbidden();
    }
}
