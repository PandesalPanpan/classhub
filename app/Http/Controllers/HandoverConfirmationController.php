<?php

namespace App\Http\Controllers;

use App\Models\ScheduleHandover;
use App\Services\EmailNotificationService;
use App\Services\HandoverOperationalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandoverConfirmationController extends Controller
{
    /**
     * Record a handover confirmation from a party via signed URL.
     */
    public function confirm(Request $request, ScheduleHandover $handover): \Illuminate\Contracts\View\View
    {
        $role = $request->query('role');

        if (! in_array($role, ['previous', 'next'], true)) {
            abort(400, 'Invalid role.');
        }

        if ($handover->resolution_finalized_at !== null) {
            return view('handover.already-resolved', ['handover' => $handover, 'action' => 'confirmed']);
        }

        $field = $role === 'previous' ? 'previous_confirmed_at' : 'next_confirmed_at';

        if ($handover->{$field} === null) {
            $handover->update([$field => now()]);

            Log::info("HandoverConfirmation: {$role} confirmed", [
                'handover_id' => $handover->id,
            ]);

            // If the other party disputed, alert admins immediately.
            if ($handover->hasAnyDispute()) {
                EmailNotificationService::sendHandoverDisputeAlert($handover);
            }

            if ($handover->isBothConfirmed()) {
                HandoverOperationalService::apply($handover);
            }
        }

        return view('handover.response', [
            'handover' => $handover,
            'action' => 'confirmed',
            'role' => $role,
        ]);
    }

    /**
     * Record a handover dispute from a party via signed URL.
     */
    public function dispute(Request $request, ScheduleHandover $handover): \Illuminate\Contracts\View\View
    {
        $role = $request->query('role');

        if (! in_array($role, ['previous', 'next'], true)) {
            abort(400, 'Invalid role.');
        }

        if ($handover->resolution_finalized_at !== null) {
            return view('handover.already-resolved', ['handover' => $handover, 'action' => 'disputed']);
        }

        $field = $role === 'previous' ? 'previous_disputed_at' : 'next_disputed_at';

        if ($handover->{$field} === null) {
            $handover->update([$field => now()]);

            Log::info("HandoverConfirmation: {$role} disputed", [
                'handover_id' => $handover->id,
            ]);

            // Alert admins immediately on any dispute so they can intervene.
            EmailNotificationService::sendHandoverDisputeAlert($handover);
        }

        return view('handover.response', [
            'handover' => $handover,
            'action' => 'disputed',
            'role' => $role,
        ]);
    }
}
