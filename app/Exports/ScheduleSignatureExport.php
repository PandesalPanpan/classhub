<?php

namespace App\Exports;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\JcTable;

class ScheduleSignatureExport
{
    public static function generate(array $filters = []): string
    {
        $schedules = static::buildFilteredQuery($filters)->get();

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
            'paperSize' => 'A4',
        ]);

        $section->addText(
            'Class Schedule Sign Sheet — Generated '.now()->format('F j, Y g:i A'),
            ['bold' => true, 'size' => 11],
            ['alignment' => 'center', 'spaceAfter' => 240]
        );

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 80,
            'cellMarginRight' => 80,
            'alignment' => JcTable::CENTER,
        ];

        $table = $section->addTable($tableStyle);

        $headerFont = ['bold' => true, 'size' => 9];
        $headerCell = ['bgColor' => 'D3D3D3', 'valign' => 'center'];
        $dataCell = ['valign' => 'center'];
        $headers = ['#', 'Date', 'Time', 'Room', 'Subject', 'Prog/Yr/Sec', 'Instructor', 'Class Representative', 'Signature'];
        $widths = [450, 1400, 1500, 700, 2200, 1800, 2200, 2200, 2948];

        $table->addRow(350);
        foreach ($headers as $i => $header) {
            $table->addCell($widths[$i], $headerCell)->addText($header, $headerFont);
        }

        foreach ($schedules as $index => $schedule) {
            $table->addRow(400);

            $cellData = [
                (string) ($index + 1),
                Carbon::parse($schedule->start_time)->format('M j, Y'),
                Carbon::parse($schedule->start_time)->format('g:i A').'–'.Carbon::parse($schedule->end_time)->format('g:i A'),
                $schedule->room?->room_number ?? '-',
                $schedule->subject ?? '-',
                $schedule->program_year_section ?? '-',
                $schedule->instructor ?? '-',
                $schedule->requester?->name ?? '-',
                '',
            ];

            foreach ($cellData as $i => $value) {
                $table->addCell($widths[$i], $dataCell)->addText($value, ['size' => 8]);
            }
        }

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $tmpPath = $tmpDir.'/schedule-signsheet-'.now()->format('YmdHis').'.docx';

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return $tmpPath;
    }

    private static function buildFilteredQuery(array $filters): Builder
    {
        $query = Schedule::query()->with(['room', 'requester'])->orderBy('start_time');

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
