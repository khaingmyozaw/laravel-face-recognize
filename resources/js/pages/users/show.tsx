import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/users';
import type { Auth, ManagedUser } from '@/types';

type Props = {
    user: ManagedUser;
};

type PageProps = {
    auth: Auth;
};

export default function UsersShow({ user }: Props) {
    const { auth } = usePage<PageProps>().props;
    const isSelf = user.id === auth.user.id;

    function confirmDelete() {
        if (!window.confirm(`Delete user ${user.name}?`)) {
            return;
        }

        router.delete(UserController.destroy.url(user.id));
    }

    return (
        <>
            <Head title={user.name} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={user.name}
                        description="User account details."
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={index()}>Back</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={UserController.edit.url(user.id)}>
                                <Pencil />
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={isSelf}
                            onClick={confirmDelete}
                        >
                            <Trash2 />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 rounded-xl border p-6 sm:grid-cols-2">
                    <Detail label="Email" value={user.email} />
                    <Detail
                        label="Role"
                        value={user.role}
                        className="capitalize"
                    />
                    <Detail
                        label="Email verified"
                        value={user.email_verified_at ? 'Yes' : 'No'}
                    />
                    <Detail
                        label="Created"
                        value={
                            user.created_at
                                ? new Date(user.created_at).toLocaleString()
                                : '—'
                        }
                    />
                </div>
            </div>
        </>
    );
}

function Detail({
    label,
    value,
    className,
}: {
    label: string;
    value: string;
    className?: string;
}) {
    return (
        <div className="space-y-1">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className={cn('text-sm font-medium', className)}>{value}</p>
        </div>
    );
}

UsersShow.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
        {
            title: props.user.name,
            href: show(props.user.id),
        },
    ],
});
