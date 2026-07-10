<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientSyncController extends Controller
{
    /**
     * Synchronize client data from the local client application.
     *
     * @return JsonResponse
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clients' => 'required|array',
            'clients.*.uuid' => 'required|uuid',
            'clients.*.dni' => 'required|string|max:10',
            'clients.*.first_name' => 'required|string|max:150',
            'clients.*.second_name' => 'nullable|string|max:150',
            'clients.*.first_last_name' => 'required|string|max:150',
            'clients.*.second_last_name' => 'nullable|string|max:150',
            'clients.*.email' => 'required|email|max:255',
            'clients.*.phone_number' => 'required|string|max:10',
            'clients.*.address' => 'required|string|max:255',
            'clients.*.updated_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The data is incomplete or incorrectly formatted.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $clients = $request->input('clients');

        foreach ($clients as $clientData) {

            $existingClient = Client::where('dni', $clientData['dni'])->first();

            if (! $existingClient) {
                $newClient = new Client;
                $newClient->uuid = $clientData['uuid'];
                $newClient->dni = $clientData['dni'];
                $newClient->first_name = $clientData['first_name'];
                $newClient->second_name = $clientData['second_name'] ?? null;
                $newClient->first_last_name = $clientData['first_last_name'];
                $newClient->second_last_name = $clientData['second_last_name'] ?? null;
                $newClient->email = $clientData['email'];
                $newClient->phone_number = $clientData['phone_number'];
                $newClient->address = $clientData['address'];
                $newClient->is_sync = true;

                $clientDate = Carbon::parse($clientData['updated_at']);
                $newClient->created_at = $clientDate;
                $newClient->updated_at = $clientDate;

                $newClient->save();
            } else {
                $clientDate = Carbon::parse($clientData['updated_at']);
                $serverDate = Carbon::parse($existingClient->updated_at);

                if ($clientDate->greaterThan($serverDate)) {
                    $existingClient->uuid = $clientData['uuid'];
                    $existingClient->first_name = $clientData['first_name'];
                    $existingClient->second_name = $clientData['second_name'] ?? null;
                    $existingClient->first_last_name = $clientData['first_last_name'];
                    $existingClient->second_last_name = $clientData['second_last_name'] ?? null;
                    $existingClient->email = $clientData['email'];
                    $existingClient->phone_number = $clientData['phone_number'];
                    $existingClient->address = $clientData['address'];
                    $existingClient->is_sync = true;
                    $existingClient->updated_at = $clientDate;
                    $existingClient->save();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Synchronization completed successfully.',
        ], 200);
    }
}
