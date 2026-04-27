<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('policy_content')->nullable()->after('allow_app_registration');
            $table->timestamp('policy_updated_at')->nullable()->after('policy_content');
        });

        $defaultPolicy = <<<'MARKDOWN'
By checking the box labeled "I agree to the CPE Room Utilization Terms & Conditions", you (the "Borrower" or "Class Representative") officially agree to the following policies, liabilities, and penalty structures set forth by the CPE Laboratory Office.

## 1. AUTHORIZATION & ELIGIBILITY

### 1.1. Authorized Personnel Only
I attest that I am the duly elected Class Representative for my section. I understand that borrowing keys or rooms without this authorization is a punishable infraction.

### 1.2. Identification
I agree to surrender my valid, current-semester PUP ID as security. I understand that my ID will only be returned once all room handover requirements (cleanliness, photos, key return) are met.

### 1.3. No Proxy
I acknowledge that I cannot use another student's ID to borrow a room unless that student is also a Class Representative and present.

## 2. USAGE & CONDUCT

### 2.1. Academic Use Only
I agree to use the room strictly for scheduled classes or approved academic activities.

### 2.2. Prohibited Acts
I will ensure my section does not engage in:

- Watching non-academic media or gaming.
- Horseplay, shouting, or playing loud music.
- Vandalism or modification of fixed equipment (wiring, boards, ACUs).

### 2.3. Noise Control
I will ensure my class maintains silence to avoid disturbing adjacent classes or ongoing examinations.

### 2.4. Vacant Rooms
I understand that entering or loitering in a vacant room without officially securing it via the log sheet is prohibited.

## 3. MANDATORY LOGGING & HANDOVER PROCEDURES

### 3.1. Log Sheet Accuracy
I certify that all details entered in the Room Usage Log Sheet (Time In/Out, Subject, Section) are accurate and complete.

### 3.2. Before-Leaving Protocol
Before returning the key, I accept full responsibility for completing the following checklist:

- **Cleanliness:** Chairs arranged, trash bin utilized, board erased.
- **Power:** ACU and ceiling fans switched OFF; Lights UNLIT.
- **Security:** Windows and sliding boards closed/locked.

### 3.3. Photographic Evidence
I agree to take and present the required "After-Use Photos" (wide view, board, ACU/fans, lights, windows, key) to the Student Assistant or Laboratory Head upon return. I understand my ID will be withheld until these photos are approved.

## 4. LIABILITY & PENALTIES

### 4.1. Assumption of Liability
I acknowledge that as the Class Representative/Borrower, I am the primary point of contact.

- **Individual Liability:** Damage traced to a specific person will be charged to them.
- **Section Liability:** Damage found in the room during my reservation that cannot be traced to an individual will be charged to my section collectively.

### 4.2. Penalties
I accept that failure to comply with these terms (for example: late key return, dirty room, missing photos, or noise violations) will result in sanctions or fines as determined by the Laboratory Head.

## 5. PRIVACY NOTICE

### 5.1. Data Collection
I consent to the collection of my name, student number, and signature for the purpose of tracking asset usage and enforcing accountability.

### 5.2. Data Usage
This data will be accessible only to the CPE Laboratory Office personnel and may be used for disciplinary reports if infractions occur.
MARKDOWN;

        $now = now();

        $existing = DB::table('settings')->first();
        if ($existing) {
            DB::table('settings')
                ->whereNull('policy_content')
                ->update([
                    'policy_content' => $defaultPolicy,
                    'policy_updated_at' => $now,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('settings')->insert([
            'id' => 1,
            'key_usage_check_percent' => (float) config('classhub.schedule.key_usage_check_percent', 0.4),
            'handover_eligibility_window_minutes' => (int) config('classhub.schedule.handover_eligibility_window_minutes', 30),
            'grace_period_minutes' => (int) config('classhub.schedule.grace_period_minutes', 15),
            'handover_enabled' => (bool) config('classhub.schedule.handover_enabled', true),
            'allow_past_schedule_requests' => (bool) config('classhub.schedule.allow_past_schedule_requests', false),
            'allow_app_registration' => (bool) config('classhub.schedule.allow_app_registration', true),
            'policy_content' => $defaultPolicy,
            'policy_updated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['policy_content', 'policy_updated_at']);
        });
    }
};
