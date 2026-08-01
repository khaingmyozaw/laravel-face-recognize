import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';

export function PageHead({ title }: { title: string }) {
    const { t } = useTranslations();

    return <Head title={t(title)} />;
}
