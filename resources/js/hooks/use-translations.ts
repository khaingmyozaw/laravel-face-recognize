import { useLocale } from '@/hooks/use-locale';
import type { TranslationReplacements } from '@/lib/i18n';
import { translate } from '@/lib/i18n';

export type TranslateFn = (
    key: string,
    replace?: TranslationReplacements,
) => string;

export function useTranslations(): { t: TranslateFn; locale: string } {
    const { locale } = useLocale();

    const t: TranslateFn = (key, replace = {}) =>
        translate(locale, key, replace);

    return { t, locale };
}
