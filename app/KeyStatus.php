<?php

namespace App;

enum KeyStatus: string
{
    case Used = 'USED';
    case Stored = 'STORED';
    case Disabled = 'DISABLED';
}
