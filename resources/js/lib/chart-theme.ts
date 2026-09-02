import { useMemo } from 'react';
import { useAppearance } from '@/hooks/use-appearance';

/**
 * Thème des graphiques.
 *
 * Les couleurs de séries sont **validées** (bande de luminosité, plancher de
 * chroma, séparation en vision déficiente, plancher en vision normale, contraste
 * sur la surface) dans les deux modes. Ne pas les modifier sans revalider :
 * bleu et violet, par exemple, sont indistinguables en deutéranopie (ΔE 0.4).
 *
 * ⚠️ Trois teintes au maximum lorsque toutes les paires sont comparables entre
 * elles (camembert, nuage de points) : au-delà, aucun jeu ne tient dans les deux
 * modes. Pour davantage de catégories, utiliser des **barres étiquetées en une
 * seule teinte** — le travail est alors une magnitude, pas une identité.
 */
const SERIES = {
    light: ['#2a78d6', '#eb6834', '#1baf7a', '#4a3aa7'],
    dark: ['#3987e5', '#d95926', '#199e70', '#9085e9'],
} as const;

/** Rampe séquentielle (magnitude / donnée ordonnée) : une seule teinte, clair → foncé. */
const SEQUENTIAL = {
    light: ['#bfdbfe', '#60a5fa', '#2a78d6', '#1e3a8a'],
    dark: ['#1e3a8a', '#1d4ed8', '#3987e5', '#93c5fd'],
} as const;

/** Couleurs d'état — réservées, jamais réutilisées comme teinte de série. */
const STATUS = {
    light: { good: '#1baf7a', warning: '#eda100', critical: '#e34948' },
    dark: { good: '#199e70', warning: '#c98500', critical: '#e66767' },
} as const;

/** Habillage : grille et axes restent en retrait, jamais au premier plan. */
const CHROME = {
    light: { grid: '#e5e7eb', axis: '#6b7280', tick: '#9ca3af', surface: '#ffffff', border: '#e5e7eb' },
    dark: { grid: '#374151', axis: '#9ca3af', tick: '#9ca3af', surface: '#1f2937', border: '#374151' },
} as const;

export type ChartTheme = {
    mode: 'light' | 'dark';
    /** Teintes catégorielles, dans un ordre fixe — ne jamais cycler. */
    series: readonly string[];
    /** Première teinte : le défaut d'une série unique. */
    primary: string;
    sequential: readonly string[];
    status: { good: string; warning: string; critical: string };
    grid: string;
    axis: string;
    tick: string;
    surface: string;
    border: string;
    /** Style de l'infobulle, accordé au mode. */
    tooltip: { contentStyle: React.CSSProperties; itemStyle: React.CSSProperties };
};

export function useChartTheme(): ChartTheme {
    const { resolvedAppearance } = useAppearance();

    return useMemo<ChartTheme>(() => {
        const mode = resolvedAppearance;
        const chrome = CHROME[mode];

        return {
            mode,
            series: SERIES[mode],
            primary: SERIES[mode][0],
            sequential: SEQUENTIAL[mode],
            status: STATUS[mode],
            ...chrome,
            tooltip: {
                contentStyle: {
                    borderRadius: 12,
                    border: `1px solid ${chrome.border}`,
                    background: chrome.surface,
                    fontSize: 13,
                    color: mode === 'dark' ? '#f3f4f6' : '#111827',
                },
                itemStyle: { color: mode === 'dark' ? '#f3f4f6' : '#111827' },
            },
        };
    }, [resolvedAppearance]);
}

/**
 * Couleur d'une catégorie, dérivée de son **identité** et non de sa position.
 *
 * Indispensable quand la liste est filtrée ou triée : sans cela, retirer une
 * catégorie vide décale toutes les couleurs (les filles héritent du bleu des
 * garçons, « Très bien » perd son vert…).
 */
export function colorFor(key: string, keys: readonly string[], series: readonly string[]): string {
    const index = keys.indexOf(key);

    return series[index === -1 ? 0 : index % series.length];
}
