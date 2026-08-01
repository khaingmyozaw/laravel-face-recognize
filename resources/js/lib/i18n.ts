import mm from '../../../lang/mm.json';

export const AVAILABLE_LOCALES = ['en', 'mm'] as const;

export type Locale = (typeof AVAILABLE_LOCALES)[number];

export type TranslationReplacements = Record<string, string | number>;

const catalogs: Record<Exclude<Locale, 'en'>, Record<string, string>> = {
    mm,
};

export function isLocale(value: string): value is Locale {
    return (AVAILABLE_LOCALES as readonly string[]).includes(value);
}

export function translate(
    locale: Locale,
    key: string,
    replace: TranslationReplacements = {},
): string {
    const catalog = locale === 'en' ? undefined : catalogs[locale];
    let translation = catalog?.[key] ?? key;

    for (const [placeholder, value] of Object.entries(replace)) {
        translation = translation.replaceAll(`:${placeholder}`, String(value));
    }

    return translation;
}
