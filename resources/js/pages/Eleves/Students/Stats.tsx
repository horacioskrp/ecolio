import { Head, router } from '@inertiajs/react';
import { BarChart3, CalendarClock, GraduationCap, Layers, UserCheck, Users } from 'lucide-react';
import {
    Bar, BarChart, CartesianGrid, Cell, LabelList, Pie, PieChart,
    ResponsiveContainer, Tooltip as RTooltip, XAxis, YAxis,
} from 'recharts';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';
import { useChartTheme } from '@/lib/chart-theme';

interface Bar { label: string; count: number }
interface YearRef { id: string; year: string }

interface Props {
    summary: { enrolled: number; active: number; inactive: number; classes: number };
    byGender: { male: number; female: number };
    byNationality: Bar[];
    byAge: Bar[];
    byClass: Bar[];
    parite: { female_pct: number; ips: number | null };
    ageMoyen: number | null;
    overAge: { evaluated: number; count: number; rate: number };
    academicYears: YearRef[];
    selectedYear: YearRef | null;
}

function Kpi({ label, value, sub, icon: Icon, tone }: Readonly<{ label: string; value: string | number; sub?: string; icon: React.ElementType; tone: string }>) {
    return (
        <div className="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-5 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{label}</p>
                    <p className={`text-2xl font-bold mt-1.5 ${tone}`}>{value}</p>
                    {sub && <p className="text-xs text-gray-400 mt-1">{sub}</p>}
                </div>
                <div className={`rounded-lg p-2 ${tone.replace('text-', 'bg-').replace('600', '50')} dark:bg-gray-700`}>
                    <Icon className={`w-6 h-6 ${tone}`} />
                </div>
            </div>
        </div>
    );
}

function Card({ title, icon, children }: Readonly<{ title: string; icon?: React.ReactNode; children: React.ReactNode }>) {
    return (
        <div className="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-5 shadow-sm">
            <div className="flex items-center gap-2 mb-4"><span className="text-gray-400">{icon}</span><h2 className="font-semibold text-sm text-gray-900 dark:text-white">{title}</h2></div>
            {children}
        </div>
    );
}

/** Libellés courts des tranches d'âge pour l'axe. */
const AGE_SHORT: Record<string, string> = {
    'Moins de 6 ans': '< 6',
    '6 à 10 ans': '6–10',
    '11 à 14 ans': '11–14',
    '15 à 18 ans': '15–18',
    'Plus de 18 ans': '> 18',
};

