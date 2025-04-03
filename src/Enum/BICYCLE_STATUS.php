<?php
namespace App\Enum;
enum BICYCLE_STATUS: string
{
    case AVAILABLE = 'available';
    case IN_USE = 'in_use';
    case CHARGING = 'charging';
    case MAINTENANCE = 'maintenance';
    case RESERVED = 'reserved';
}