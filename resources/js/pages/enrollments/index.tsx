import { Link, router } from '@inertiajs/react';
import {
    Download,
    FileSpreadsheet,
    Plus,
    Search,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import Heading from '@/components/heading';
import { PageHead } from '@/components/page-head';
import { ImagePreviewDialog } from '@/components/image-preview-dialog';
import type { PreviewImage } from '@/components/image-preview-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCan } from '@/hooks/use-can';
import { useTranslations } from '@/hooks/use-translations';
import { index as enrollmentsIndex } from '@/routes/enrollments';
import type { PaginatedEnrollments } from '@/types';

type Props = {
    enrollments: PaginatedEnrollments;
    filters: {
        search: string;
    };
};

export default function EnrollmentsIndex({ enrollments, filters }: Props) {
    const can = useCan();
    const { t } = useTranslations();
    const [preview, setPreview] = useState<PreviewImage | null>(null);
    const [search, setSearch] = useState<string>(filters.search ?? '');
    const [isImporting, setIsImporting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement | null>(null);

    useEffect(() => {
        const current = filters.search ?? '';
        const trimmed = search.trim();

        if (trimmed === current) {
            return;
        }

        const timer = window.setTimeout(() => {
            router.get(
                enrollmentsIndex().url,
                trimmed === '' ? {} : { search: trimmed },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 350);

        return () => window.clearTimeout(timer);
    }, [search, filters.search]);

    function onSubmitSearch(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
    }

    function clearSearch() {
        setSearch('');
    }

    function confirmDelete(id: number, name: string) {
        if (
            !window.confirm(
                t('Delete enrollment for :name?', { name }),
            )
        ) {
            return;
        }

        router.delete(EnrollmentController.destroy.url(id));
    }

    function triggerImport() {
        fileInputRef.current?.click();
    }

    function onFileSelected(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        setIsImporting(true);
        router.post(EnrollmentController.import.url(), formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setIsImporting(false);

                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    }

    return (
        <>
            <PageHead title="Enrollments" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Enrollments"
                        description="Manage enrolled people and their face images."
                    />
                    <div className="flex flex-wrap gap-2">
                        {can('enrollments.search') && (
                            <Button variant="outline" asChild>
                                <Link href={EnrollmentController.search.url()}>
                                    {t('Search by face')}
                                </Link>
                            </Button>
                        )}
                        {can('enrollments.export') && (
                            <Button variant="outline" asChild>
                                <a
                                    href={EnrollmentController.export.url()}
                                    download
                                >
                                    <Download />
                                    {t('Export')}
                                </a>
                            </Button>
                        )}
                        {can('enrollments.import') && (
                            <>
                                <Button variant="outline" asChild>
                                    <a
                                        href={EnrollmentController.importTemplate.url()}
                                        download
                                    >
                                        <FileSpreadsheet />
                                        {t('Template')}
                                    </a>
                                </Button>
                                <Button
                                    variant="outline"
                                    type="button"
                                    onClick={triggerImport}
                                    disabled={isImporting}
                                >
                                    <Upload />
                                    {isImporting
                                        ? t('Importing…')
                                        : t('Import')}
                                </Button>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    className="hidden"
                                    onChange={onFileSelected}
                                />
                            </>
                        )}
                        {can('enrollments.create') && (
                            <Button asChild>
                                <Link href={EnrollmentController.create.url()}>
                                    <Plus />
                                    {t('New enrollment')}
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <form
                    onSubmit={onSubmitSearch}
                    role="search"
                    className="flex w-full max-w-md items-center gap-2"
                >
                    <div className="relative flex-1">
                        <Search
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            type="search"
                            name="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Search by name, NRC, address…')}
                            aria-label={t('Search enrollments')}
                            className="pr-9 pl-9"
                        />
                        {search !== '' && (
                            <button
                                type="button"
                                onClick={clearSearch}
                                aria-label={t('Clear search')}
                                className="absolute top-1/2 right-2 flex size-6 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </div>
                </form>

                {enrollments.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-muted-foreground">
                        {filters.search
                            ? `No enrollments match “${filters.search}”.`
                            : 'No enrollments yet. Create the first one to get started.'}
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
                                    <th className="px-4 py-3 text-right font-medium">
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
                                                    {can(
                                                        'enrollments.update',
                                                    ) && (
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
                                                    )}
                                                    {can(
                                                        'enrollments.delete',
                                                    ) && (
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
                                                    )}
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
