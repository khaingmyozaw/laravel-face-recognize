import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index, show } from '@/routes/roles';
import type { ManagedRole, PermissionOption } from '@/types';

type Props = {
    role: ManagedRole;
    permissionOptions: PermissionOption[];
};

export default function RolesShow({ role, permissionOptions }: Props) {
    const can = useCan();
    const isAdmin = role.name === 'admin';
    const labels = Object.fromEntries(
        permissionOptions.map((option) => [option.value, option.label]),
    );

    function confirmDelete() {
        if (!window.confirm(`Delete role ${role.label}?`)) {
            return;
        }

        router.delete(RoleController.destroy.url(role.id));
    }

    return (
        <>
            <Head title={role.label} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={role.label}
                        description={`Role key: ${role.name}`}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={index()}>Back</Link>
                        </Button>
                        {can('roles.update') && !isAdmin && (
                            <Button variant="outline" asChild>
                                <Link href={RoleController.edit.url(role.id)}>
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                        )}
                        {can('roles.delete') && !isAdmin && (
                            <Button
                                variant="destructive"
                                disabled={(role.users_count ?? 0) > 0}
                                onClick={confirmDelete}
                            >
                                <Trash2 />
                                Delete
                            </Button>
                        )}
                    </div>
                </div>

                <div className="rounded-xl border p-6">
                    <h3 className="mb-4 text-sm font-medium">
                        Permissions ({role.permissions.length})
                    </h3>
                    {role.permissions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            This role has no permissions assigned.
                        </p>
                    ) : (
                        <ul className="grid gap-2 sm:grid-cols-2">
                            {role.permissions.map((permission) => (
                                <li
                                    key={permission}
                                    className="rounded-md border px-3 py-2 text-sm"
                                >
                                    <p className="font-medium">
                                        {labels[permission] ?? permission}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {permission}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}

RolesShow.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Roles',
            href: index(),
        },
        {
            title: props.role.label,
            href: show(props.role.id),
        },
    ],
});
