import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import Heading from '@/components/heading';
import {
    ImagePreviewDialog,
    type PreviewImage,
} from '@/components/image-preview-dialog';
import { Button } from '@/components/ui/button';
import { index as enrollmentsIndex } from '@/routes/enrollments';
import type { PaginatedEnrollments } from '@/types';

type Props = {
    enrollments: PaginatedEnrollments;
};

export default function EnrollmentsIndex({ enrollments }: Props) {
    const [preview, setPreview] = useState<PreviewImage | null>(null);

    function confirmDelete(id: number, name: string) {
        if (!window.confirm(`Delete enrollment for ${name}?`)) {
            return;
        }

        router.delete(EnrollmentController.destroy.url(id));
    }

    return (
        <>
            <Head title="Enrollments" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Enrollments"
                        description="Manage enrolled people and their face images."
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={EnrollmentController.search.url()}>
                                Search by face
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={EnrollmentController.create.url()}>
                                <Plus />
                                New enrollment
                            </Link>
                        </Button>
                    </div>
                </div>

                {enrollments.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-muted-foreground">
                        No enrollments yet. Create the first one to get started.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[720px] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Photo
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        NRC
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Address
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Images
                                    </th>
                                    <th className="px-4 py-3 font-medium text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {enrollments.data.map((enrollment) => {
                                    const thumb = enrollment.images[0];

                                    return (
                                        <tr
                                            key={enrollment.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                {thumb ? (
                                                    <button
                                                        type="button"
                                                        className="rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                        onClick={() =>
                                                            setPreview({
                                                                url: thumb.url,
                                                                name: enrollment.name,
                                                            })
                                                        }
                                                    >
                                                        <img
                                                            src={thumb.url}
                                                            alt={
                                                                enrollment.name
                                                            }
                                                            className="size-12 cursor-zoom-in rounded-md object-cover transition-opacity hover:opacity-80"
                                                        />
                                                    </button>
                                                ) : (
                                                    <div className="flex size-12 items-center justify-center rounded-md bg-muted text-xs text-muted-foreground">
                                                        N/A
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                <Link
                                                    href={EnrollmentController.show.url(
                                                        enrollment.id,
                                                    )}
                                                    className="hover:underline"
                                                >
                                                    {enrollment.name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {enrollment.nrc || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {enrollment.address}
                                            </td>
                                            <td className="px-4 py-3">
                                                {enrollment.images.length}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={EnrollmentController.edit.url(
                                                                enrollment.id,
                                                            )}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            confirmDelete(
                                                                enrollment.id,
                                                                enrollment.name,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 />
                                                        Delete
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                {enrollments.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {enrollments.links.map((link, index) => (
                            <Button
                                key={`${link.label}-${index}`}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={Boolean(link.url)}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>

            <ImagePreviewDialog
                preview={preview}
                onClose={() => setPreview(null)}
            />
        </>
    );
}

EnrollmentsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Enrollments',
            href: enrollmentsIndex(),
        },
    ],
};
