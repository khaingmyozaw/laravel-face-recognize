<?php

namespace App\Enums;

enum Permission: string
{
    case EnrollmentsView = 'enrollments.view';
    case EnrollmentsCreate = 'enrollments.create';
    case EnrollmentsUpdate = 'enrollments.update';
    case EnrollmentsDelete = 'enrollments.delete';
    case EnrollmentsSearch = 'enrollments.search';
    case EnrollmentsImport = 'enrollments.import';
    case EnrollmentsExport = 'enrollments.export';
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';
    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';

    public function label(): string
    {
        return match ($this) {
            self::EnrollmentsView => 'View enrollments',
            self::EnrollmentsCreate => 'Create enrollments',
            self::EnrollmentsUpdate => 'Update enrollments',
            self::EnrollmentsDelete => 'Delete enrollments',
            self::EnrollmentsSearch => 'Search by face',
            self::EnrollmentsImport => 'Import enrollments',
            self::EnrollmentsExport => 'Export enrollments',
            self::UsersView => 'View users',
            self::UsersCreate => 'Create users',
            self::UsersUpdate => 'Update users',
            self::UsersDelete => 'Delete users',
            self::RolesView => 'View roles',
            self::RolesCreate => 'Create roles',
            self::RolesUpdate => 'Update roles',
            self::RolesDelete => 'Delete roles',
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
