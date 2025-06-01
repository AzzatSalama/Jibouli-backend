<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Client;
use App\Models\Partner;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\DeliveryPerson;
use App\Services\AuthEntityService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function ordersStats(Request $request)
    {
        $validator = Validator::make($request->only('date'), [
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $date = Carbon::parse($request->date);

        $stats = Order::whereDate('created_at', $date)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = "pending" then 1 else 0 end) as pending')
            ->selectRaw('sum(case when status = "accepted" then 1 else 0 end) as accepted')
            ->selectRaw('sum(case when status = "delivered" then 1 else 0 end) as delivered')
            ->selectRaw('sum(case when status = "canceled" then 1 else 0 end) as canceled')
            ->first();

        return response()->json([
            'orders' => $stats
        ]);
    }

    public function drivers()
    {
        $total = DeliveryPerson::count();
        $active = DeliveryPerson::where('is_available', true)->count();

        return response()->json([
            'total' => $total,
            'active' => $active
        ]);
    }

    public function orders(AuthEntityService $authEntityService)
    {
        $orders = Order::with(['client', 'user', 'deliveryPerson'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'client' => [
                        'name' => $order->client->client_name,
                        'phone' => $order->client->client_phone,
                        'address' => $order->client->client_address
                    ],
                    'request' => $order->request,
                    'user_id' => $order->user->id,
                    'status' => $order->status,
                    'driver' => $order->deliveryPerson ? $order->deliveryPerson->delivery_person_name : 'N/A',
                    'created_at' => $order->created_at->toDateTimeString()
                ];
            });

        $orders = $orders->map(function ($order) use ($authEntityService) {
            $actionCreator = $authEntityService->getUserById($order['user_id']);
            if ($actionCreator instanceof Partner) {
                $userName = 'Partenaire - ' . $actionCreator->name;
            } elseif ($actionCreator instanceof Employee) {
                $userName = 'Employé - ' . $actionCreator->employee_name;
            } else if ($actionCreator instanceof DeliveryPerson) {
                $userName = 'Livreur - ' . $actionCreator->deliveryPerson->delivery_person_name;
            } else {
                $userName = 'Admin';
            }

            // Attach the user details to the order
            $order['user'] = $userName;

            return $order;
        });
        return response()->json($orders);
    }

    public function clients()
    {
        $clients = Client::withCount('orders')
            ->with(['orders' => function ($query) {
                $query->select('client_id', 'status', 'created_at')
                    ->orderBy('created_at', 'desc');
            }])
            ->get()
            ->map(function ($client) {
                $lastOrder = $client->orders->first();
                $statusCounts = $client->orders->groupBy('status')->map->count();

                return [
                    'id' => $client->id,
                    'name' => $client->client_name,
                    'phone' => $client->client_phone,
                    'address' => $client->client_address,
                    'orders' => [
                        'total' => $client->orders_count,
                        'delivered' => $statusCounts->get('delivered', 0),
                        'canceled' => $statusCounts->get('canceled', 0),
                        'pending' => $statusCounts->get('pending', 0)
                    ],
                    'last_order' => $lastOrder ? $lastOrder->created_at->toDateTimeString() : 'N/A'
                ];
            });

        return response()->json($clients);
    }

    public function clientStats()
    {
        // Total clients
        $totalClients = Client::count();

        // Average orders per client
        $totalOrders = Order::count();
        $avgOrdersPerClient = $totalClients > 0 ? $totalOrders / $totalClients : 0;

        // Average orders per month per client
        $firstOrderDate = Order::orderBy('created_at')->value('created_at');
        $monthsOfOperation = $firstOrderDate
            ? Carbon::now()->diffInMonths(Carbon::parse($firstOrderDate))
            : 0;

        $avgMonthlyPerClient = ($totalClients > 0 && $monthsOfOperation > 0)
            ? ($totalOrders / $monthsOfOperation) / $totalClients
            : 0;

        return response()->json([
            'total' => $totalClients,
            'avg_orders_per_client' => round($avgOrdersPerClient, 1),
            'avg_orders_per_month_per_client' => round($avgMonthlyPerClient, 1)
        ]);
    }
}