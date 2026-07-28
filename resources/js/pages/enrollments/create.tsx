import { Form, Head, Link } from '@inertiajs/react';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import { EnrollmentFormFields } from '@/components/enrollment-form-fields';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/enrollments';

export default function EnrollmentsCreate() {
    return (
        <>
            <Head title="New enrollment" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="New enrollment"
                        description="Add a person and optional face images."
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <Form
                    {...EnrollmentController.store.form()}
                    encType="multipart/form-data"
                    className="rounded-xl border p-6"
                >
                    {({ processing, errors }) => (
                        <EnrollmentFormFields
                            errors={errors}
                            processing={processing}
                            submitLabel="Create enrollment"
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

EnrollmentsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Enrollments',
            href: index(),
        },
        {
            title: 'Create',
            href: create(),
        },
    ],
};
