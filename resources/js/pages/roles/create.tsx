import { Form, Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import { PageHead } from '@/components/page-head';
import { RoleFormFields } from '@/components/role-form-fields';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/roles';
import type { PermissionOption } from '@/types';

type Props = {
    permissionOptions: PermissionOption[];
};

export default function RolesCreate({ permissionOptions }: Props) {
    return (
        <>
            <PageHead title="New role" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="New role"
                        description="Create a role and choose its permissions."
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Form
                    {...RoleController.store.form()}
                    className="rounded-xl border p-6"
                >
                    {({ processing, errors }) => (
                        <RoleFormFields
                            permissionOptions={permissionOptions}
                            errors={errors}
                            processing={processing}
                            submitLabel="Create role"
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

RolesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: index(),
        },
        {
            title: 'Create',
            href: create(),
        },
    ],
};
