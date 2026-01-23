<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\ScheduleStatus;
use App\Services\ScheduleOverlapChecker;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the Class Representative user (requester)
        $classUser = User::where('email', 'class@test.com')->first();
        if (! $classUser) {
            $this->command->error('Class Representative user not found. Please run UserSeeder first.');

            return;
        }

        // Get the Admin user (approver) - using Moderator with Admin role
        $adminUser = User::where('email', 'moderator@test.com')->first();
        if (! $adminUser) {
            $this->command->error('Admin user not found. Please run UserSeeder first.');

            return;
        }

        // Get all available rooms
        $rooms = Room::where('is_active', true)->get();
        if ($rooms->isEmpty()) {
            $this->command->error('No active rooms found. Please run RoomSeeder first.');

            return;
        }

        // Sample subjects for university schedule
        $subjects = [
            'Data Structures and Algorithms',
            'Database Management Systems',
            'Web Development',
            'Software Engineering',
            'Computer Networks',
            'Operating Systems',
            'Object-Oriented Programming',
            'Mobile Application Development',
            'Artificial Intelligence',
            'Machine Learning',
            'Cybersecurity',
            'Cloud Computing',
            'Software Testing',
            'Project Management',
            'Research Methods',
        ];

        // Sample instructors
        $instructors = [
            'Dr. Maria Santos',
            'Prof. John Dela Cruz',
            'Engr. Robert Garcia',
            'Dr. Anna Rodriguez',
            'Prof. Michael Tan',
            'Engr. Sarah Lopez',
            'Dr. James Martinez',
            'Prof. Lisa Anderson',
            'Engr. David Brown',
            'Dr. Jennifer Wilson',
        ];

        // Sample program sections
        $programSections = [
            'BSCPE 1-1',
            'BSCPE 1-2',
            'BSCPE 2-1',
            'BSCPE 2-2',
            'BSCPE 3-1',
            'BSCPE 3-2',
            'BSCPE 4-1',
            'BSCPE 4-2',
        ];

        // Time slots for a typical university day (7:30 AM to 9 PM)
        // Classes can start at :00 or :30 and end at :00 or :30
        $timeSlots = [
            // Morning slots starting at :00
            ['start_hour' => 7, 'start_minute' => 30, 'duration' => 60],   // 7:30 AM - 8:30 AM
            ['start_hour' => 8, 'start_minute' => 0, 'duration' => 60],    // 8:00 AM - 9:00 AM
            ['start_hour' => 8, 'start_minute' => 30, 'duration' => 60],   // 8:30 AM - 9:30 AM
            ['start_hour' => 9, 'start_minute' => 0, 'duration' => 60],     // 9:00 AM - 10:00 AM
            ['start_hour' => 9, 'start_minute' => 30, 'duration' => 60],    // 9:30 AM - 10:30 AM
            ['start_hour' => 10, 'start_minute' => 0, 'duration' => 60],   // 10:00 AM - 11:00 AM
            ['start_hour' => 10, 'start_minute' => 30, 'duration' => 60],  // 10:30 AM - 11:30 AM
            ['start_hour' => 11, 'start_minute' => 0, 'duration' => 60],    // 11:00 AM - 12:00 PM
            ['start_hour' => 11, 'start_minute' => 30, 'duration' => 60],   // 11:30 AM - 12:30 PM
            // Afternoon slots
            ['start_hour' => 13, 'start_minute' => 0, 'duration' => 60],    // 1:00 PM - 2:00 PM
            ['start_hour' => 13, 'start_minute' => 30, 'duration' => 60],   // 1:30 PM - 2:30 PM
            ['start_hour' => 14, 'start_minute' => 0, 'duration' => 60],    // 2:00 PM - 3:00 PM
            ['start_hour' => 14, 'start_minute' => 30, 'duration' => 60],   // 2:30 PM - 3:30 PM
            ['start_hour' => 15, 'start_minute' => 0, 'duration' => 60],    // 3:00 PM - 4:00 PM
            ['start_hour' => 15, 'start_minute' => 30, 'duration' => 60],   // 3:30 PM - 4:30 PM
            ['start_hour' => 16, 'start_minute' => 0, 'duration' => 60],   // 4:00 PM - 5:00 PM
            ['start_hour' => 16, 'start_minute' => 30, 'duration' => 60],   // 4:30 PM - 5:30 PM
            ['start_hour' => 17, 'start_minute' => 0, 'duration' => 60],    // 5:00 PM - 6:00 PM
            ['start_hour' => 17, 'start_minute' => 30, 'duration' => 60],   // 5:30 PM - 6:30 PM
            ['start_hour' => 18, 'start_minute' => 0, 'duration' => 60],    // 6:00 PM - 7:00 PM
            ['start_hour' => 18, 'start_minute' => 30, 'duration' => 60],  // 6:30 PM - 7:30 PM
            ['start_hour' => 19, 'start_minute' => 0, 'duration' => 60],    // 7:00 PM - 8:00 PM
            ['start_hour' => 19, 'start_minute' => 30, 'duration' => 60],  // 7:30 PM - 8:30 PM
            ['start_hour' => 20, 'start_minute' => 0, 'duration' => 60],   // 8:00 PM - 9:00 PM
            ['start_hour' => 20, 'start_minute' => 30, 'duration' => 60],  // 8:30 PM - 9:30 PM
            // 1.5 hour classes
            ['start_hour' => 7, 'start_minute' => 30, 'duration' => 90],   // 7:30 AM - 9:00 AM
            ['start_hour' => 8, 'start_minute' => 0, 'duration' => 90],    // 8:00 AM - 9:30 AM
            ['start_hour' => 9, 'start_minute' => 0, 'duration' => 90],    // 9:00 AM - 10:30 AM
            ['start_hour' => 10, 'start_minute' => 0, 'duration' => 90],   // 10:00 AM - 11:30 AM
            ['start_hour' => 13, 'start_minute' => 0, 'duration' => 90],  // 1:00 PM - 2:30 PM
            ['start_hour' => 14, 'start_minute' => 0, 'duration' => 90],   // 2:00 PM - 3:30 PM
            ['start_hour' => 15, 'start_minute' => 0, 'duration' => 90],   // 3:00 PM - 4:30 PM
            ['start_hour' => 16, 'start_minute' => 0, 'duration' => 90],   // 4:00 PM - 5:30 PM
            ['start_hour' => 17, 'start_minute' => 0, 'duration' => 90],   // 5:00 PM - 6:30 PM
            ['start_hour' => 18, 'start_minute' => 0, 'duration' => 90],    // 6:00 PM - 7:30 PM
            ['start_hour' => 19, 'start_minute' => 0, 'duration' => 90],   // 7:00 PM - 8:30 PM
            ['start_hour' => 20, 'start_minute' => 0, 'duration' => 90],   // 8:00 PM - 9:30 PM
            // 2 hour classes
            ['start_hour' => 7, 'start_minute' => 30, 'duration' => 120],  // 7:30 AM - 9:30 AM
            ['start_hour' => 8, 'start_minute' => 0, 'duration' => 120],   // 8:00 AM - 10:00 AM
            ['start_hour' => 9, 'start_minute' => 0, 'duration' => 120],    // 9:00 AM - 11:00 AM
            ['start_hour' => 10, 'start_minute' => 0, 'duration' => 120],   // 10:00 AM - 12:00 PM
            ['start_hour' => 13, 'start_minute' => 0, 'duration' => 120],   // 1:00 PM - 3:00 PM
            ['start_hour' => 14, 'start_minute' => 0, 'duration' => 120],  // 2:00 PM - 4:00 PM
            ['start_hour' => 15, 'start_minute' => 0, 'duration' => 120],   // 3:00 PM - 5:00 PM
            ['start_hour' => 16, 'start_minute' => 0, 'duration' => 120],   // 4:00 PM - 6:00 PM
            ['start_hour' => 17, 'start_minute' => 0, 'duration' => 120],   // 5:00 PM - 7:00 PM
            ['start_hour' => 18, 'start_minute' => 0, 'duration' => 120],   // 6:00 PM - 8:00 PM
            ['start_hour' => 19, 'start_minute' => 0, 'duration' => 120],  // 7:00 PM - 9:00 PM
        ];

        // Get current month start and end
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $schedulesCreated = 0;

        // Iterate through each day of the current month
        $currentDate = $startOfMonth->copy();
        $attemptsPerDay = 0;
        $maxAttemptsPerDay = 50; // Maximum attempts to find non-overlapping schedules per day

        while ($currentDate->lte($endOfMonth)) {
            // Create schedules for weekdays and Saturday (Monday = 1, Saturday = 6)
            $dayOfWeek = $currentDate->dayOfWeek;
            if ($dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::SATURDAY) {
                // Create multiple schedules per day (like a packed university schedule)
                $schedulesPerDay = rand(10, 15); // 44-88 classes per day
                $attemptsPerDay = 0;

                for ($i = 0; $i < $schedulesPerDay && $attemptsPerDay < $maxAttemptsPerDay; $i++) {
                    $attemptsPerDay++;

                    // Randomly select a time slot
                    $timeSlot = $timeSlots[array_rand($timeSlots)];

                    // Create start time
                    $startTime = $currentDate->copy()
                        ->setTime($timeSlot['start_hour'], $timeSlot['start_minute'], 0);

                    // Create end time based on duration
                    $endTime = $startTime->copy()->addMinutes($timeSlot['duration']);

                    // Skip if end time goes beyond 9 PM (21:00)
                    if ($endTime->hour > 21 || ($endTime->hour === 21 && $endTime->minute > 0)) {
                        $i--; // Don't count this as a successful schedule

                        continue;
                    }

                    // Randomly select subject, instructor, program section, and room
                    $subject = $subjects[array_rand($subjects)];
                    $instructor = $instructors[array_rand($instructors)];
                    $programSection = $programSections[array_rand($programSections)];
                    $room = $rooms->random();

                    // Check for overlap before creating the schedule
                    if (ScheduleOverlapChecker::hasOverlap(
                        $room->id,
                        $startTime,
                        $endTime
                    )) {
                        $i--; // Don't count this as a successful schedule, try again

                        continue;
                    }

                    // Mix of Pending and Approved statuses (70% approved, 30% pending)
                    $status = (rand(1, 100) <= 70) ? ScheduleStatus::Approved : ScheduleStatus::Pending;
                    $approverId = ($status === ScheduleStatus::Approved) ? $adminUser->id : null;

                    // Create the schedule
                    Schedule::create([
                        'requester_id' => $classUser->id,
                        'approver_id' => $approverId,
                        'room_id' => $room->id,
                        'subject' => $subject,
                        'program_year_section' => $programSection,
                        'instructor' => $instructor,
                        'status' => $status,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'remarks' => rand(1, 10) <= 3 ? fake()->sentence() : null, // 30% chance of remarks
                    ]);

                    $schedulesCreated++;
                }
            }

            // Move to next day
            $currentDate->addDay();
        }

        $this->command->info("Created {$schedulesCreated} schedules for the current month.");
    }
}
