export type UserRole = 'admin' | 'gate';

export type UserRoleOption = {
    value: UserRole;
    label: string;
};

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    email_verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type PaginatedUsers = {
    data: ManagedUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};
