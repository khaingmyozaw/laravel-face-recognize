import { Head } from '@inertiajs/react';
import { Database, Download, FileArchive, Images } from 'lucide-react';
import BackupController from '@/actions/App/Http/Controllers/BackupController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as backupsIndex } from '@/routes/backups';

export default function BackupsIndex() {
    return (
        <>
            <Head title="Backups" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Backups"
                    description="Download a database dump or a zip of enrollment images."
                />

                <div className="grid gap-4 md:grid-cols-2">
                    <section className="rounded-xl border p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <Database className="size-5 text-muted-foreground" />
                            <h2 className="text-base font-medium">Database</h2>
                        </div>
                        <p className="mb-4 text-sm text-muted-foreground">
                            Export the full application database as SQL.
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <a
                                    href={BackupController.database.url()}
                                    download
                                >
                                    <Download />
                                    Download .sql
                                </a>
                            </Button>
                            <Button variant="outline" asChild>
                                <a
                                    href={BackupController.database.url({
                                        query: { format: 'zip' },
                                    })}
                                    download
                                >
                                    <FileArchive />
                                    Download .sql.zip
                                </a>
                            </Button>
                        </div>
                    </section>

                    <section className="rounded-xl border p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <Images className="size-5 text-muted-foreground" />
                            <h2 className="text-base font-medium">
                                Enrollment images
                            </h2>
                        </div>
                        <p className="mb-4 text-sm text-muted-foreground">
                            Zip all files under the enrollments storage folder,
                            preserving folder structure.
                        </p>
                        <Button variant="outline" asChild>
                            <a href={BackupController.images.url()} download>
                                <Download />
                                Download images zip
                            </a>
                        </Button>
                    </section>
                </div>
            </div>
        </>
    );
}

BackupsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Backups',
            href: backupsIndex(),
        },
    ],
};
