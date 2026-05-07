<?php

return [
    'schedule' => [
        'handover_eligibility_window_minutes' => 30,
        'grace_period_minutes' => 15,
        'key_usage_check_percent' => 0.4,
        'early_key_pickup_minutes' => 15,
        'handover_enabled' => true,
        'allow_past_schedule_requests' => false,
        'allow_app_registration' => true,
        'auto_verify_registration' => false,
        'reservation_rules_content' => <<<'MARKDOWN'
### Reservations and Priority Rules

- Reservation inquiries are accepted no earlier than one (1) week and no later than two (2) hours before the scheduled use. Requests are accepted on a **FIRST COME, FIRST SERVED** basis and apply to blocks without an assigned room unless instructed otherwise by the instructor.
- Room re-assignment or transfer between rooms is prohibited unless authorized by the instructor, S.A. on duty, or Lab Head.
- Allocation priority for laboratory rooms (with ACU):
    1. CMPE courses
    2. Sections handled by CPE faculty
    3. Sections already assigned to that lab room.
- If a class with an instructor needs a room currently used by a class without an instructor, the room is reallocated to the class with an instructor; the non-instructor class must vacate and follow the photo & return procedures.
MARKDOWN,
    ],
];
