import type { HTMLAttributes } from 'react';
import { useLocale } from '@/hooks/use-locale';
import type { Locale } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const tabs: { value: Locale; nativeLabel: string }[] = [
    { value: 'en', nativeLabel: 'English' },
    { value: 'mm', nativeLabel: 'မြန်မာ' },
];

export default function LanguageTabs({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { locale, updateLocale } = useLocale();

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, nativeLabel }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateLocale(value)}
                    className={cn(
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                        locale === value
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    <span className="text-sm">{nativeLabel}</span>
                </button>
            ))}
        </div>
    );
}
