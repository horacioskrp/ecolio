import { BookOpen, GraduationCap, LayoutGrid, Layers, Settings, Tag, Users } from 'lucide-react';
import { route } from '@/helpers/route';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Paramètres',
        href: '#',
        icon: Settings,
        items: [
            {
                title: 'Écoles',
                href: route('schools.index'),
                icon: BookOpen,
            },
            {
                title: 'Classes',
                href: route('classrooms.index'),
                icon: Layers,
            },
            {
                title: 'Types de classes',
                href: route('classroom-types.index'),
                icon: Tag,
            },
        ],
    },
    {
        title: 'Gestion',
        href: '#',
        icon: Users,
        items: [
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