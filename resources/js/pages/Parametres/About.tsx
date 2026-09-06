import { Head } from '@inertiajs/react';
import {
    BadgeCheck, BookOpen, CalendarRange, ClipboardList, DatabaseBackup, ExternalLink,
    FileBadge, GraduationCap, Info, Scale, Server, Wallet, Globe, FileCode2, ScrollText, Users, Heart,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';

interface Props {
    app: { name: string; version: string; laravel: string; php: string; environment: string };
}

const GITHUB = 'https://github.com/wearedalibi/dalibi';
const SITE = 'https://dalibi.wearekarfi.dev';

/** Modules couverts par l'application (résumé). */
const MODULES: { icon: React.ElementType; title: string; desc: string }[] = [
    { icon: Users, title: 'Élèves & inscriptions', desc: 'Dossiers complets, inscriptions, passage de classe, bourses.' },
    { icon: FileBadge, title: 'Notes & bulletins', desc: 'Saisie, moyennes paramétrables, bulletins PDF, réclamations.' },
    { icon: ClipboardList, title: 'Présences', desc: 'Appel par séance, statistiques d’assiduité, permissions d’absence.' },
    { icon: GraduationCap, title: 'Examens officiels', desc: 'CEPD, BEPC, BAC : inscriptions, planning, résultats.' },
    { icon: Wallet, title: 'Finances & recouvrement', desc: 'Frais, factures, paiements (dont Mobile Money), reçus.' },
    { icon: BadgeCheck, title: 'Personnel & paie', desc: 'Fiches employés, cycles de paie, bulletins (CNSS, ITS).' },
    { icon: CalendarRange, title: 'Emploi du temps & calendrier', desc: 'Grilles par classe, calendrier de l’établissement.' },
    { icon: DatabaseBackup, title: 'Sauvegardes & statistiques', desc: 'Sauvegardes planifiées, tableaux de bord et exports.' },
];

const LINKS: { icon: React.ElementType; title: string; href: string; sub: string }[] = [
    { icon: BookOpen, title: 'Documentation', href: `${SITE}/documentation`, sub: 'Guide de A à Z' },
    { icon: Globe, title: 'Site web', href: SITE, sub: 'Présentation & fonctionnalités' },
    { icon: FileCode2, title: 'Code source', href: GITHUB, sub: 'Dépôt GitHub' },
    { icon: ScrollText, title: 'Journal des versions', href: `${GITHUB}/blob/main/CHANGELOG.md`, sub: 'Historique des évolutions' },
];

function Card({ children }: { children: React.ReactNode }) {
    return <div className="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-5 shadow-sm">{children}</div>;
}

export default function About({ app }: Readonly<Props>) {
    const brand = app.name ? app.name.charAt(0).toUpperCase() + app.name.slice(1) : 'Dalibi';

    return (
        <AppLayout>
            <Head title="À propos" />
            <div className="w-full max-w-5xl mx-auto space-y-6 p-1">

                {/* En-tête : logo officiel Dalibi (version blanche en mode sombre) + version */}
                <div className="rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-6 shadow-sm">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="sr-only">{brand}</h1>
                        <img src="/svgs/dalibi.svg" alt={brand} className="h-11 w-auto dark:hidden" />
                        <img src="/svgs/dalibi-blanc.svg" alt={brand} className="h-11 w-auto hidden dark:block" />
                        <a href={`${GITHUB}/releases/tag/v${app.version}`} target="_blank" rel="noopener noreferrer"
                            className="inline-flex items-center rounded-full border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                            v{app.version}
                        </a>
                    </div>
                    <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Système de gestion scolaire — de l’inscription de l’élève au bulletin, en passant par l’écolage et la paie.
                        Pensé pour les établissements togolais 🇹🇬.
                    </p>
                </div>

                {/* Liens utiles */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {LINKS.map((l) => {
                        const Icon = l.icon;
                        return (
                            <a key={l.title} href={l.href} target="_blank" rel="noopener noreferrer"
                                className="group rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-4 shadow-sm hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
                                <div className="flex items-center justify-between">
                                    <Icon className="w-5 h-5 text-blue-600" />
                                    <ExternalLink className="w-3.5 h-3.5 text-gray-300 group-hover:text-blue-500" />
                                </div>
                                <p className="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{l.title}</p>
                                <p className="text-xs text-gray-400">{l.sub}</p>
                            </a>
                        );
                    })}
                </div>

                {/* Ce que fait l'application */}
                <Card>
                    <div className="flex items-center gap-2 mb-4"><Info className="w-4 h-4 text-gray-400" /><h2 className="font-semibold text-sm text-gray-900 dark:text-white">Ce que couvre l’application</h2></div>
                    <div className="grid sm:grid-cols-2 gap-x-6 gap-y-4">
                        {MODULES.map((m) => {
                            const Icon = m.icon;
                            return (
                                <div key={m.title} className="flex gap-3">
                                    <div className="rounded-lg bg-blue-50 dark:bg-gray-700 p-2 h-fit"><Icon className="w-4 h-4 text-blue-600 dark:text-blue-300" /></div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white">{m.title}</p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">{m.desc}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </Card>

                <div className="grid lg:grid-cols-2 gap-4">
                    {/* Éditeur & licence */}
                    <Card>
                        <div className="flex items-center gap-2 mb-4"><Scale className="w-4 h-4 text-gray-400" /><h2 className="font-semibold text-sm text-gray-900 dark:text-white">Éditeur & licence</h2></div>
                        <dl className="space-y-3 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-gray-500 dark:text-gray-400">Édité par</dt>
                                <dd className="font-medium text-gray-900 dark:text-white text-right">Kudayah Sassou Horacio Herve</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-gray-500 dark:text-gray-400">Licence</dt>
                                <dd className="font-medium text-gray-900 dark:text-white text-right">GNU GPL v3 — logiciel libre</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-gray-500 dark:text-gray-400">Droits</dt>
                                <dd className="font-medium text-gray-900 dark:text-white text-right">© {new Date().getFullYear()} {brand}</dd>
                            </div>
                        </dl>
                        <p className="mt-4 flex items-center gap-1.5 text-xs text-gray-400">
                            <Heart className="w-3.5 h-3.5 text-rose-400" /> Vous pouvez utiliser, étudier, modifier et redistribuer ce logiciel selon les termes de la GPL v3.
                        </p>
                    </Card>

                    {/* Informations techniques */}
                    <Card>
                        <div className="flex items-center gap-2 mb-4"><Server className="w-4 h-4 text-gray-400" /><h2 className="font-semibold text-sm text-gray-900 dark:text-white">Informations techniques</h2></div>
                        <dl className="space-y-3 text-sm">
                            <div className="flex justify-between gap-4"><dt className="text-gray-500 dark:text-gray-400">Version de l’application</dt><dd className="font-mono font-medium text-gray-900 dark:text-white">v{app.version}</dd></div>
                            <div className="flex justify-between gap-4"><dt className="text-gray-500 dark:text-gray-400">Laravel</dt><dd className="font-mono font-medium text-gray-900 dark:text-white">{app.laravel}</dd></div>
                            <div className="flex justify-between gap-4"><dt className="text-gray-500 dark:text-gray-400">PHP</dt><dd className="font-mono font-medium text-gray-900 dark:text-white">{app.php}</dd></div>
                            <div className="flex justify-between gap-4"><dt className="text-gray-500 dark:text-gray-400">Environnement</dt><dd className="font-mono font-medium text-gray-900 dark:text-white">{app.environment}</dd></div>
                        </dl>
                    </Card>
                </div>

                {/* Support */}
                <Card>
                    <div className="flex items-center gap-2 mb-2"><Info className="w-4 h-4 text-gray-400" /><h2 className="font-semibold text-sm text-gray-900 dark:text-white">Besoin d’aide ?</h2></div>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Consultez d’abord la <a href={`${SITE}/documentation`} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">documentation</a>.
                        Pour signaler un problème ou proposer une amélioration, ouvrez un ticket sur le <a href={`${GITHUB}/issues`} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">dépôt GitHub</a>.
                    </p>
                </Card>
            </div>
        </AppLayout>
    );
}
