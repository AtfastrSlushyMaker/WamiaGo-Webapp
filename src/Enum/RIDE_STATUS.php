<?php

namespace App\Enum;

enum RIDE_STATUS : string

{


    case ONGOING = 'ONGOING';
    case COMPLETED = 'COMPLETED';
    case CANCELED = 'CANCELED';
}
