<?php

namespace App;

enum ScheduleType: string
{
    case Fixed = 'FIXED';
    case Request = 'REQUEST';
    case Template = 'TEMPLATE';

}