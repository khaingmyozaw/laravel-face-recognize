<?php

use App\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'admin')->first();

        if ($role === null) {
            return;
        }

        $permissions = json_decode((string) $role->permissions, true);

        if (! is_array($permissions)) {
            $permissions = [];
        }

        $permission = Permission::BackupsDownload->value;

        if (in_array($permission, $permissions, true)) {
            return;
        }

        $permissions[] = $permission;

        DB::table('roles')->where('id', $role->id)->update([
            'permissions' => json_encode(array_values($permissions)),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'admin')->first();

        if ($role === null) {
            return;
        }

        $permissions = json_decode((string) $role->permissions, true);

        if (! is_array($permissions)) {
            return;
        }

        $permissions = array_values(array_filter(
            $permissions,
            fn (mixed $value): bool => $value !== Permission::BackupsDownload->value,
        ));

        DB::table('roles')->where('id', $role->id)->update([
            'permissions' => json_encode($permissions),
            'updated_at' => now(),
        ]);
    }
};
