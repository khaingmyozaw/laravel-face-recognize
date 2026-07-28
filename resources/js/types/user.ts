export type UserRoleOption = {
    value: number;
    label: string;
};

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_id: number;
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

export type PermissionOption = {
    value: string;
    label: string;
};

export type ManagedRole = {
    id: number;
    name: string;
    label: string;
    permissions: string[];
    users_count?: number;
    created_at: string | null;
    updated_at: string | null;
};
