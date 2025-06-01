<?php

namespace App\Http\Controllers;

use DatePeriod;
use DateInterval;
use App\Models\User;
use App\Models\Order;
use App\Models\Employee;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{


    public function me()
    {
        $user = Auth::guard('sanctum')->user();

        try {
            $employee = Employee::with([
                'tasks' => function ($query) {
                    $query->where('task_status', '!=', 'finished');
                },
                'orders' => function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->whereIn('status', ['pending', 'accepted'])
                        ->with(['client', 'deliveryPerson']);
                },
            ])->where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json([
                    'message' => 'Employee not found for the authenticated user'
                ], 401);
            }

            // Map through tasks and modify task_content
            if (!$employee->tasks->isEmpty()) {
                $employee->tasks->transform(function ($task) {
                    $task->task_content = $this->generateTaskContent($task->order);
                    return $task;
                });
            }

            return response()->json([
                'employee' => $employee,
                'active_tasks' => $employee->tasks || null,
                'handled_orders' => $employee->orders,
                'employee_id' => $employee->id
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error fetching employee data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    private function generateTaskContent(Order $order)
    {
        return view('tasks.cancellation', ['order' => $order])->render();
    }

    public function index()
    {
        $employees = Cache::remember('employees', 60, function () {
            return Employee::with('user:id,email')->withCount([
                'orders as total_orders' => fn($q) => $q->where('user_id', '=', $q->getModel()->id),
                'orders as delivered_orders' => fn($q) => $q->where('user_id', '=', $q->getModel()->id)->where('status', 'delivered'),
                'orders as canceled_orders' => fn($q) => $q->where('user_id', '=', $q->getModel()->id)->where('status', 'canceled'),
                'orders as pending_orders' => fn($q) => $q->where('user_id', '=', $q->getModel()->id)->where('status', 'pending')
            ])->get()->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_name' => $employee->employee_name,
                    'employee_phone' => $employee->employee_phone,
                    'status' => $employee->status,
                    'email' => $employee->user->email ?? null,
                    'order_stats' => [
                        'total' => $employee->total_orders,
                        'delivered' => $employee->delivered_orders,
                        'canceled' => $employee->canceled_orders,
                        'pending' => $employee->pending_orders
                    ]
                ];
            });
        });

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        //get authenticated user
        $user = Auth::guard('sanctum')->user();
        $validator = Validator::make($request->all(), [
            'employee_name' => 'required|string|max:255',
            'employee_phone' => 'nullable|string|unique:employee,employee_phone|max:20',
            'status' => 'sometimes|string|in:active,inactive',
            'email' => 'required|string|email|max:255|unique:user,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => "employee",
            ]);
            $employee = Employee::create([
                'employee_name' => $validated['employee_name'],
                'employee_phone' => $validated['employee_phone'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'user_id' => $user->id,
            ]);
            Cache::forget('employees');
            return response()->json([
                'id' => $employee->id,
                ...$request->all(),
                'order_stats' => ['total' => 0, 'delivered' => 0, 'canceled' => 0, 'pending' => 0]
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error creating employee',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $employee = Employee::withCount([
                'orders as total_orders',
                'orders as delivered_orders' => fn($q) => $q->where('status', 'delivered'),
                'orders as canceled_orders' => fn($q) => $q->where('status', 'canceled'),
                'orders as pending_orders' => fn($q) => $q->where('status', 'pending')
            ])->findOrFail($id);

            return response()->json([
                'employee' => $employee,
                'order_stats' => [
                    'total' => $employee->total_orders,
                    'delivered' => $employee->delivered_orders,
                    'canceled' => $employee->canceled_orders,
                    'pending' => $employee->pending_orders
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Employee not found',
                'error' => $th->getMessage()
            ], 404);
        }
    }

    // Add to EmployeeController
    /**
     * Retrieve statistics and timeline data for an employee's orders.
     *
     * @param \Illuminate\Http\Request $request The HTTP request instance containing optional 'from' and 'to' date filters.
     * @param string $id The ID of the employee whose statistics are being retrieved.
     * 
     * @return \Illuminate\Http\JsonResponse A JSON response containing:
     * - `stats`: An object with aggregated order statistics:
     *   - `total`: Total number of orders.
     *   - `delivered`: Number of delivered orders.
     *   - `canceled`: Number of canceled orders.
     *   - `pending`: Number of pending orders.
     * - `timeline`: A collection of daily order counts within the specified or default date range.
     * 
     * The function performs the following steps:
     * 1. Validates the 'from' and 'to' date filters in the request.
     * 2. Fetches the employee by ID or returns a 404 error if not found.
     * 3. Filters the employee's orders based on the provided date range.
     * 4. Calculates aggregated statistics for the orders.
     * 5. Determines the date range boundaries and generates a complete sequence of dates.
     * 6. Retrieves daily order counts and fills in missing dates with zero counts.
     * 7. Returns the statistics and timeline in a JSON response.
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the employee with the given ID is not found.
     * @throws \Throwable If any other error occurs during execution.
     */
    public function stats(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($id);
            $baseQuery = $employee->orders();

            // Handle date filters properly
            $fromDate = $request->filled('from')
                ? Carbon::parse($request->from)->startOfDay()
                : Carbon::parse($employee->orders()->min('created_at') ?? now()->subMonth())->startOfDay();

            $toDate = $request->filled('to')
                ? Carbon::parse($request->to)->endOfDay()
                : Carbon::now()->endOfDay();

            // Apply date filters correctly
            if ($request->filled('from') || $request->filled('to')) {
                $baseQuery->whereBetween('created_at', [$fromDate, $toDate]);
            }

            // Get statistics
            $stats = $baseQuery->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = "canceled" THEN 1 ELSE 0 END) as canceled,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
        ')->first();

            // Get daily counts
            $dailyCounts = $baseQuery->clone()
                ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->get()
                ->keyBy('day');

            // Generate complete date period
            $period = CarbonPeriod::create($fromDate, $toDate);
            $fullTimeline = collect($period->toArray())->map(function ($date) use ($dailyCounts) {
                $dateStr = $date->format('Y-m-d');
                return [
                    'day' => $dateStr,
                    'count' => $dailyCounts->has($dateStr) ? $dailyCounts[$dateStr]->count : 0
                ];
            });

            return response()->json([
                'stats' => $stats,
                'timeline' => $fullTimeline,
                'date_boundaries' => [
                    'start' => $fromDate->format('Y-m-d'),
                    'end' => $toDate->format('Y-m-d')
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error fetching statistics',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_name' => 'sometimes|string|max:255',
            'employee_phone' => 'sometimes|nullable|string|unique:employee,employee_phone,' . $id . '|max:20',
            'status' => 'sometimes|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        $employee->update($validator->validated());
        Cache::forget('employees');
    }

    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);

        if ($employee->orders()->exists() || $employee->tasks()->exists()) {
            return response()->json([
                'message' => 'Cannot delete employee with associated orders or tasks'
            ], 403);
        }

        $employee->delete();
        Cache::forget('employees');
        return response()->json(null, 200);
    }
}