<?php

namespace App\Http\Controllers;

use App\KeyStatus;
use App\Models\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeyController extends Controller
{
    /**
     * List all keys (e.g. for IoT device or admin to see slot_number ↔ room mapping).
     * Includes accept_used_from_hardware for boot sync: true only when status is STORED or USED.
     */
    public function index(): JsonResponse
    {
        $keys = Key::query()
            ->with('room:id,room_number')
            ->orderBy('slot_number')
            ->get(['id', 'room_id', 'slot_number', 'status', 'updated_at']);

        $data = $keys->map(function (Key $key) {
            $item = $key->toArray();
            $item['accept_used_from_hardware'] = in_array($key->status, [KeyStatus::Stored, KeyStatus::Used], true);

            return $item;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Get a single key by its slot number (physical slot in the keybox).
     * Includes accept_used_from_hardware for boot sync: true only when status is STORED or USED.
     */
    public function showBySlot(string $slotNumber): JsonResponse
    {
        $key = Key::query()
            ->with('room:id,room_number')
            ->where('slot_number', $slotNumber)
            ->first(['id', 'room_id', 'slot_number', 'status', 'updated_at']);

        if (! $key) {
            return response()->json(['message' => 'Key not found'], 404);
        }

        $data = $key->only(['id', 'room_id', 'slot_number', 'updated_at']);
        $data['status'] = $key->status->value;
        $data['accept_used_from_hardware'] = in_array($key->status, [KeyStatus::Stored, KeyStatus::Used], true);

        return response()->json(['data' => $data]);
    }

    /**
     * Update key status (e.g. IoT device reports key in/out of box for this slot).
     * Hardware may only send STORED or USED. MISSING and HANDED_OVER are set by the application.
     * Disabled can only be set via the admin dashboard.
     */
    public function updateStatus(Request $request, string $slotNumber): JsonResponse
    {
        $allowedValues = [KeyStatus::Stored->value, KeyStatus::Used->value];

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', $allowedValues)],
        ], [
            'status.in' => 'The API only accepts STORED or USED from the hardware. MISSING and HANDED_OVER are set by the application.',
        ]);

        $key = Key::where('slot_number', $slotNumber)->first();

        if (! $key) {
            return response()->json(['message' => 'Key not found'], 404);
        }

        if ($key->status === KeyStatus::Disabled) {
            return response()->json([
                'message' => 'This key is disabled. Re-enable it in the admin dashboard before updating its status via the API.',
            ], 403);
        }

        $requestedStatus = KeyStatus::from($validated['status']);

        if ($requestedStatus === KeyStatus::Stored) {
            $key->status = KeyStatus::Stored;
            $key->save();

            return response()->json([
                'message' => 'Key status updated',
                'data' => [
                    'id' => $key->id,
                    'slot_number' => $key->slot_number,
                    'status' => $key->status->value,
                ],
                'updated' => true,
            ]);
        }

        if ($requestedStatus === KeyStatus::Used) {
            $preserveSoftwareState = in_array($key->status, [KeyStatus::Missing, KeyStatus::HandedOver], true);

            if ($preserveSoftwareState) {
                return response()->json([
                    'message' => 'Key status not updated; software state preserved.',
                    'data' => [
                        'id' => $key->id,
                        'slot_number' => $key->slot_number,
                        'status' => $key->status->value,
                    ],
                    'updated' => false,
                ]);
            }

            $key->status = KeyStatus::Used;
            $key->save();

            return response()->json([
                'message' => 'Key status updated',
                'data' => [
                    'id' => $key->id,
                    'slot_number' => $key->slot_number,
                    'status' => $key->status->value,
                ],
                'updated' => true,
            ]);
        }

        throw new \InvalidArgumentException('Invalid status requested.');
    }
}
