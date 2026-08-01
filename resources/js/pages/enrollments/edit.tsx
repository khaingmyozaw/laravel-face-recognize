import { Form, Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import { EnrollmentFormFields } from '@/components/enrollment-form-fields';
import Heading from '@/components/heading';
import { ImagePreviewDialog } from '@/components/image-preview-dialog';
import type { PreviewImage } from '@/components/image-preview-dialog';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { edit, index } from '@/routes/enrollments';
import type { Enrollment } from '@/types';

type Props = {
    enrollment: Enrollment;
};

export default function EnrollmentsEdit({ enrollment }: Props) {
    const [preview, setPreview] = useState<PreviewImage | null>(null);
    const { t } = useTranslations();
    const pageTitle = t('Edit :name', { name: enrollment.name });

    function confirmDeleteImage(imageId: number) {
        if (!window.confirm(t('Delete this image?'))) {
            return;
        }

        router.delete(
            EnrollmentController.destroyImage.url({
                enrollment: enrollment.id,
                image: imageId,
            }),
        );
    }

    return (
        <>
            <Head title={pageTitle} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={pageTitle}
                        description="Update details or add more face images."
                    />
                    <Button variant="outline" asChild>
                        <Link
                            href={EnrollmentController.show.url(enrollment.id)}
                        >
                            View
                        </Link>
                    </Button>
                </div>

                {enrollment.images.length > 0 && (
                    <div className="rounded-xl border p-6">
                        <h3 className="mb-4 text-sm font-medium">
                            Existing images
                        </h3>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {enrollment.images.map((image) => (
                                <div
                                    key={image.id}
                                    className="relative overflow-hidden rounded-md border"
                                >
                                    <button
                                        type="button"
                                        className="w-full rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        onClick={() =>
                                            setPreview({
                                                url: image.url,
                                                name: enrollment.name,
                                            })
                                        }
                                    >
                                        <img
                                            src={image.url}
                                            alt={`${enrollment.name} face`}
                                            className="aspect-square w-full cursor-zoom-in object-cover transition-opacity hover:opacity-80"
                                        />
                                    </button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="icon"
                                        className="absolute top-2 right-2 size-8"
                                        onClick={() =>
                                            confirmDeleteImage(image.id)
                                        }
                                        aria-label="Delete image"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <Form
                    {...EnrollmentController.update.form(enrollment.id)}
                    encType="multipart/form-data"
                    className="rounded-xl border p-6"
                >
                    {({ processing, errors }) => (
                        <EnrollmentFormFields
                            enrollment={enrollment}
                            errors={errors}
                            processing={processing}
                            submitLabel="Save changes"
                        />
                    )}
                </Form>
            </div>

            <ImagePreviewDialog
                preview={preview}
                onClose={() => setPreview(null)}
            />
        </>
    );
}

EnrollmentsEdit.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Enrollments',
            href: index(),
        },
        {
            title: props.enrollment.name,
            href: EnrollmentController.show.url(props.enrollment.id),
        },
        {
            title: 'Edit',
            href: edit(props.enrollment.id),
        },
    ],
});
