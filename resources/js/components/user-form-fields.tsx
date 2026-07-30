import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ManagedUser, UserRoleOption } from '@/types';

type UserFormFieldsProps = {
    user?: ManagedUser;
    roles: UserRoleOption[];
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
    requirePassword?: boolean;
};

export function UserFormFields({
    user,
    roles,
    errors,
    processing,
    submitLabel,
    requirePassword = true,
}: UserFormFieldsProps) {
    return (
        <div className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        defaultValue={user?.name ?? ''}
                        autoComplete="name"
                        placeholder="Full name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        defaultValue={user?.email ?? ''}
                        autoComplete="username"
                        placeholder="email@example.com"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="role_id">Role</Label>
                    <select
                        id="role_id"
                        name="role_id"
                        required
                        defaultValue={user?.role_id ?? roles[0]?.value}
                        className="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {roles.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.role_id} />
                </div>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="password">
                        Password
                        {!requirePassword && (
                            <span className="ml-1 font-normal text-muted-foreground">
                                (leave blank to keep current)
                            </span>
                        )}
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required={requirePassword}
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        Confirm password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required={requirePassword}
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>
            </div>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Saving...' : submitLabel}
                </Button>
            </div>
        </div>
    );
}
