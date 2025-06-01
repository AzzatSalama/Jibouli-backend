<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Partner;
use App\Models\Employee;
use App\Models\DeliveryPerson;
use App\Services\AuthEntityService;
use App\Http\Controllers\Controller;

class ActionsOnOrderController extends Controller
{
    public function show($id, AuthEntityService $authEntityService)
    {
        // Fetch the order by ID
        $order = Order::findOrFail($id);
        $actionsOnOrder = $order->actionsOnOrder()
            ->with('user') // Join with the user
            ->orderBy('action_performed_at', 'asc')
            ->get();

        foreach ($actionsOnOrder as $action) {
            $actionCreator = $authEntityService->getUserById($action->user_id);
            if ($actionCreator instanceof Partner) {
                $userName = 'Partenaire - ' . $actionCreator->name;
                $userPhone = $actionCreator->phone;
            } elseif ($actionCreator instanceof Employee) {
                $userName = 'Employé - ' . $actionCreator->employee_name;
                $userPhone = $actionCreator->phone;
            } else if ($actionCreator instanceof DeliveryPerson) {
                $userName = 'Livreur - ' . $actionCreator->delivery_person_name;
                $userPhone = $actionCreator->delivery_phone;
            } else {
                $userName = 'Admin';
            }

            // Attach the user details to the action
            $action->user_name = $userName;
            $action->user_phone = $userPhone ?? null;
        }



        // Return the order details
        return response()->json($actionsOnOrder, 200);
    }
}