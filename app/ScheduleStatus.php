<?php

namespace App;

enum ScheduleStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';
    case Completed = 'COMPLETED';
    case Expired = 'EXPIRED';
}
