<?php
namespace App\Enum;
enum ReservationStatus: string
{
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case COMPLETED = 'COMPLETED';
    case ON_GOING = 'ON_GOING';
}