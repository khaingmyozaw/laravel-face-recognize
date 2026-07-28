<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Gate = 'gate';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Gate => 'Gate',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
