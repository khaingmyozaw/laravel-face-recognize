import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type PreviewImage = {
    url: string;
    name: string;
};

type Props = {
    preview: PreviewImage | null;
    onClose: () => void;
};

export function ImagePreviewDialog({ preview, onClose }: Props) {
    return (
        <Dialog
            open={preview !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onClose();
                }
            }}
        >
            <DialogContent className="max-w-2xl p-0 sm:max-w-2xl">
                <DialogHeader className="sr-only">
                    <DialogTitle>
                        {preview?.name ?? 'Enrollment photo'}
                    </DialogTitle>
                    <DialogDescription>
                        Full-size enrollment photo preview
                    </DialogDescription>
                </DialogHeader>
                {preview && (
                    <img
                        src={preview.url}
                        alt={preview.name}
                        className="max-h-[80vh] w-full rounded-lg object-contain"
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
