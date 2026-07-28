import { Form, Head, Link } from '@inertiajs/react';
import { ScanFace, Video } from 'lucide-react';
import { useEffect, useState } from 'react';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import Heading from '@/components/heading';
import { ImageDropzone } from '@/components/image-dropzone';
import { Button } from '@/components/ui/button';
import { VideoDropzone } from '@/components/video-dropzone';
import { cn } from '@/lib/utils';
import { index, search } from '@/routes/enrollments';
import type { Enrollment } from '@/types';

type SearchMode = 'photo' | 'video';

type SearchMatch = {
    similarity: number;
    is_match: boolean;
    enrollment: Enrollment;
};

type SearchResult = {
    matches: SearchMatch[];
    face_count: number | null;
    frames_sampled: number | null;
    frames_with_face: number | null;
    threshold: number | null;
    candidate_count: number;
    error: string | null;
};

type Props = {
    result: SearchResult | null;
};

export default function EnrollmentsSearch({ result }: Props) {
    const [mode, setMode] = useState<SearchMode>('photo');

    useEffect(() => {
        if (result?.frames_sampled != null) {
            setMode('video');
        } else if (result?.face_count != null) {
            setMode('photo');
        }
    }, [result]);

    return (
        <>
            <Head title="Search by face" />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Search by face"
                        description="Upload a photo or video to find matching enrollments."
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back</Link>
                    </Button>
                </div>

                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant={mode === 'photo' ? 'default' : 'outline'}
                        onClick={() => setMode('photo')}
                    >
                        <ScanFace />
                        Photo
                    </Button>
                    <Button
                        type="button"
                        variant={mode === 'video' ? 'default' : 'outline'}
                        onClick={() => setMode('video')}
                    >
                        <Video />
                        Video
                    </Button>
                </div>

                <div className={cn(mode !== 'photo' && 'hidden')}>
                    <Form
                        {...EnrollmentController.identify.form()}
                        encType="multipart/form-data"
                        className="rounded-xl border p-6"
                        options={{ preserveScroll: true }}
                    >
                        {({ processing, errors }) => (
                            <div className="grid gap-6">
                                <ImageDropzone
                                    name="image"
                                    label="Probe photo"
                                    multiple={false}
                                    errors={errors}
                                />

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        <ScanFace />
                                        {processing
                                            ? 'Searching…'
                                            : 'Search enrollments'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>

                <div className={cn(mode !== 'video' && 'hidden')}>
                    <Form
                        {...EnrollmentController.identifyVideo.form()}
                        encType="multipart/form-data"
                        className="rounded-xl border p-6"
                        options={{ preserveScroll: true }}
                    >
                        {({ processing, errors }) => (
                            <div className="grid gap-6">
                                <VideoDropzone
                                    name="video"
                                    label="Probe video"
                                    errors={errors}
                                />

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        <Video />
                                        {processing
                                            ? 'Searching…'
                                            : 'Search enrollments'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>

                {result && <SearchResults result={result} />}
            </div>
        </>
    );
}

function SearchResults({ result }: { result: SearchResult }) {
    if (result.error) {
        return (
            <div className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                {result.error}
            </div>
        );
    }

    if (result.matches.length === 0) {
        return (
            <div className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                No matching enrollments found
                {formatDetectionHint(result)}.
            </div>
        );
    }

    return (
        <div className="grid gap-4">
            <div className="flex flex-wrap items-baseline justify-between gap-2">
                <h2 className="text-sm font-medium">
                    Matches ({result.matches.length})
                </h2>
                <p className="text-xs text-muted-foreground">
                    Compared against {result.candidate_count} enrolled face
                    {result.candidate_count === 1 ? '' : 's'}
                    {result.threshold !== null
                        ? ` · threshold ${result.threshold.toFixed(2)}`
                        : ''}
                    {result.frames_sampled !== null
                        ? ` · frames ${result.frames_with_face ?? 0}/${result.frames_sampled}`
                        : ''}
                </p>
            </div>

            <div className="overflow-x-auto rounded-xl border">
                <table className="w-full min-w-[640px] text-left text-sm">
                    <thead className="border-b bg-muted/40">
                        <tr>
                            <th className="px-4 py-3 font-medium">Photo</th>
                            <th className="px-4 py-3 font-medium">Name</th>
                            <th className="px-4 py-3 font-medium">NRC</th>
                            <th className="px-4 py-3 font-medium">Similarity</th>
                            <th className="px-4 py-3 font-medium">Match</th>
                            <th className="px-4 py-3 font-medium text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {result.matches.map((match) => {
                            const thumb = match.enrollment.images[0];

                            return (
                                <tr
                                    key={match.enrollment.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3">
                                        {thumb ? (
                                            <img
                                                src={thumb.url}
                                                alt={match.enrollment.name}
                                                className="size-12 rounded-md object-cover"
                                            />
                                        ) : (
                                            <div className="flex size-12 items-center justify-center rounded-md bg-muted text-xs text-muted-foreground">
                                                N/A
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        <Link
                                            href={EnrollmentController.show.url(
                                                match.enrollment.id,
                                            )}
                                            className="hover:underline"
                                        >
                                            {match.enrollment.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {match.enrollment.nrc || '—'}
                                    </td>
                                    <td className="px-4 py-3 tabular-nums">
                                        {(match.similarity * 100).toFixed(1)}%
                                    </td>
                                    <td className="px-4 py-3">
                                        {match.is_match ? (
                                            <span className="text-emerald-700 dark:text-emerald-400">
                                                Yes
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                Below threshold
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={EnrollmentController.show.url(
                                                    match.enrollment.id,
                                                )}
                                            >
                                                View
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function formatDetectionHint(result: SearchResult): string {
    if (result.frames_sampled !== null) {
        return ` (faces in ${result.frames_with_face ?? 0} of ${result.frames_sampled} frames)`;
    }

    if (result.face_count !== null) {
        return ` (faces detected: ${result.face_count})`;
    }

    return '';
}

EnrollmentsSearch.layout = {
    breadcrumbs: [
        {
            title: 'Enrollments',
            href: index(),
        },
        {
            title: 'Search',
            href: search(),
        },
    ],
};
