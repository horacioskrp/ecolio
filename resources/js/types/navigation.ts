import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: string;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: NavItem[];
    /** Permission requise pour afficher l'entrée (absente = toujours visible). */
    permission?: string;
    /**
     * Chemins supplémentaires qui gardent l'entrée active (avec leurs
     * sous-pages). Utile quand une section regroupe plusieurs URL sans préfixe
     * commun exclusif — ex. « Mon compte » : profil, mot de passe, 2FA, apparence.
     */
    match?: string[];
};
