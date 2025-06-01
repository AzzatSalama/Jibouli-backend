<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CanceledOrdersCause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;

class CanceledOrdersCauses extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all cancellation causes with filters
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'sometimes|exists:order,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from'
        ]);

        $causes = CanceledOrdersCause::with(['order.client'])
            ->when($request->order_id, fn($q) => $q->where('order_id', $request->order_id))
            ->when($request->date_from, fn($q) => $q->whereBetween('created_at', [
                $request->date_from,
                $request->date_to ?? now()
            ]))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($causes);
    }

    /**
     * Store a new cancellation reason
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:order,id',
            'cause' => 'required|string|max:500'
        ]);

        return DB::transaction(function () use ($validated) {
            // Verify order is canceled
            $order = Order::findOrFail($validated['order_id']);

            if ($order->status !== 'canceled') {
                abort(400, 'Cannot add cancellation cause to non-canceled order');
            }

            $cause = CanceledOrdersCause::create($validated);

            return response()->json($cause, 201);
        });
    }

    /**
     * Get specific cancellation details
     */
    public function show(string $id)
    {
        $cause = CanceledOrdersCause::with(['order.client', 'order.employee'])
            ->findOrFail($id);

        return response()->json([
            'cause' => $cause,
            'order_details' => $cause->order,
            'client' => $cause->order->client
        ]);
    }

    /**
     * Update cancellation reason (restricted to admins)
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'cause' => 'required|string|max:500'
        ]);

        $cause = CanceledOrdersCause::findOrFail($id);

        $cause->update($validated);
        return response()->json($cause);
    }

    /**
     * Delete cancellation record (admin only)
     */
    public function destroy(string $id)
    {
        $cause = CanceledOrdersCause::findOrFail($id);

        $cause->delete();
        return response()->json(null, 204);
    }
}
