<?php
namespace App\Enum;

enum BICYCLE_STATION_STATUS: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case DISABLED = 'disabled';

    /**
     * Create an enum instance from a string value
     *
     * @param string $value The string value to convert
     * @return self The corresponding enum instance
     * @throws \ValueError If the value doesn't match any enum case
     */
    public static function fromValue(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new \ValueError("\"$value\" is not a valid backing value for enum " . self::class);
    }
}