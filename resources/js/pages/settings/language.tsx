import Heading from '@/components/heading';
import LanguageTabs from '@/components/language-tabs';
import { PageHead } from '@/components/page-head';
import { useTranslations } from '@/hooks/use-translations';
import { edit as editLanguage } from '@/routes/language';

export default function Language() {
    const { t } = useTranslations();

    return (
        <>
            <PageHead title="Language settings" />

            <h1 className="sr-only">{t('Language settings')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Language settings"
                    description="Choose the language for the application interface"
                />
                <LanguageTabs />
            </div>
        </>
    );
}

Language.layout = {
    breadcrumbs: [
        {
            title: 'Language settings',
            href: editLanguage(),
        },
    ],
};
