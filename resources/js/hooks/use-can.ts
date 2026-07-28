import { usePage } from '@inertiajs/react';
import type { Auth, Permission } from '@/types';

type PageProps = {
    auth: Auth;
};

export function useCan(): (permission: Permission) => boolean {
    const { auth } = usePage<PageProps>().props;

    return (permission: Permission): boolean =>
        auth.permissions.includes(permission);
}
