<?php
namespace App\Enum;
enum BICYCLE_STATUS: string
{
    case AVAILABLE = 'available';
    case IN_USE = 'in_use';
    case CHARGING = 'charging';
    case MAINTENANCE = 'maintenance';
    case RESERVED = 'reserved';

    public static function formValue(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new \ValueError("\"$value\" is not a valid backing value for enum " . self::class);
    }
    
    /**
     * Convert enum to string (replacement for __toString which can't be used in enums)
     */
    public static function toString(self $status): string
    {
        return $status->value;
    }
}