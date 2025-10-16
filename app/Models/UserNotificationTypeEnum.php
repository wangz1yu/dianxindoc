<?php

namespace App\Models;

enum UserNotificationTypeEnum: int
{
    case DOCUMENT_SHARE = 0;
    case REMINDER = 1;
    case FILE_REQUEST = 2;
    case WORKFLOW = 3;
}
