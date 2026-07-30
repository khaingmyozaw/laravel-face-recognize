import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index as rolesIndex } from '@/routes/roles';
import type { ManagedRole } from '@/types';

type Props = {
    roles: ManagedRole[];
};

export default function RolesIndex({ roles }: Props) {
    const can = useCan();

    function confirmDelete(role: ManagedRole) {
        if (!window.confirm(`Delete role ${role.label}?`)) {
            return;
        }

        router.delete(RoleController.destroy.url(role.id));
    }

    return (
        <>
            <Head title="Roles" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="Manage roles and the permissions assigned to each."
                    />
                    {can('roles.create') && (
                        <Button asChild>
                            <Link href={RoleController.create.url()}>
                                <Plus />
                                New role
                            </Link>
                        </Button>
                    )}
                </div>

                {roles.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-muted-foreground">
                        No roles configured yet.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Role
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Permissions
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Users
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {roles.map((role) => {
                                    const isAdmin = role.name === 'admin';

                                    return (
                                        <tr
                                            key={role.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                <Link
                                                    href={RoleController.show.url(
                                                        role.id,
                                                    )}
                                                    className="hover:underline"
                                                >
                                                    {role.label}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {role.name}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {role.permissions.length}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {role.users_count ?? 0}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={RoleController.show.url(
                                                                role.id,
                                                            )}
                                                        >
                                                            View
                                                        </Link>
                                                    </Button>
                                                    {can('roles.update') &&
                                                        !isAdmin && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={RoleController.edit.url(
                                                                        role.id,
                                                                    )}
                                                                >
                                                                    <Pencil />
                                                                    Edit
                                                                </Link>
                                                            </Button>
                                                        )}
                                                    {can('roles.delete') &&
                                                        !isAdmin && (
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                disabled={
                                                                    (role.users_count ??
                                                                        0) > 0
                                                                }
                                                                onClick={() =>
                                                                    confirmDelete(
                                                                        role,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 />
                                                                Delete
                                                            </Button>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: rolesIndex(),
        },
    ],
};
