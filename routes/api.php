<?php

use App\Http\Controllers\ActionsOnOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CanceledOrdersCauses;
use App\Http\Controllers\DeliveryPersonController;
use App\Http\Controllers\PartnerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('register', [UserController::class, 'store'])->name('register');
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/validate-token', [AuthController::class, 'validateToken']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('order/{id}/driverLocation', [DeliveryPersonController::class, 'getDeliveryDriverLocation']); //this is used to get the driver location
// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Order management
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('/orders/{order}/accept', [OrderController::class, 'acceptOrder']);
    Route::get('/orders/pending', [OrderController::class, 'pendingOrders']);
    Route::post('/orders/{order}/status', [OrderController::class, 'update']);
    Route::post('order', [OrderController::class, 'store']); //add order
    Route::get('order/{order}', [OrderController::class, 'show']); //get order
    Route::get('order/{order}/actions', [ActionsOnOrderController::class, 'show']); //get order actions
    Route::post('order/{order}/edit', [OrderController::class, 'update']); //edit order
    Route::delete('order/{order}', [OrderController::class, 'destroy']);
    Route::post('order/cancellation-reason', [CanceledOrdersCauses::class, 'store']); //cancel order
    Route::post('order/{order}/reject', [OrderController::class, 'rejectOrder']); //reject order

    // Delivery person routes
    Route::get('delivery-persons', [DeliveryPersonController::class, 'index']);
    Route::prefix('delivery')->group(function () {
        Route::get('/me', [DeliveryPersonController::class, 'show']);
        Route::put('/availability', [DeliveryPersonController::class, 'update']);
        Route::post('/location', [DeliveryPersonController::class, 'updateLocation']); //this is used to update the driver location
        Route::post('/store', [DeliveryPersonController::class, 'store']);
        Route::post('/{deliveryPerson}/edit', [DeliveryPersonController::class, 'update']);
        Route::get('/{deliveryPerson}/stats', [DeliveryPersonController::class, 'stats']);
        Route::delete('/{deliveryPerson}', [DeliveryPersonController::class, 'destroy']);
    });

    // Task management
    // Route::apiResource('tasks', TaskController::class)->except(['update', 'destroy']);
    Route::patch('/task/{task}/complete', [TaskController::class, 'markAsCompleted']);

    //Employees routes
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::prefix('employee')->group(function () {
        Route::get('/me', [EmployeeController::class, 'me']); //this will return the active tasks and current orders
        Route::get('/activeDeliveryPersons', [DeliveryPersonController::class, 'activeDeliveryPersons']);
        Route::post('/store', [EmployeeController::class, 'store']);
        Route::post('/{employee}/edit', [EmployeeController::class, 'update']);
        Route::get('/{employee}/stats', [EmployeeController::class, 'stats']);
        Route::delete('/{employee}', [EmployeeController::class, 'destroy']);
    });

    // Resource routes
    Route::post('client', [ClientController::class, 'store']);
    Route::post('clients/{client}', [ClientController::class, 'update']);

    //Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/orders-stats', [DashboardController::class, 'ordersStats']);
        Route::get('/client-stats', [DashboardController::class, 'clientStats']);
        Route::get('/drivers', [DashboardController::class, 'drivers']);
        Route::get('/orders', [DashboardController::class, 'orders']);
        Route::get('/clients', [DashboardController::class, 'clients']);
    });



    //Partners routes
    Route::get('partners', [PartnerController::class, 'index']);
    Route::post('partner', [PartnerController::class, 'store']);
    Route::post('partner/{partner}/edit', [PartnerController::class, 'update']);
    Route::delete('partner/{partner}', [PartnerController::class, 'destroy']);
    Route::prefix('partner')->group(function () {
        // Orders
        Route::get('/orders', [PartnerController::class, 'orders']);
        Route::post('/order', [PartnerController::class, 'addOrder']);
        Route::post('/order/{order}/edit', [PartnerController::class, 'updateOrder']);
        Route::delete('/order/{order}', [PartnerController::class, 'deleteOrder']);
        Route::get('/orders/{order}', [PartnerController::class, 'show']);
        Route::get('/clients', [PartnerController::class, 'clients']);
    });


    // Cancellation reasons (admin only)
    Route::middleware('admin')->group(function () {
        Route::apiResource('canceled-orders-causes', CanceledOrdersCauses::class)
            ->only(['update', 'destroy']);
    });

    // Cancellation reasons (general access)
    Route::apiResource('canceled-orders-causes', CanceledOrdersCauses::class)
        ->except(['update', 'destroy']);

    // Notifications
    Route::post('notification/token', [UserController::class, 'saveUserNotificationToken']);
    // Route::get('/notifications', [NotificationController::class, 'index']);
    // Route::post('/notifications/mark-read', [NotificationController::class, 'markAllAsRead']);
});
