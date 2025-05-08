<?php
namespace App\Enum;
enum BICYCLE_STATION_STATUS: string
{
case ACTIVE = 'active';
case INACTIVE = 'inactive';
case MAINTENANCE = 'maintenance';
case DISABLED = 'disabled';

}