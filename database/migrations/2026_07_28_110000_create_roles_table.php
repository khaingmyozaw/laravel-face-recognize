<?php

use App\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->json('permissions');
            $table->timestamps();
        });

        $adminId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'label' => 'Admin',
            'permissions' => json_encode(Permission::values()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gateId = DB::table('roles')->insertGetId([
            'name' => 'gate',
            'label' => 'Gate',
            'permissions' => json_encode([
                Permission::EnrollmentsView->value,
                Permission::EnrollmentsCreate->value,
                Permission::EnrollmentsUpdate->value,
                Permission::EnrollmentsDelete->value,
                Permission::EnrollmentsSearch->value,
                Permission::EnrollmentsImport->value,
                Permission::EnrollmentsExport->value,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->after('email')
                ->constrained('roles')
                ->restrictOnDelete();
        });

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')->where('role', 'admin')->update(['role_id' => $adminId]);
            DB::table('users')->where('role', 'gate')->update(['role_id' => $gateId]);
            DB::table('users')->whereNull('role_id')->update(['role_id' => $gateId]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        DB::table('users')->whereNull('role_id')->update(['role_id' => $gateId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('gate')->after('email');
        });

        $roles = DB::table('roles')->pluck('name', 'id');

        foreach ($roles as $id => $name) {
            DB::table('users')->where('role_id', $id)->update(['role' => $name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
