export type Permission =
    | 'enrollments.view'
    | 'enrollments.create'
    | 'enrollments.update'
    | 'enrollments.delete'
    | 'enrollments.search'
    | 'enrollments.import'
    | 'enrollments.export'
    | 'users.view'
    | 'users.create'
    | 'users.update'
    | 'users.delete'
    | 'roles.view'
    | 'roles.create'
    | 'roles.update'
    | 'roles.delete'
    | 'backups.download';

export type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_id?: number;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    permissions: Permission[];
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
