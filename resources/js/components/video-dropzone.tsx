import { Film, Trash2 } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const ACCEPTED_TYPES = [
    'video/mp4',
    'video/quicktime',
    'video/webm',
    'video/x-m4v',
    'video/x-matroska',
    'video/x-msvideo',
];
const ACCEPTED_EXTENSIONS = ['.mp4', '.mov', '.m4v', '.webm', '.mkv', '.avi'];
const MAX_SIZE_MB = 50;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

type SelectedVideo = {
    id: string;
    file: File;
    preview: string;
};

type VideoDropzoneProps = {
    name?: string;
    errors?: Record<string, string>;
    label?: string;
};

export function VideoDropzone({
    name = 'video',
    errors = {},
    label = 'Probe video',
}: VideoDropzoneProps) {
    const inputId = useId();
    const inputRef = useRef<HTMLInputElement>(null);
    const videoRef = useRef<SelectedVideo | null>(null);
    const [video, setVideo] = useState<SelectedVideo | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);

    videoRef.current = video;

    useEffect(() => {
        return () => {
            if (videoRef.current) {
                URL.revokeObjectURL(videoRef.current.preview);
            }
        };
    }, []);

    useEffect(() => {
        syncInputFiles(inputRef.current, video ? [video.file] : []);
    }, [video]);

    function addFile(fileList: FileList | File[]) {
        const [file] = Array.from(fileList);

        if (!file) {
            return;
        }

        const nextError = validateIncoming(file);

        if (nextError) {
            setLocalError(nextError);

            return;
        }

        setLocalError(null);

        setVideo((current) => {
            if (current) {
                URL.revokeObjectURL(current.preview);
            }

            return {
                id: `${file.name}-${file.size}-${file.lastModified}-${crypto.randomUUID()}`,
                file,
                preview: URL.createObjectURL(file),
            };
        });
    }

    function removeVideo() {
        setVideo((current) => {
            if (current) {
                URL.revokeObjectURL(current.preview);
            }

            return null;
        });
        setLocalError(null);
    }

    function onDrop(event: React.DragEvent<HTMLLabelElement>) {
        event.preventDefault();
        setIsDragging(false);
        addFile(event.dataTransfer.files);
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor={inputId}>{label}</Label>

            <input
                ref={inputRef}
                id={inputId}
                name={name}
                type="file"
                accept={[...ACCEPTED_TYPES, ...ACCEPTED_EXTENSIONS].join(',')}
                className="sr-only"
                onChange={(event) => {
                    addFile(event.target.files ?? []);
                    event.target.value = '';
                }}
            />

            <label
                htmlFor={inputId}
                onDragEnter={(event) => {
                    event.preventDefault();
                    setIsDragging(true);
                }}
                onDragOver={(event) => {
                    event.preventDefault();
                    setIsDragging(true);
                }}
                onDragLeave={(event) => {
                    event.preventDefault();
                    setIsDragging(false);
                }}
                onDrop={onDrop}
                className={cn(
                    'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-6 py-10 text-center transition-colors',
                    isDragging
                        ? 'border-primary bg-primary/5'
                        : 'border-muted-foreground/30 hover:border-primary/50 hover:bg-muted/40',
                )}
            >
                <Film className="size-8 text-muted-foreground" />
                <div className="space-y-1">
                    <p className="text-sm font-medium">
                        Drag and drop a video here, or click to browse
                    </p>
                    <p className="text-xs text-muted-foreground">
                        MP4, MOV, M4V, WebM, MKV, or AVI. Max {MAX_SIZE_MB}MB.
                    </p>
                </div>
            </label>

            <InputError message={localError ?? undefined} />
            <InputError message={errors.video} />

            {video && (
                <div className="relative mt-2 overflow-hidden rounded-md border">
                    <video
                        src={video.preview}
                        controls
                        className="aspect-video max-h-64 w-full bg-black object-contain"
                    />
                    <div className="flex items-center justify-between gap-2 border-t px-3 py-2">
                        <p className="truncate text-xs text-muted-foreground">
                            {video.file.name}
                        </p>
                        <Button
                            type="button"
                            variant="destructive"
                            size="icon"
                            className="size-8 shrink-0"
                            onClick={removeVideo}
                            aria-label={`Remove ${video.file.name}`}
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

function syncInputFiles(input: HTMLInputElement | null, files: File[]): void {
    if (!input) {
        return;
    }

    const dataTransfer = new DataTransfer();
    files.forEach((file) => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
}

function validateIncoming(file: File): string | null {
    const extension = `.${file.name.split('.').pop()?.toLowerCase() ?? ''}`;
    const typeOk =
        ACCEPTED_TYPES.includes(file.type) ||
        ACCEPTED_EXTENSIONS.includes(extension);

    if (!typeOk) {
        return `${file.name} is not a supported video type.`;
    }

    if (file.size > MAX_SIZE_BYTES) {
        return `${file.name} exceeds the ${MAX_SIZE_MB}MB limit.`;
    }

    return null;
}
