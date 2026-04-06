<?php

namespace App\Console\Commands;

use App\Mail\Schedule\ScheduleCreatedConfirmation;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {--to= : Email address to send test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to debug mail configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $to = $this->option('to');

        if (! $to) {
            $to = $this->ask('Enter email address to send test to');
        }

        if (! $to) {
            $this->error('No email address provided');

            return Command::FAILURE;
        }

        $this->info('Testing email configuration...');
        $this->info("Sending to: {$to}");
        $this->newLine();

        // Step 1: Check mail configuration
        $this->info('📋 Step 1: Checking mail configuration...');
        $mailConfig = [
            'MAIL_MAILER' => config('mail.mailers.smtp.host') ?? config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
        ];

        foreach ($mailConfig as $key => $value) {
            $displayValue = $key === 'MAIL_USERNAME' || $key === 'MAIL_PASSWORD'
                ? (empty($value) ? '(empty)' : '***hidden***')
                : (empty($value) ? '(empty)' : $value);
            $this->line("  {$key}: {$displayValue}");
        }
        $this->newLine();

        // Step 2: Check if recipient exists in database
        $this->info('📋 Step 2: Checking database for test user...');
        $testUser = User::where('email', $to)->first();
        if ($testUser) {
            $this->line("  ✅ User found: {$testUser->name} (ID: {$testUser->id})");
        } else {
            $this->line('  ℹ️  User not found in database (sending to external email)');
        }
        $this->newLine();

        // Step 3: Get a test schedule
        $this->info('📋 Step 3: Getting test schedule data...');
        $schedule = Schedule::with(['requester', 'room'])->first();
        if (! $schedule) {
            $this->warn('  ⚠️  No schedules found in database. Creating mock data...');
            $schedule = new Schedule;
            $schedule->subject = 'Test Schedule';
            $schedule->program_year_section = 'BSIT 3-1';
            $schedule->instructor = 'Test Instructor';
            $schedule->start_time = now()->addDay();
            $schedule->end_time = now()->addDay()->addHours(2);
            $schedule->requester_id = User::first()?->id ?? 1;
        } else {
            $this->line("  ✅ Schedule found: {$schedule->subject} (ID: {$schedule->id})");
        }
        $this->newLine();

        // Step 4: Send test email
        $this->info('📋 Step 4: Sending test email...');

        try {
            $mailable = new ScheduleCreatedConfirmation($schedule);

            Mail::to($to)->send($mailable);

            $this->info('  ✅ Email sent successfully!');
            $this->newLine();

            // Step 5: Check mail queue
            $this->info('📋 Step 5: Checking mail queue status...');
            $queueConnection = config('queue.default');
            $this->line("  Queue connection: {$queueConnection}");

            if ($queueConnection === 'database') {
                $pendingJobs = \DB::table('jobs')->count();
                $this->line("  Pending jobs in queue: {$pendingJobs}");
            }
            $this->newLine();

            $this->info('🎉 Test completed!');
            $this->line('Check your inbox (and spam folder) for the test email.');
            $this->line('If using Mailtrap, check your Mailtrap inbox at https://mailtrap.io/');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('  ❌ Failed to send email!');
            $this->newLine();
            $this->error('Error details:');
            $this->line('  Exception: '.get_class($e));
            $this->line('  Message: '.$e->getMessage());
            $this->newLine();

            if ($this->output->isVerbose()) {
                $this->error('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            // Log the error for debugging
            Log::error('[TestEmail] Failed to send test email', [
                'to' => $to,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
