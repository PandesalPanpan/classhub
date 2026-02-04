<?php

namespace App\Filament\Pages\Schemas;

use App\Models\Room;
use Carbon\Carbon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

class OverrideTemplateForm
{
    public static function schema(): array
    {
        return [
            Section::make('You are requesting to override this template slot.')
                ->description('Values are copied from the template and cannot be changed.')
                ->schema([
                    Placeholder::make('room_display')
                        ->label('Room')
                        ->content(fn ($get) => Room::find($get('room_id'))?->room_number ?? '—')
                        ->dehydrated(false),
                    Placeholder::make('time_display')
                        ->label('Time')
                        ->content(function ($get) {
                            $start = $get('start_time');
                            $end = $get('end_time');
                            if (! $start || ! $end) {
                                return '—';
                            }

                            return Carbon::parse($start)->format('M j, Y g:i A').' – '.Carbon::parse($end)->format('g:i A');
                        })
                        ->dehydrated(false),
                    Placeholder::make('subject_display')
                        ->label('Subject / Purpose')
                        ->content(fn ($get) => $get('subject') ?? '—')
                        ->dehydrated(false),
                    Placeholder::make('program_year_section_display')
                        ->label('Program Year & Section')
                        ->content(fn ($get) => $get('program_year_section') ?? '—')
                        ->dehydrated(false),
                    Placeholder::make('instructor_display')
                        ->label('Instructor')
                        ->content(fn ($get) => $get('instructor') ?? '—')
                        ->dehydrated(false),
                    Hidden::make('room_id'),
                    Hidden::make('start_time'),
                    Hidden::make('end_time'),
                    Hidden::make('subject'),
                    Hidden::make('program_year_section'),
                    Hidden::make('instructor'),
                ])
                ->columns(2),
        ];
    }
}
