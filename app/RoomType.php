<?php

namespace App;

enum RoomType: string
{
    case Laboratory = 'LABORATORY';
    case Lecture = 'LECTURE';
    case Other = 'OTHER';
}
