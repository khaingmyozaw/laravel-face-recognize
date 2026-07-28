<?php

namespace App\Models;

use App\Enums\Permission;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $label
 * @property list<string> $permissions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'label', 'permissions'])]
class Role extends Model
{
    public const ADMIN = 'admin';

    public const GATE = 'gate';

    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $value = $permission instanceof Permission
            ? $permission->value
            : $permission;

        return in_array($value, $this->permissions ?? [], true);
    }

    /**
     * @return list<Permission>
     */
    public function permissionEnums(): array
    {
        $permissions = [];

        foreach ($this->permissions ?? [] as $value) {
            $permission = Permission::tryFrom($value);

            if ($permission !== null) {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function defaultAdminPermissions(): array
    {
        return Permission::values();
    }

    /**
     * @return list<string>
     */
    public static function defaultUserPermissions(): array
    {
        return [
            Permission::EnrollmentsView->value,
            Permission::EnrollmentsCreate->value,
            Permission::EnrollmentsUpdate->value,
            Permission::EnrollmentsDelete->value,
            Permission::EnrollmentsSearch->value,
            Permission::EnrollmentsImport->value,
            Permission::EnrollmentsExport->value,
        ];
    }
}
