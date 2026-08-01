import { useSyncExternalStore } from 'react';
import type { Locale } from '@/lib/i18n';
import { isLocale } from '@/lib/i18n';

export type UseLocaleReturn = {
    readonly locale: Locale;
    readonly updateLocale: (locale: Locale) => void;
};

const listeners = new Set<() => void>();
let currentLocale: Locale = 'en';

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getCookie = (name: string): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(
        new RegExp(`(?:^|; )${name}=([^;]*)`),
    );

    return match ? decodeURIComponent(match[1]) : null;
};

const getStoredLocale = (): Locale => {
    if (typeof window === 'undefined') {
        return 'en';
    }

    const fromCookie = getCookie('locale');

    if (fromCookie && isLocale(fromCookie)) {
        return fromCookie;
    }

    const stored = localStorage.getItem('locale');

    if (stored && isLocale(stored)) {
        return stored;
    }

    return 'en';
};

const applyLocale = (locale: Locale): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.lang = locale;
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

export function initializeLocale(): void {
    if (typeof window === 'undefined') {
        return;
    }

    currentLocale = getStoredLocale();
    localStorage.setItem('locale', currentLocale);
    setCookie('locale', currentLocale);
    applyLocale(currentLocale);
}

export function useLocale(): UseLocaleReturn {
    const locale: Locale = useSyncExternalStore(
        subscribe,
        () => currentLocale,
        () => 'en',
    );

    const updateLocale = (next: Locale): void => {
        currentLocale = next;
        localStorage.setItem('locale', next);
        setCookie('locale', next);
        applyLocale(next);
        notify();
        window.location.reload();
    };

    return { locale, updateLocale } as const;
}
