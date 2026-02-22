import { BookOpen, GraduationCap, LayoutGrid, Settings, Users } from 'lucide-react';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Gestion',
        href: '#',
        icon: Settings,
        items: [
            {
                title: 'Écoles',
                href: '#',
                icon: BookOpen,
            },
            {
                title: 'Utilisateurs',
                href: '#',
                icon: Users,
            },
            {
                title: 'Élèves',
                href: '#',
                icon: GraduationCap,
            },
        ],
    },
];