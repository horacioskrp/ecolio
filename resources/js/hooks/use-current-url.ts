import type { InertiaLinkProps } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { toUrl } from '@/lib/utils';

type Href = NonNullable<InertiaLinkProps['href']>;

export type IsCurrentUrlFn = (urlToCheck: Href, currentUrl?: string) => boolean;

export type WhenCurrentUrlFn = <TIfTrue, TIfFalse = null>(
    urlToCheck: Href,
    ifTrue: TIfTrue,
    ifFalse?: TIfFalse,
) => TIfTrue | TIfFalse;

export type UseCurrentUrlReturn = {
    currentUrl: string;
    /** Correspondance exacte avec l'URL courante. */
    isCurrentUrl: IsCurrentUrlFn;
    /** L'URL courante est-elle cette page **ou l'une de ses sous-pages** ? */
    isUnderUrl: IsCurrentUrlFn;
    /**
     * Parmi plusieurs liens, celui qui correspond le mieux à l'URL courante
     * (le plus spécifique). Retourne son chemin normalisé, ou null.
     */
    findActiveHref: (hrefs: Href[]) => string | null;
    whenCurrentUrl: WhenCurrentUrlFn;
};

/** Chemin normalisé d'un lien : sans origine, sans query ni fragment, sans / final. */
export function toPath(href: Href): string | null {
    const raw = toUrl(href);

    if (!raw || raw === '#' || raw.startsWith('#')) {
        return null;
    }

    let path: string;
    try {
        path = new URL(raw, window?.location.origin).pathname;
    } catch {
        return null;
    }

    return path.length > 1 ? path.replace(/\/+$/, '') : path;
}

/**
 * L'URL courante est-elle `path` ou l'un de ses descendants ?
 *
 * La comparaison se fait **par segment** : `/students` ne doit pas capter
 * `/students-import` ni `/students-stats`, qui sont des entrées de menu
 * distinctes.
 */
function isUnder(current: string, path: string): boolean {
    if (path === '/') {
        return current === '/';
    }

    return current === path || current.startsWith(path + '/');
}

export function useCurrentUrl(): UseCurrentUrlReturn {
    const page = usePage();
    const currentUrlPath = toPath(page.url) ?? '/';

    const isCurrentUrl: IsCurrentUrlFn = (urlToCheck, currentUrl) => {
        const path = toPath(urlToCheck);

        return path !== null && path === (currentUrl ?? currentUrlPath);
    };

    const isUnderUrl: IsCurrentUrlFn = (urlToCheck, currentUrl) => {
        const path = toPath(urlToCheck);

        return path !== null && isUnder(currentUrl ?? currentUrlPath, path);
    };

    // Le lien le plus spécifique l'emporte : sur /accounting/transactions, seul
    // « Journal des transactions » s'allume, pas « Vue d'ensemble » (/accounting).
    const findActiveHref = (hrefs: Href[]): string | null =>
        hrefs
            .map(toPath)
            .filter((path): path is string => path !== null && isUnder(currentUrlPath, path))
            .sort((a, b) => b.length - a.length)[0] ?? null;

    const whenCurrentUrl: WhenCurrentUrlFn = <TIfTrue, TIfFalse = null>(
        urlToCheck: Href,
        ifTrue: TIfTrue,
        ifFalse: TIfFalse = null as TIfFalse,
    ): TIfTrue | TIfFalse => (isCurrentUrl(urlToCheck) ? ifTrue : ifFalse);

    return {
        currentUrl: currentUrlPath,
        isCurrentUrl,
        isUnderUrl,
        findActiveHref,
        whenCurrentUrl,
    };
}
