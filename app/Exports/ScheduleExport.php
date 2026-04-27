<?php

namespace App\Exports;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $rowIndex = 0;

    public function __construct(private array $filters = []) {}

    public function query(): Builder
    {
        return $this->buildFilteredQuery();
    }

    public function headings(): array
    {
        return [
            '#',
            'Date',
            'Time',
            'Room',
            'Subject',
            'Program / Year / Section',
            'Instructor',
            'Class Representative',
            'Status',
            'Remarks',
        ];
    }

    public function map($schedule): array
    {
        $this->rowIndex++;

        return [
            $this->rowIndex,
            Carbon::parse($schedule->start_time)->format('M j, Y'),
            Carbon::parse($schedule->start_time)->format('g:i A').' – '.Carbon::parse($schedule->end_time)->format('g:i A'),
            $schedule->room?->room_number ?? '-',
            $schedule->subject ?? '-',
            $schedule->program_year_section ?? '-',
            $schedule->instructor ?? '-',
            $schedule->requester?->name ?? '-',
            $schedule->status?->value ?? '-',
            $schedule->remarks ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function buildFilteredQuery(): Builder
    {
        $query = Schedule::query()->with(['room', 'requester'])->orderBy('start_time');
        $filters = $this->filters;

        $statusValues = $filters['status']['values'] ?? [];
        if (! empty($statusValues)) {
            $query->whereIn('status', $statusValues);
        }

        $roomId = $filters['room_id']['value'] ?? null;
        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        $from = $filters['schedule_date_range']['from'] ?? null;
        $to = $filters['schedule_date_range']['to'] ?? null;
        if ($from) {
            $query->where('end_time', '>=', Carbon::parse($from)->format('Y-m-d H:i:s'));
        }
        if ($to) {
            $query->where('start_time', '<=', Carbon::parse($to)->format('Y-m-d H:i:s'));
        }

        $instructor = $filters['instructor']['value'] ?? null;
        if ($instructor) {
            $query->where('instructor', $instructor);
        }

        $subject = $filters['subject']['value'] ?? null;
        if ($subject) {
            $query->where('subject', $subject);
        }

        $pys = $filters['program_year_section']['value'] ?? null;
        if ($pys) {
            $query->where('program_year_section', $pys);
        }

        $requesterId = $filters['requester_id']['value'] ?? null;
        if ($requesterId) {
            $query->where('requester_id', $requesterId);
        }

        return $query;
    }
}
