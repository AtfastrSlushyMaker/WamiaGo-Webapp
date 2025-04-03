<?php
namespace App\Enum;
enum DRIVER_ROLE: string
{
    case TAXI_DRIVER = 'TAXI_DRIVER';
    case TRANSPORTER = 'TRANSPORTER';
    case CARPOOL_DRIVER = 'CARPOOL_DRIVER';
}