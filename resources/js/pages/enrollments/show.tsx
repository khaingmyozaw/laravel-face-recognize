import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import Heading from '@/components/heading';
import {
    ImagePreviewDialog
    
} from '@/components/image-preview-dialog';
import type {PreviewImage} from '@/components/image-preview-dialog';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/enrollments';
import type { Enrollment } from '@/types';

type Props = {
    enrollment: Enrollment;
};

export default function EnrollmentsShow({ enrollment }: Props) {
    const [preview, setPreview] = useState<PreviewImage | null>(null);

    function confirmDelete() {
        if (!window.confirm(`Delete enrollment for ${enrollment.name}?`)) {
            return;
        }

        router.delete(EnrollmentController.destroy.url(enrollment.id));
    }

    function confirmDeleteImage(imageId: number) {
        if (!window.confirm('Delete this image?')) {
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
            <Head title={enrollment.name} />

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={enrollment.name}
                        description="Enrollment details and face images."
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={index()}>Back</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={EnrollmentController.edit.url(
                                    enrollment.id,
                                )}
                            >
                                <Pencil />
                                Edit
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            <Trash2 />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 rounded-xl border p-6 sm:grid-cols-2">
                    <Detail label="NRC" value={enrollment.nrc || '—'} />
                    <Detail label="Address" value={enrollment.address} />
                    <Detail label="Father" value={enrollment.father_name} />
                    <Detail label="Mother" value={enrollment.mother_name} />
                    <Detail
                        label="Father alive"
                        value={enrollment.is_f_alive ? 'Yes' : 'No'}
                    />
                    <Detail
                        label="Mother alive"
                        value={enrollment.is_m_alive ? 'Yes' : 'No'}
                    />
                    <div className="sm:col-span-2">
                        <Detail
                            label="Remark"
                            value={enrollment.remark || '—'}
                        />
                    </div>
                </div>

                <div className="rounded-xl border p-6">
                    <h3 className="mb-4 text-sm font-medium">
                        Face images ({enrollment.images.length})
                    </h3>

                    {enrollment.images.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No images uploaded yet.
                        </p>
                    ) : (
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {enrollment.images.map((image) => (
                                <div
                                    key={image.id}
                                    className="group relative overflow-hidden rounded-md"
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
                    )}
                </div>
            </div>

            <ImagePreviewDialog
                preview={preview}
                onClose={() => setPreview(null)}
            />
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className="text-sm font-medium">{value}</p>
        </div>
    );
}

EnrollmentsShow.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Enrollments',
            href: index(),
        },
        {
            title: props.enrollment.name,
            href: show(props.enrollment.id),
        },
    ],
});
