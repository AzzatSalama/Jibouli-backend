<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Order;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of tasks
     */
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        $tasks = Task::with(['order.client', 'assignedEmployee'])
            ->where('assigned_employee_id', $user->id)
            ->orderBy('created_at', 'desc');

        return response()->json($tasks);
    }

    /**
     * Display a specific task
     */
    public function show(string $id)
    {
        $task = Task::with(['order.client', 'assignedEmployee'])
            ->findOrFail($id);

        return response()->json($task);
    }

    /**
     * Update a task (finishing deletes it)
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'assigned_employee_id' => 'sometimes|exists:employee,id',
            'task_status' => ['sometimes', Rule::in(['finished'])]
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $task = Task::findOrFail($id);
            $originalEmployee = $task->assigned_employee_id;

            // Handle task completion
            if (isset($validated['task_status']) && $validated['task_status'] === 'finished') {
                $task->delete();
                return response()->json(null, 204);
            }

            // Handle reassignment
            if (isset($validated['assigned_employee_id']) && $validated['assigned_employee_id'] !== $originalEmployee) {
                $this->handleReassignment($task, $validated['assigned_employee_id']);
            }

            $task->update($validated);
            return response()->json($task);
        });
    }

    //mark task as completed
    public function markAsCompleted(Request $request, string $id)
    {
        $validated = $request->validate([
            'task_status' => ['required', Rule::in(['finished'])],
        ]);
        $task = Task::findOrFail($id);
        $task->update($validated);
        // $task->delete();
        return response()->json(null, 204);
    }

    /**
     * Handle task reassignment
     */
    private function handleReassignment(Task $task, $newEmployeeId)
    {
        // Notify previous employee
        $this->sendTaskNotification(
            $task->assigned_employee_id,
            'Tâche réassignée',
            "La tâche #{$task->id} a été transférée",
            "/employee/tasks"
        );

        // Notify new employee
        $this->sendTaskNotification(
            $newEmployeeId,
            'Nouvelle tâche assignée',
            "Une tâche vous a été transférée",
            "/employee/tasks/{$task->id}"
        );
    }

    /**
     * Send notifications safely
     */
    private function sendTaskNotification($employeeId, $title, $message, $link)
    {
        try {
            $employee = Employee::with('user')->find($employeeId);

            if ($employee->user && $employee->user->notification_preference !== 'disabled') {
                $tokens = $employee->user->tokens->pluck('device_token')->filter()->toArray();

                if (!empty($tokens)) {
                    $this->notificationService->sendNotification(
                        $title,
                        $message,
                        $link,
                        $tokens
                    );
                }
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Remove a task (only allowed for started tasks)
     */
    public function destroy(string $id)
    {
        $task = Task::findOrFail($id);

        if ($task->task_status === 'started') {
            $task->delete();
            return response()->json(null, 204);
        }

        return response()->json([
            'message' => 'Seules les tâches en cours peuvent être supprimées'
        ], 403);
    }
}
