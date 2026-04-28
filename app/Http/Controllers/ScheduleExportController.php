<?php

namespace App\Http\Controllers;

use App\Exports\ScheduleExport;
use App\Exports\ScheduleSignatureExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleExportController extends Controller
{
    public function excel(Request $request)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Superadmin']), 403);

        $filters = session()->pull('schedule_export_filters', []);

        return Excel::download(
            new ScheduleExport($filters),
            'schedules-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function signSheet(Request $request)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Superadmin']), 403);

        $filters = session()->pull('schedule_export_filters', []);
        $path = ScheduleSignatureExport::generate($filters);

        return response()
            ->download($path, 'schedule-sign-sheet-'.now()->format('Y-m-d').'.docx')
            ->deleteFileAfterSend(true);
    }
}
