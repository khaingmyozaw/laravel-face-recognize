import { Form, Head, Link } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { UserFormFields } from '@/components/user-form-fields';
import { create, index } from '@/routes/users';
import type { UserRoleOption } from '@/types';

type Props = {
    roles: UserRoleOption[];
};

export default function UsersCreate({ roles }: Props) {
    return (
        <>
            <Head title="New user" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="New user"
                        description="Create an admin or gate account."
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Form
                    {...UserController.store.form()}
                    className="rounded-xl border p-6"
                >
                    {({ processing, errors }) => (
                        <UserFormFields
                            roles={roles}
                            errors={errors}
                            processing={processing}
                            submitLabel="Create user"
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

UsersCreate.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
        {
            title: 'Create',
            href: create(),
        },
    ],
};
