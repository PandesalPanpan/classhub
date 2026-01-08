<?php

namespace App\Http\Controllers;

use App\KeyStatus;
use App\Models\Key;
use Illuminate\Http\Request;

class KeyController extends Controller
{
    // Change Key Status based on Key Slot Number
    // TODO: later change this to check if the key is disabled status
    public function changeKeyStatus(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'slot_number' => 'required|string',
            'status' => 'required|string|in:'.implode(',', array_column(KeyStatus::cases(), 'value')),
        ]);


        // Check if the key exists
        $key = Key::where('slot_number', $validated['slot_number'])->first();
        if (!$key) {
            return response()->json(['message' => 'Key not found'], 404);
        }

        // Change the key status
        $key->status = KeyStatus::from($validated['status']);
        $key->save();
        return response()->json(['message' => 'Slot number '. $key->slot_number .' status changed to '. $key->status->value]);
    }
}
