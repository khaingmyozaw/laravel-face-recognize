import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ManagedRole, PermissionOption } from '@/types';

type RoleFormFieldsProps = {
    role?: ManagedRole;
    permissionOptions: PermissionOption[];
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
};

export function RoleFormFields({
    role,
    permissionOptions,
    errors,
    processing,
    submitLabel,
}: RoleFormFieldsProps) {
    const [selected, setSelected] = useState<string[]>(
        role?.permissions ?? [],
    );

    function togglePermission(permission: string, checked: boolean) {
        setSelected((current) => {
            if (checked) {
                return current.includes(permission)
                    ? current
                    : [...current, permission];
            }

            return current.filter((value) => value !== permission);
        });
    }

    return (
        <div className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="label">Label</Label>
                    <Input
                        id="label"
                        name="label"
                        required
                        defaultValue={role?.label ?? ''}
                        placeholder="Gate operator"
                    />
                    <InputError message={errors.label} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="name">Key</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        defaultValue={role?.name ?? ''}
                        placeholder="gate_operator"
                    />
                    <p className="text-xs text-muted-foreground">
                        Lowercase letters, numbers, dashes, and underscores.
                    </p>
                    <InputError message={errors.name} />
                </div>
            </div>

            {selected.map((permission) => (
                <input
                    key={permission}
                    type="hidden"
                    name="permissions[]"
                    value={permission}
                />
            ))}

            <div className="space-y-3">
                <Label>Permissions</Label>
                <div className="grid gap-3 sm:grid-cols-2">
                    {permissionOptions.map((option) => {
                        const checked = selected.includes(option.value);

                        return (
                            <label
                                key={option.value}
                                className="flex cursor-pointer items-start gap-3 rounded-md border px-3 py-3"
                            >
                                <Checkbox
                                    checked={checked}
                                    onCheckedChange={(value) =>
                                        togglePermission(
                                            option.value,
                                            value === true,
                                        )
                                    }
                                    className="mt-0.5"
                                />
                                <span className="space-y-1">
                                    <Label className="cursor-pointer">
                                        {option.label}
                                    </Label>
                                    <span className="block text-xs text-muted-foreground">
                                        {option.value}
                                    </span>
                                </span>
                            </label>
                        );
                    })}
                </div>
                <InputError message={errors.permissions} />
            </div>

            <div className="flex justify-end gap-2">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Saving...' : submitLabel}
                </Button>
            </div>
        </div>
    );
}
