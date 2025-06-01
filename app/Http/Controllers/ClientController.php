<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index()
    {
        $clients = cache()->remember('clients', 60, function () {
            return Client::all();
        });

        return response()->json($clients);
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        //check to see if the phone number is associated with another client
        $clientExists = Client::where('client_phone', $request->client_phone)->exists();
        if ($clientExists) {
            return response()->json(['message' => 'Un client avec ce numéro de téléphone et déja enregistré'], 422);
        }
        $validator = Validator::make($request->all(), [
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'required|string|unique:client,client_phone|max:20',
            'client_address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $validated = $validator->validated();

        $client = Client::create([
            'client_name' => $validated['client_name'] ?? null,
            'client_phone' => $validated['client_phone'],
            'client_address' => $validated['client_address'] ?? null,
            'added_by' => $user->id,
        ]);
        Cache::forget('clients');
        return response()->json(['message' => 'client created succefully'], 201);
    }

    /**
     * Display client details with past orders.
     */
    public function show(string $id)
    {
        $client = Client::with('orders')->findOrFail($id);
        return response()->json([
            'client' => $client,
            'orders' => $client->orders
        ]);
    }

    /**
     * Update client information.
     */
    public function update(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'client_name' => 'sometimes|string|max:255',
            'client_phone' => 'sometimes|string|unique:client,client_phone,' . $id . '|max:20',
            'client_address' => 'sometimes|string|max:500'
        ]);

        $client->update($validated);
        Cache::forget('clients');
        return response()->json($client);
    }

    /**
     * Delete a client (with order relationship check).
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);

        $client->delete();
        Cache::forget('clients');
        return response()->json(null, 204);
    }
}
