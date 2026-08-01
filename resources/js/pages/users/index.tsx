import { Link, router, usePage } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { PageHead } from '@/components/page-head';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index as usersIndex } from '@/routes/users';
import type { Auth, PaginatedUsers } from '@/types';

type Props = {
    users: PaginatedUsers;
};

type PageProps = {
    auth: Auth;
};

export default function UsersIndex({ users }: Props) {
    const { auth } = usePage<PageProps>().props;
    const can = useCan();

    function confirmDelete(id: number, name: string) {
        if (!window.confirm(`Delete user ${name}?`)) {
            return;
        }

        router.delete(UserController.destroy.url(id));
    }

    return (
        <>
            <PageHead title="Users" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Users"
                        description="Manage admin and gate accounts."
                    />
                    {can('users.create') && (
                        <Button asChild>
                            <Link href={UserController.create.url()}>
                                <Plus />
                                New user
                            </Link>
                        </Button>
                    )}
                </div>

                {users.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-muted-foreground">
                        No users yet. Create the first one to get started.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Email
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Role
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            <Link
                                                href={UserController.show.url(
                                                    user.id,
                                                )}
                                                className="hover:underline"
                                            >
                                                {user.name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {user.email}
                                        </td>
                                        <td className="px-4 py-3 capitalize">
                                            {user.role}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                {can('users.update') && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={UserController.edit.url(
                                                                user.id,
                                                            )}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                )}
                                                {can('users.delete') && (
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        disabled={
                                                            user.id ===
                                                            auth.user?.id
                                                        }
                                                        onClick={() =>
                                                            confirmDelete(
                                                                user.id,
                                                                user.name,
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
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {users.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {users.links.map((link, index) => (
                            <Button
                                key={`${link.label}-${index}`}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={Boolean(link.url)}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: usersIndex(),
        },
    ],
};
