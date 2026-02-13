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
     */
    public function index(): JsonResponse
    {
        $keys = Key::query()
            ->with('room:id,room_number')
            ->orderBy('slot_number')
            ->get(['id', 'room_id', 'slot_number', 'status', 'updated_at']);

        return response()->json(['data' => $keys]);
    }

    /**
     * Get a single key by its slot number (physical slot in the keybox).
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

        return response()->json(['data' => $key]);
    }

    /**
     * Update key status (e.g. IoT device reports key in/out of box for this slot).
     * Only physical states (Stored, Used, HandedOver, Missing) are allowed here.
     * Disabled can only be set via the admin dashboard.
     */
    public function updateStatus(Request $request, string $slotNumber): JsonResponse
    {
        $allowedForIot = [KeyStatus::Used, KeyStatus::Stored, KeyStatus::HandedOver, KeyStatus::Missing];
        $allowedValues = array_map(fn (KeyStatus $s) => $s->value, $allowedForIot);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', $allowedValues)],
        ], [
            'status.in' => 'The key status cannot be set to Disabled via the API. Use the admin dashboard to disable a key.',
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

        $key->status = KeyStatus::from($validated['status']);
        $key->save();

        return response()->json([
            'message' => 'Key status updated',
            'data' => [
                'id' => $key->id,
                'slot_number' => $key->slot_number,
                'status' => $key->status->value,
            ],
        ]);
    }
}