export default function Stats({ summary, byGender, byAge, byClass, parite, ageMoyen, overAge, academicYears, selectedYear }: Readonly<Props>) {
    const theme = useChartTheme();
    const [BLUE, ORANGE] = theme.series;

    // L'identité (sexe) est portée par la couleur de la catégorie, jamais par son rang.
    const genderData = [
        { name: 'Garçons', value: byGender.male, color: BLUE },
        { name: 'Filles', value: byGender.female, color: ORANGE },
    ].filter((d) => d.value > 0);
    const genderTotal = byGender.male + byGender.female;

    const ageData = byAge.map((b) => ({ ...b, short: AGE_SHORT[b.label] ?? b.label }));
    const classData = [...byClass].sort((a, b) => b.count - a.count);
    const classChartHeight = Math.max(220, classData.length * 22 + 24);

    const cards = [
        { label: `Inscrits ${selectedYear?.year ?? ''}`, value: summary.enrolled, sub: `${summary.classes} classes`, tone: 'text-blue-600', icon: GraduationCap },
        { label: 'Actifs', value: summary.active, sub: `${summary.inactive} inactifs`, tone: 'text-emerald-600', icon: UserCheck },
        { label: 'Âge moyen', value: ageMoyen != null ? `${ageMoyen} ans` : '—', sub: 'de la cohorte', tone: 'text-violet-600', icon: CalendarClock },
        { label: 'Sur-âge', value: `${overAge.rate}%`, sub: `${overAge.count} élèves en retard`, tone: 'text-amber-600', icon: Layers },
    ];

    const changeYear = (id: string) =>
        router.get(route('students.stats'), { academic_year_id: id }, { preserveScroll: true, replace: true });

    return (
        <AppLayout>
            <Head title="Statistiques élèves" />
            <div className="w-full space-y-6 p-1">

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <BarChart3 className="h-7 w-7 text-blue-600 shrink-0" /> Statistiques élèves
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">Vue démographique et effectifs des élèves inscrits pour l&apos;année sélectionnée.</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <label htmlFor="stats_year" className="text-sm font-medium text-gray-600 dark:text-gray-300">Année</label>
                        <select
                            id="stats_year"
                            value={selectedYear?.id ?? ''}
                            onChange={(e) => changeYear(e.target.value)}
                            className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-card text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            {academicYears.map((y) => (
                                <option key={y.id} value={y.id}>{y.year}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* KPIs */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {cards.map((c) => <Kpi key={c.label} {...c} />)}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Répartition par sexe */}
                    <Card title="Répartition par sexe" icon={<Users className="w-4 h-4" />}>
                        {genderTotal === 0 ? (
                            <p className="text-sm text-gray-400 text-center py-12">Aucune donnée</p>
                        ) : (
                            <div className="flex items-center gap-4">
                                <ResponsiveContainer width="50%" height={200}>
                                    <PieChart>
                                        <Pie data={genderData} dataKey="value" nameKey="name" innerRadius={52} outerRadius={80} paddingAngle={2} isAnimationActive={false}>
                                            {genderData.map((d) => <Cell key={d.name} fill={d.color} stroke={theme.surface} strokeWidth={2} />)}
                                        </Pie>
                                        <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="flex-1 space-y-3">
                                    <div>
                                        <p className="text-3xl font-bold text-gray-900 dark:text-white">{parite.female_pct}%</p>
                                        <p className="text-xs text-gray-400">de filles{parite.ips != null ? ` · IPS ${parite.ips}` : ''}</p>
                                    </div>
                                    <div className="space-y-1.5 text-sm">
                                        <span className="flex items-center gap-2 text-gray-600 dark:text-gray-300"><span className="w-3 h-3 rounded-full" style={{ background: BLUE }} /> Garçons : <strong>{byGender.male}</strong></span>
                                        <span className="flex items-center gap-2 text-gray-600 dark:text-gray-300"><span className="w-3 h-3 rounded-full" style={{ background: ORANGE }} /> Filles : <strong>{byGender.female}</strong></span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </Card>

                    {/* Répartition par tranche d'âge */}
                    <Card title="Répartition par tranche d'âge" icon={<CalendarClock className="w-4 h-4" />}>
                        {ageData.every((a) => a.count === 0) ? (
                            <p className="text-sm text-gray-400 text-center py-12">Aucune donnée</p>
                        ) : (
                            <ResponsiveContainer width="100%" height={200}>
                                <BarChart data={ageData} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} vertical={false} />
                                    <XAxis dataKey="short" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} interval={0} />
                                    <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={30} allowDecimals={false} />
                                    <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} labelFormatter={(l, p) => (p?.[0]?.payload?.label ?? l)} formatter={(v) => [`${v} élèves`, 'Effectif']} />
                                    <Bar dataKey="count" radius={[4, 4, 0, 0]} maxBarSize={54}>
                                        {ageData.map((d, i) => (
                                            <Cell key={d.label} fill={theme.sequential[Math.round((i / Math.max(ageData.length - 1, 1)) * (theme.sequential.length - 1))]} />
                                        ))}
                                        <LabelList dataKey="count" position="top" style={{ fontSize: 11, fill: theme.tick }} />
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </Card>
                </div>

                {/* Effectifs par classe */}
                <Card title={`Effectifs par classe${selectedYear ? ` — ${selectedYear.year}` : ''}`} icon={<BarChart3 className="w-4 h-4" />}>
                    {classData.length === 0 ? (
                        <p className="text-sm text-gray-400 text-center py-12">Aucune donnée</p>
                    ) : (
                        <ResponsiveContainer width="100%" height={classChartHeight}>
                            <BarChart layout="vertical" data={classData} margin={{ top: 0, right: 28, left: 8, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} horizontal={false} />
                                <XAxis type="number" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} allowDecimals={false} />
                                <YAxis type="category" dataKey="label" width={96} tick={{ fontSize: 11, fill: theme.axis }} axisLine={false} tickLine={false} />
                                <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} formatter={(v) => [`${v} élèves`, 'Effectif']} />
                                <Bar dataKey="count" fill={BLUE} radius={[0, 4, 4, 0]} maxBarSize={20}>
                                    <LabelList dataKey="count" position="right" style={{ fontSize: 11, fill: theme.tick }} />
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
