<?php
namespace App\Enum;

enum ReservationStatus: string
{
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case COMPLETED = 'COMPLETED';
    case ON_GOING = 'ON_GOING';
    
    public function getDisplayName(): string
    {
        return match($this) {
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
            self::ON_GOING => 'On Going',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::CONFIRMED => '#28a745',  // Vert
            self::CANCELLED => '#dc3545',   // Rouge
            self::COMPLETED => '#17a2b8',   // Cyan
            self::ON_GOING => '#ffc107',    // Jaune
        };
    }
    
    public function getIcon(): string
    {
        return match($this) {
            self::CONFIRMED => 'check-circle',
            self::CANCELLED => 'times-circle',
            self::COMPLETED => 'flag-checkered',
            self::ON_GOING => 'hourglass-half',
        };
    }
}