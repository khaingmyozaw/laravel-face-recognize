import { Form, Head, Link } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { UserFormFields } from '@/components/user-form-fields';
import { useTranslations } from '@/hooks/use-translations';
import { edit, index } from '@/routes/users';
import type { ManagedUser, UserRoleOption } from '@/types';

type Props = {
    user: ManagedUser;
    roles: UserRoleOption[];
};

export default function UsersEdit({ user, roles }: Props) {
    const { t } = useTranslations();
    const pageTitle = t('Edit :name', { name: user.name });

    return (
        <>
            <Head title={pageTitle} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={pageTitle}
                        description="Update account details or role."
                    />
                    <Button variant="outline" asChild>
                        <Link href={UserController.show.url(user.id)}>
                            {t('View')}
                        </Link>
                    </Button>
                </div>

                <Form
                    {...UserController.update.form(user.id)}
                    className="rounded-xl border p-6"
                >
                    {({ processing, errors }) => (
                        <UserFormFields
                            user={user}
                            roles={roles}
                            errors={errors}
                            processing={processing}
                            submitLabel="Save changes"
                            requirePassword={false}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

UsersEdit.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
        {
            title: props.user.name,
            href: UserController.show.url(props.user.id),
        },
        {
            title: 'Edit',
            href: edit(props.user.id),
        },
    ],
});
