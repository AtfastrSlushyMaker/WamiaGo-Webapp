<?php

namespace App\Enum;

enum REQUEST_STATUS : string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case CANCELED = 'CANCELED';
}
