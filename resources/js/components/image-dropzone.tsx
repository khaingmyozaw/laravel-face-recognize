import { ImagePlus, Trash2 } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn, createClientId } from '@/lib/utils';

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const DEFAULT_MAX_FILES = 10;
const MAX_SIZE_MB = 5;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

type SelectedImage = {
    id: string;
    file: File;
    preview: string;
};

type ImageDropzoneProps = {
    name?: string;
    errors?: Record<string, string>;
    label?: string;
    maxFiles?: number;
    multiple?: boolean;
};

export function ImageDropzone({
    name = 'images[]',
    errors = {},
    label = 'Face images',
    maxFiles = DEFAULT_MAX_FILES,
    multiple = true,
}: ImageDropzoneProps) {
    const inputId = useId();
    const inputRef = useRef<HTMLInputElement>(null);
    const imagesRef = useRef<SelectedImage[]>([]);
    const [images, setImages] = useState<SelectedImage[]>([]);
    const [isDragging, setIsDragging] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);
    const effectiveMax = multiple ? maxFiles : 1;

    useEffect(() => {
        imagesRef.current = images;
    }, [images]);

    useEffect(() => {
        return () => {
            imagesRef.current.forEach((image) =>
                URL.revokeObjectURL(image.preview),
            );
        };
    }, []);

    useEffect(() => {
        syncInputFiles(
            inputRef.current,
            images.map((image) => image.file),
        );
    }, [images]);

    function addFiles(fileList: FileList | File[]) {
        const incoming = Array.from(fileList);
        const nextError = validateIncoming(
            incoming,
            multiple ? images.length : 0,
            effectiveMax,
        );

        if (nextError) {
            setLocalError(nextError);

            return;
        }

        setLocalError(null);

        const mapped = incoming.map((file) => ({
            id: `${file.name}-${file.size}-${file.lastModified}-${createClientId()}`,
            file,
            preview: URL.createObjectURL(file),
        }));

        setImages((current) => {
            if (!multiple) {
                current.forEach((image) => URL.revokeObjectURL(image.preview));

                return mapped.slice(0, 1);
            }

            return [...current, ...mapped];
        });
    }

    function removeImage(id: string) {
        setImages((current) => {
            const target = current.find((image) => image.id === id);

            if (target) {
                URL.revokeObjectURL(target.preview);
            }

            return current.filter((image) => image.id !== id);
        });
        setLocalError(null);
    }

    function onDrop(event: React.DragEvent<HTMLLabelElement>) {
        event.preventDefault();
        setIsDragging(false);
        addFiles(event.dataTransfer.files);
    }

    const fieldError =
        errors.images ??
        errors.image ??
        errors['images.0'] ??
        errors['image.0'];

    return (
        <div className="grid gap-2">
            <Label htmlFor={inputId}>{label}</Label>

            <input
                ref={inputRef}
                id={inputId}
                name={name}
                type="file"
                accept={ACCEPTED_TYPES.join(',')}
                multiple={multiple}
                className="sr-only"
                onChange={(event) => {
                    addFiles(event.target.files ?? []);
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
                <ImagePlus className="size-8 text-muted-foreground" />
                <div className="space-y-1">
                    <p className="text-sm font-medium">
                        Drag and drop {multiple ? 'images' : 'an image'} here,
                        or click to browse
                    </p>
                    <p className="text-xs text-muted-foreground">
                        JPEG, PNG, or WebP
                        {multiple
                            ? `. Up to ${effectiveMax} images, ${MAX_SIZE_MB}MB each.`
                            : `. Max ${MAX_SIZE_MB}MB.`}
                    </p>
                </div>
            </label>

            <InputError message={localError ?? undefined} />
            <InputError message={fieldError} />

            {images.length > 0 && (
                <div className="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {images.map((image) => (
                        <div
                            key={image.id}
                            className="relative overflow-hidden rounded-md border"
                        >
                            <img
                                src={image.preview}
                                alt={image.file.name}
                                className="aspect-square w-full object-cover"
                            />
                            <Button
                                type="button"
                                variant="destructive"
                                size="icon"
                                className="absolute top-2 right-2 size-8"
                                onClick={() => removeImage(image.id)}
                                aria-label={`Remove ${image.file.name}`}
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    ))}
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

function validateIncoming(
    files: File[],
    currentCount: number,
    maxFiles: number,
): string | null {
    if (files.length === 0) {
        return null;
    }

    if (currentCount + files.length > maxFiles) {
        return maxFiles === 1
            ? 'Upload a single image only.'
            : `You can upload up to ${maxFiles} images.`;
    }

    const invalidType = files.find(
        (file) => !ACCEPTED_TYPES.includes(file.type),
    );

    if (invalidType) {
        return `${invalidType.name} is not a supported image type.`;
    }

    const tooLarge = files.find((file) => file.size > MAX_SIZE_BYTES);

    if (tooLarge) {
        return `${tooLarge.name} exceeds the ${MAX_SIZE_MB}MB limit.`;
    }

    return null;
}
