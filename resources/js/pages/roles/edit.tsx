import { Form, Head, Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import { RoleFormFields } from '@/components/role-form-fields';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { edit, index } from '@/routes/roles';
import type { ManagedRole, PermissionOption } from '@/types';

type Props = {
    role: ManagedRole;
    permissionOptions: PermissionOption[];
};

export default function RolesEdit({ role, permissionOptions }: Props) {
    const { t } = useTranslations();
    const pageTitle = t('Edit :name', { name: role.label });

    return (
        <>
            <Head title={pageTitle} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={pageTitle}
                        description="Update this role's label, key, and permissions."
                    />
                    <Button variant="outline" asChild>
                        <Link href={RoleController.show.url(role.id)}>
                            {t('View')}
                        </Link>
                    </Button>
                </div>

                <Form
                    {...RoleController.update.form(role.id)}
                    className="rounded-xl border p-6"
                >
                    {({ processing, errors }) => (
                        <RoleFormFields
                            role={role}
                            permissionOptions={permissionOptions}
                            errors={errors}
                            processing={processing}
                            submitLabel="Save role"
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

RolesEdit.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Roles',
            href: index(),
        },
        {
            title: props.role.label,
            href: RoleController.show.url(props.role.id),
        },
        {
            title: 'Edit',
            href: edit(props.role.id),
        },
    ],
});
