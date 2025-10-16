<?php

namespace App\Models;

enum RetentionActionEnum: int
{
    case ARCHIVE = 0;
    case PERMANENT_DELETE = 1;
    case EXPIRE = 2;
}
