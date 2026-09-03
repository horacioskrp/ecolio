import { Head, router } from '@inertiajs/react';
import { BarChart3, Download, FileSpreadsheet, GraduationCap, Layers, MapPin, PieChart as PieIcon, School, TrendingUp, UserCheck, Users, Wallet } from 'lucide-react';
import { useState } from 'react';
import {
    Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, LabelList, Legend, Line, LineChart, Pie, PieChart,
    ResponsiveContainer, Tooltip as RTooltip, Treemap, XAxis, YAxis,
} from 'recharts';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useMoney } from '@/helpers/money';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';
import { useChartTheme } from '@/lib/chart-theme';
import type { BreadcrumbItem } from '@/types';

/* ---------------- Types ---------------- */
interface YearOption { id: string; year: string; active?: boolean }
interface ClassOption { id: string; name: string }
interface Filters { academic_year_id: string | null; class_id: string | null; gender: string | null }

interface Enrollment {
    total: number;
    gender: { male: number; female: number; other: number };
    ips: number | null;
    part_filles: number;
    by_class: { name: string; male: number; female: number; total: number }[];
    academic_status: Record<string, number>;
    rates: { promotion: number; redoublement: number; abandon: number };
    age_distribution: { age: number; total: number }[];
    age_moyen: number | null;
    by_city: { city: string; total: number }[];
    over_age: { evaluated: number; count: number; rate: number; threshold: number };
}
interface Finance {
    billed: number; collected: number; remaining: number; recovery_rate: number;
    total_invoices: number; paid_count: number; partial_count: number; unpaid_count: number;
    by_class: { name: string; billed: number; collected: number; rate: number }[];
    by_method: { method: string; total: number; count: number }[];
    monthly: { month: string; total: number }[];
}
interface Success {
    bulletins: number; moyenne_generale: number | null; pass_rate: number;
    mentions: { passable: number; assez_bien: number; bien: number; tres_bien: number };
    exams: { name: string; type: string; center: string | null; registered: number; admitted: number; failed: number; absent: number; admission_rate: number; presentation_rate: number }[];
    exams_summary: { registered: number; admitted: number; admission_rate: number };
}
interface Resources {
    total_students: number; total_teachers: number; rem: number | null;
    class_count: number; avg_class_size: number; threshold: number;
    overcrowded: { name: string; total: number }[];
    class_sizes: { name: string; total: number }[];
}
interface Attendance {
    total: number; present: number; absent: number; late: number; excused: number;
    presence_rate: number; absence_rate: number; late_rate: number;
    chronic_absentees: number; chronic_threshold: number;
    by_period: { name: string; present: number; absent: number; late: number }[];
    by_class: { name: string; absence_rate: number }[];
}
interface TrendPoint { year: string; effectif: number; part_filles: number; redoublement: number; abandon: number; recouvrement: number; reussite: number; admission: number }
interface Trends { series: TrendPoint[] }
interface Geography {
    total: number; localized: number; coverage: number;
    by_region: { name: string; total: number }[];
    by_prefecture: { name: string; region: string; total: number }[];
}
interface Props {
    filters: Filters;
    academicYears: YearOption[];
    classes: ClassOption[];
    enrollment: Enrollment;
    finance: Finance;
    success: Success;
    resources: Resources;
    attendance: Attendance;
    trends: Trends;
    geography: Geography;
}

/* ---------------- Helpers ---------------- */
const methodLabel: Record<string, string> = { CASH: 'Espèces', MOBILE_MONEY: 'Mobile Money', BANK_TRANSFER: 'Virement', CHEQUE: 'Chèque' };


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Statistiques', href: '/statistiques' }];

function Kpi({ label, value, sub, icon: Icon, tone }: { label: string; value: string | number; sub?: string; icon: React.ElementType; tone: string }) {
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

function Card({ title, icon, children }: { title: string; icon?: React.ReactNode; children: React.ReactNode }) {
    return (
        <div className="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-5 shadow-sm">
            <div className="flex items-center gap-2 mb-4"><span className="text-gray-400">{icon}</span><h2 className="font-semibold text-sm text-gray-900 dark:text-white">{title}</h2></div>
            {children}
        </div>
    );
}

/**
 * Nœud du treemap géographique. La région (depth 1) est un cadre étiqueté ; la
 * préfecture (feuille) est remplie par une rampe séquentielle selon son effectif
 * (magnitude → teinte, jamais une couleur par région, indistinguable au-delà de
 * trois ou quatre catégories). L'identité passe par les libellés.
 */
function TreemapNode(props: {
    x?: number; y?: number; width?: number; height?: number; depth?: number;
    name?: string; value?: number; sequential?: readonly string[]; surface?: string;
    axis?: string; tick?: string; maxLeaf?: number;
}) {
    const { x = 0, y = 0, width = 0, height = 0, depth = 0, name = '', value = 0 } = props;
    const seq = props.sequential ?? ['#bfdbfe', '#60a5fa', '#2a78d6', '#1e3a8a'];
    const surface = props.surface ?? '#fff';

    // Racine : ne rien peindre, sinon un grand rectangle recouvrirait tout le treemap.
    if (depth === 0) {
        return <g />;
    }

    if (depth === 1) {
        return (
            <g>
                <rect x={x} y={y} width={width} height={height} fill="none" stroke={surface} strokeWidth={3} />
                {width > 64 && height > 18 && (
                    <text x={x + 6} y={y + 15} fontSize={12} fontWeight={700} fill={props.axis ?? '#374151'}>{name}</text>
                )}
            </g>
        );
    }

    const ratio = props.maxLeaf && props.maxLeaf > 0 ? value / props.maxLeaf : 0;
    const idx = Math.min(seq.length - 1, Math.max(0, Math.round(ratio * (seq.length - 1))));
    const onDark = idx >= seq.length - 2;
    const label = name.length > 15 ? `${name.slice(0, 14)}…` : name;

    return (
        <g>
            <rect x={x} y={y} width={width} height={height} fill={seq[idx]} stroke={surface} strokeWidth={1.5} rx={2} />
            {width > 46 && height > 26 && (
                <>
                    <text x={x + 5} y={y + 15} fontSize={10.5} fontWeight={600} fill={onDark ? '#ffffff' : (props.axis ?? '#374151')}>{label}</text>
                    <text x={x + 5} y={y + 28} fontSize={10} fill={onDark ? 'rgba(255,255,255,0.85)' : (props.tick ?? '#9ca3af')}>{value}</text>
                </>
            )}
        </g>
    );
}

/* ---------------- Page ---------------- */
type Tab = 'effectifs' | 'finances' | 'reussite' | 'encadrement' | 'assiduite' | 'comparaisons' | 'geographie';

export default function StatisticsIndex({ filters, academicYears, classes, enrollment, finance, success, resources, attendance, trends, geography }: Readonly<Props>) {
    const theme = useChartTheme();
    const [BLUE, ORANGE, GREEN, VIOLET] = theme.series;

    // La couleur est portée par la catégorie elle-même : filtrer une catégorie
    // vide ne doit jamais repeindre les autres.
    const genderData = [
        { name: 'Garçons', value: enrollment.gender.male, color: BLUE },
        { name: 'Filles', value: enrollment.gender.female, color: ORANGE },
        { name: 'Autre', value: enrollment.gender.other, color: GREEN },
    ].filter((d) => d.value > 0);

    // Donnée ordonnée (Passable → Très bien) : rampe séquentielle, pas des teintes sans rapport.
    const mentionData = [
        { name: 'Passable', value: success.mentions.passable },
        { name: 'Assez bien', value: success.mentions.assez_bien },
        { name: 'Bien', value: success.mentions.bien },
        { name: 'Très bien', value: success.mentions.tres_bien },
    ].map((d, i) => ({ ...d, color: theme.sequential[i] })).filter((d) => d.value > 0);
    const money = useMoney();
    const [tab, setTab] = useState<Tab>('effectifs');

    const setFilter = (key: keyof Filters, value: string) => {
        router.get(route('statistics.index'), {
            ...filters,
            [key]: value === 'all' ? '' : value,
        }, { preserveScroll: true, preserveState: false, replace: true });
    };

    const exportUrl = (section: Tab, format: 'pdf' | 'xlsx') => {
        const q = new URLSearchParams();
        if (filters.academic_year_id) q.set('academic_year_id', filters.academic_year_id);
        if (filters.class_id) q.set('class_id', filters.class_id);
        if (filters.gender) q.set('gender', filters.gender);
        return `/statistiques/${section}/export/${format}?${q.toString()}`;
    };

    const tabs: { key: Tab; label: string; icon: React.ElementType }[] = [
        { key: 'effectifs', label: 'Effectifs & parité', icon: Users },
        { key: 'finances', label: 'Finances & recouvrement', icon: Wallet },
        { key: 'reussite', label: 'Réussite & examens', icon: GraduationCap },
        { key: 'encadrement', label: 'Encadrement', icon: School },
        { key: 'assiduite', label: 'Assiduité', icon: UserCheck },
        { key: 'comparaisons', label: 'Comparaisons', icon: TrendingUp },
        { key: 'geographie', label: 'Géographie', icon: MapPin },
    ];

    /* ---- Comparaisons : l'année active est en cours, ses taux de fin d'année
       (redoublement, abandon, admission) ne sont pas encore décidés. On les met à
       null pour que les courbes s'arrêtent au lieu de plonger à zéro. ---- */
    const activeYearLabel = academicYears.find((y) => y.active)?.year;
    type TrendKey = 'effectif' | 'part_filles' | 'redoublement' | 'abandon' | 'recouvrement' | 'reussite' | 'admission';
    const endOfYearKeys: TrendKey[] = ['redoublement', 'abandon', 'admission'];
    const trendSeries = trends.series.map((p) => {
        const inProgress = p.year === activeYearLabel;
        return {
            ...p,
            redoublement: inProgress ? null : p.redoublement,
            abandon: inProgress ? null : p.abandon,
            admission: inProgress ? null : p.admission,
        } as Record<string, number | string | null>;
    });
    // Dernier point renseigné vs le précédent, par métrique (les taux de fin
    // d'année sautent l'année en cours ; les autres non).
    const metricDelta = (key: TrendKey) => {
        const pts = trendSeries.filter((p) => p[key] != null);
        const cur = pts[pts.length - 1];
        const prv = pts[pts.length - 2];
        if (!cur) return null;
        const value = cur[key] as number;
        const delta = prv ? Math.round((value - (prv[key] as number)) * 10) / 10 : null;
        return { year: cur.year as string, value, delta };
    };
    const isPct = (k: TrendKey) => k !== 'effectif';
    const trendKpis: { key: TrendKey; label: string; higherIsBetter: boolean }[] = [
        { key: 'effectif', label: 'Effectif', higherIsBetter: true },
        { key: 'reussite', label: 'Réussite', higherIsBetter: true },
        { key: 'recouvrement', label: 'Recouvrement', higherIsBetter: true },
        { key: 'abandon', label: 'Abandon', higherIsBetter: false },
    ];

    /* ---- Géographie : concentration et hiérarchie région → préfecture. ---- */
    const grandLomeNames = ['Golfe', 'Agoè-Nyivé'];
    const grandLomeTotal = geography.by_prefecture.filter((p) => grandLomeNames.includes(p.name)).reduce((s, p) => s + p.total, 0);
    const grandLomeShare = geography.localized > 0 ? Math.round((grandLomeTotal / geography.localized) * 1000) / 10 : 0;
    const maxLeaf = geography.by_prefecture.reduce((m, p) => Math.max(m, p.total), 0);
    const treemapData = geography.by_region
        .map((r) => ({
            name: r.name,
            children: geography.by_prefecture.filter((p) => p.region === r.name).map((p) => ({ name: p.name, size: p.total })),
        }))
        .filter((r) => r.children.length > 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Statistiques" />
            <div className="space-y-6 p-1">
                {/* En-tête + filtres */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <BarChart3 className="h-7 w-7 text-blue-600 shrink-0" /> Statistiques
                        </h1>
                        <p className="text-sm text-gray-500 mt-0.5">Indicateurs de l'établissement — effectifs, finances et réussite.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Select value={filters.academic_year_id ?? ''} onValueChange={(v) => setFilter('academic_year_id', v)}>
                            <SelectTrigger className="w-40 bg-white dark:bg-card"><SelectValue placeholder="Année" /></SelectTrigger>
                            <SelectContent>{academicYears.map((y) => <SelectItem key={y.id} value={y.id}>{y.year}{y.active ? ' · active' : ''}</SelectItem>)}</SelectContent>
                        </Select>
                        <Select value={filters.class_id ?? 'all'} onValueChange={(v) => setFilter('class_id', v)}>
                            <SelectTrigger className="w-40 bg-white dark:bg-card"><SelectValue placeholder="Classe" /></SelectTrigger>
                            <SelectContent><SelectItem value="all">Toutes les classes</SelectItem>{classes.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
                        </Select>
                        <Select value={filters.gender ?? 'all'} onValueChange={(v) => setFilter('gender', v)}>
                            <SelectTrigger className="w-32 bg-white dark:bg-card"><SelectValue placeholder="Sexe" /></SelectTrigger>
                            <SelectContent><SelectItem value="all">Tous</SelectItem><SelectItem value="male">Garçons</SelectItem><SelectItem value="female">Filles</SelectItem></SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Onglets + export */}
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700">
                    <div className="flex gap-1">
                        {tabs.map((t) => (
                            <button key={t.key} onClick={() => setTab(t.key)}
                                className={`flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition ${tab === t.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800'}`}>
                                <t.icon className="w-4 h-4" /> {t.label}
                            </button>
                        ))}
                    </div>
                    <div className="flex gap-2 pb-2">
                        <a href={exportUrl(tab, 'pdf')}><Button variant="outline" size="sm" className="gap-1.5 text-xs"><Download className="w-3.5 h-3.5" /> PDF</Button></a>
                        <a href={exportUrl(tab, 'xlsx')}><Button variant="outline" size="sm" className="gap-1.5 text-xs"><FileSpreadsheet className="w-3.5 h-3.5" /> Excel</Button></a>
                    </div>
                </div>

                {/* ---- Effectifs & parité ---- */}
                {tab === 'effectifs' && (
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <Kpi label="Effectif total" value={enrollment.total} sub={`${enrollment.gender.male} G · ${enrollment.gender.female} F`} icon={Users} tone="text-blue-600" />
                            <Kpi label="Indice de parité (IPS)" value={enrollment.ips ?? '—'} sub={`${enrollment.part_filles}% de filles`} icon={PieIcon} tone="text-pink-600" />
                            <Kpi label="Taux de redoublement" value={`${enrollment.rates.redoublement}%`} sub={`Promotion ${enrollment.rates.promotion}%`} icon={TrendingUp} tone="text-orange-600" />
                            <Kpi label="Taux d'abandon" value={`${enrollment.rates.abandon}%`} sub={`Âge moyen ${enrollment.age_moyen ?? '—'}`} icon={GraduationCap} tone="text-red-600" />
                        </div>
                        <div className="grid lg:grid-cols-3 gap-5">
                            <div className="lg:col-span-2"><Card title="Effectifs par classe (garçons / filles)" icon={<Users className="w-4 h-4" />}>
                                <ResponsiveContainer width="100%" height={260}>
                                    <BarChart data={enrollment.by_class}>
                                        <XAxis dataKey="name" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} interval={0} angle={-15} textAnchor="end" height={50} />
                                        <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={30} allowDecimals={false} />
                                        <RTooltip />
                                        <Bar dataKey="male" name="Garçons" stackId="a" fill={BLUE} radius={[0, 0, 0, 0]} />
                                        <Bar dataKey="female" name="Filles" stackId="a" fill={ORANGE} radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </Card></div>
                            <Card title="Répartition par sexe" icon={<PieIcon className="w-4 h-4" />}>
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart>
                                        <Pie isAnimationActive={false} dataKey="value" nameKey="name" innerRadius={55} outerRadius={90} paddingAngle={2}
                                            data={genderData}>
                                            {genderData.map((d) => <Cell key={d.name} fill={d.color} />)}
                                        </Pie>
                                        <Legend wrapperStyle={{ fontSize: 12 }} />
                                        <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </Card>
                        </div>
                        <div className="grid lg:grid-cols-2 gap-5">
                            <Card title="Pyramide des âges" icon={<BarChart3 className="w-4 h-4" />}>
                                <ResponsiveContainer width="100%" height={200}>
                                    <BarChart data={enrollment.age_distribution}>
                                        <XAxis dataKey="age" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} />
                                        <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={30} allowDecimals={false} />
                                        <RTooltip />
                                        <Bar dataKey="total" name="Élèves" fill={VIOLET} radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                                <div className="mt-3 flex items-center justify-between rounded-lg bg-amber-50 dark:bg-amber-900/20 px-3 py-2 text-sm">
                                    <span className="text-amber-700 dark:text-amber-300">Taux de sur-âge (≥ +{enrollment.over_age.threshold} ans)</span>
                                    <span className="font-bold text-amber-700 dark:text-amber-300">
                                        {enrollment.over_age.evaluated === 0 ? '—' : `${enrollment.over_age.rate}%`}
                                        {enrollment.over_age.evaluated > 0 && <span className="text-xs font-normal ml-1">({enrollment.over_age.count}/{enrollment.over_age.evaluated})</span>}
                                    </span>
                                </div>
                                {enrollment.over_age.evaluated === 0 && <p className="text-xs text-gray-400 mt-1.5">Renseignez l'âge attendu sur les classes pour activer ce calcul.</p>}
                            </Card>
                            <Card title="Origine géographique (top villes)" icon={<Users className="w-4 h-4" />}>
                                {enrollment.by_city.length === 0 ? <p className="text-sm text-gray-400 text-center py-8">Non renseigné</p> : (
                                    <div className="space-y-2">
                                        {enrollment.by_city.map((c) => (
                                            <div key={c.city} className="flex items-center justify-between text-sm">
                                                <span className="text-gray-700 dark:text-gray-300">{c.city}</span>
                                                <span className="font-semibold">{c.total}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </Card>
                        </div>
                    </div>
                )}

                {/* ---- Finances ---- */}
                {tab === 'finances' && (
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <Kpi label="Total facturé" value={money(finance.billed)} sub={`${finance.total_invoices} factures`} icon={Wallet} tone="text-blue-600" />
                            <Kpi label="Encaissé" value={money(finance.collected)} icon={TrendingUp} tone="text-green-600" />
                            <Kpi label="Taux de recouvrement" value={`${finance.recovery_rate}%`} sub={money(finance.remaining) + ' restant'} icon={PieIcon} tone="text-orange-600" />
                            <Kpi label="Impayés" value={finance.unpaid_count} sub={`${finance.partial_count} partiels`} icon={GraduationCap} tone="text-red-600" />
                        </div>
                        <div className="grid lg:grid-cols-3 gap-5">
                            <div className="lg:col-span-2"><Card title="Encaissements mensuels" icon={<TrendingUp className="w-4 h-4" />}>
                                <ResponsiveContainer width="100%" height={240}>
                                    <AreaChart data={finance.monthly}>
                                        <defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor={BLUE} stopOpacity={0.35} /><stop offset="95%" stopColor={BLUE} stopOpacity={0} /></linearGradient></defs>
                                        <XAxis dataKey="month" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} />
                                        <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={48} tickFormatter={(v: number) => v >= 1000 ? `${Math.round(v / 1000)}k` : `${v}`} />
                                        <RTooltip formatter={(v) => money(Number(v))} />
                                        <Area type="monotone" dataKey="total" stroke={BLUE} strokeWidth={2} fill="url(#g)" />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </Card></div>
                            <Card title="Modes de paiement" icon={<PieIcon className="w-4 h-4" />}>
                                {/* Comparer des montants par catégorie est une magnitude, pas une identité :
                                    des barres étiquetées en une seule teinte se lisent mieux qu'un camembert,
                                    et l'identité ne repose plus sur la couleur. */}
                                <ResponsiveContainer width="100%" height={240}>
                                    <BarChart layout="vertical" data={finance.by_method.map((m) => ({ ...m, method: methodLabel[m.method] ?? m.method }))}
                                        margin={{ top: 4, right: 16, left: 8, bottom: 4 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} horizontal={false} />
                                        <XAxis type="number" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false}
                                            tickFormatter={(v) => (Number(v) >= 1000 ? `${Math.round(Number(v) / 1000)}k` : String(v))} />
                                        <YAxis type="category" dataKey="method" width={96} tick={{ fontSize: 12, fill: theme.axis }} axisLine={false} tickLine={false} />
                                        <RTooltip formatter={(v) => money(Number(v))} contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} />
                                        <Bar dataKey="total" fill={BLUE} radius={[0, 4, 4, 0]} maxBarSize={26} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </Card>
                        </div>
                        <Card title="Recouvrement par classe" icon={<Wallet className="w-4 h-4" />}>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead><tr className="text-left text-xs text-gray-500 uppercase border-b border-gray-100 dark:border-gray-700">
                                        <th className="py-2">Classe</th><th className="py-2 text-right">Facturé</th><th className="py-2 text-right">Encaissé</th><th className="py-2 text-right">Taux</th></tr></thead>
                                    <tbody>{finance.by_class.map((c) => (
                                        <tr key={c.name} className="border-b border-gray-50 dark:border-gray-700/50">
                                            <td className="py-2">{c.name}</td><td className="py-2 text-right">{money(c.billed)}</td>
                                            <td className="py-2 text-right text-green-600">{money(c.collected)}</td>
                                            <td className="py-2 text-right font-semibold">{c.rate}%</td>
                                        </tr>))}</tbody>
                                </table>
                            </div>
                        </Card>
                    </div>
                )}

                {/* ---- Réussite ---- */}
                {tab === 'reussite' && (
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <Kpi label="Bulletins validés" value={success.bulletins} icon={GraduationCap} tone="text-blue-600" />
                            <Kpi label="Moyenne générale" value={success.moyenne_generale ?? '—'} icon={BarChart3} tone="text-violet-600" />
                            <Kpi label="Réussite interne (≥10)" value={`${success.pass_rate}%`} icon={TrendingUp} tone="text-green-600" />
                            <Kpi label="Admission examens off." value={`${success.exams_summary.admission_rate}%`} sub={`${success.exams_summary.admitted}/${success.exams_summary.registered}`} icon={PieIcon} tone="text-orange-600" />
                        </div>
                        <div className="grid lg:grid-cols-3 gap-5">
                            <Card title="Répartition des mentions" icon={<PieIcon className="w-4 h-4" />}>
                                <ResponsiveContainer width="100%" height={240}>
                                    <PieChart>
                                        <Pie isAnimationActive={false} dataKey="value" nameKey="name" innerRadius={50} outerRadius={88} paddingAngle={2} data={mentionData}>
                                            {mentionData.map((d) => <Cell key={d.name} fill={d.color} />)}
                                        </Pie>
                                        <Legend wrapperStyle={{ fontSize: 12 }} />
                                        <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </Card>
                            <div className="lg:col-span-2"><Card title="Examens officiels (CEPD / BEPC / BAC)" icon={<GraduationCap className="w-4 h-4" />}>
                                {success.exams.length === 0 ? <p className="text-sm text-gray-400 text-center py-8">Aucun examen officiel pour cette année</p> : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead><tr className="text-left text-xs text-gray-500 uppercase border-b border-gray-100 dark:border-gray-700">
                                                <th className="py-2">Examen</th><th className="py-2 text-right">Inscrits</th><th className="py-2 text-right">Admis</th><th className="py-2 text-right">Admission</th></tr></thead>
                                            <tbody>{success.exams.map((e, i) => (
                                                <tr key={i} className="border-b border-gray-50 dark:border-gray-700/50">
                                                    <td className="py-2"><span className="uppercase text-blue-600 font-bold mr-1.5">{e.type}</span>{e.name}</td>
                                                    <td className="py-2 text-right">{e.registered}</td><td className="py-2 text-right text-green-600">{e.admitted}</td>
                                                    <td className="py-2 text-right font-semibold">{e.admission_rate}%</td>
                                                </tr>))}</tbody>
                                        </table>
                                    </div>
                                )}
                            </Card></div>
                        </div>
                    </div>
                )}

                {/* ---- Encadrement ---- */}
                {tab === 'encadrement' && (
                    <div className="space-y-6">
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <Kpi label="Ratio élèves / enseignant" value={resources.rem ?? '—'} sub={`${resources.total_teachers} enseignants`} icon={GraduationCap} tone="text-blue-600" />
                            <Kpi label="Effectif total" value={resources.total_students} icon={Users} tone="text-violet-600" />
                            <Kpi label="Taille moyenne / classe" value={resources.avg_class_size} sub={`${resources.class_count} classes`} icon={Layers} tone="text-green-600" />
                            <Kpi label="Classes pléthoriques" value={resources.overcrowded.length} sub={`> ${resources.threshold} élèves`} icon={School} tone="text-orange-600" />
                        </div>
                        <Card title="Effectif par classe (rouge = pléthorique)" icon={<Layers className="w-4 h-4" />}>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={resources.class_sizes}>
                                    <XAxis dataKey="name" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} interval={0} angle={-15} textAnchor="end" height={50} />
                                    <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={30} allowDecimals={false} />
                                    <RTooltip />
                                    <Bar dataKey="total" name="Élèves" radius={[4, 4, 0, 0]}>
                                        {resources.class_sizes.map((c, i) => <Cell key={i} fill={c.total > resources.threshold ? ORANGE : BLUE} />)}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </Card>
                        {resources.overcrowded.length > 0 && (
                            <Card title="Classes pléthoriques à surveiller" icon={<School className="w-4 h-4" />}>
                                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    {resources.overcrowded.map((c) => (
                                        <div key={c.name} className="flex items-center justify-between rounded-lg bg-orange-50 dark:bg-orange-900/20 px-4 py-2.5">
                                            <span className="text-sm font-medium text-gray-800 dark:text-gray-200">{c.name}</span>
                                            <span className="text-sm font-bold text-orange-600">{c.total} élèves</span>
                                        </div>
                                    ))}
                                </div>
                            </Card>
                        )}
                    </div>
                )}

                {/* ---- Assiduité ---- */}
                {tab === 'assiduite' && (
                    <div className="space-y-6">
                        {attendance.total === 0 ? (
                            <div className="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-12 text-center text-gray-400">
                                Aucune donnée de présence pour cette période.
                            </div>
                        ) : (
                            <>
                                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                    <Kpi label="Taux de présence" value={`${attendance.presence_rate}%`} sub={`${attendance.present} présences`} icon={UserCheck} tone="text-green-600" />
                                    <Kpi label="Taux d'absence" value={`${attendance.absence_rate}%`} sub={`${attendance.absent} absences`} icon={TrendingUp} tone="text-red-600" />
                                    <Kpi label="Taux de retard" value={`${attendance.late_rate}%`} sub={`${attendance.late} retards`} icon={BarChart3} tone="text-orange-600" />
                                    <Kpi label="Absentéisme chronique" value={attendance.chronic_absentees} sub={`> ${attendance.chronic_threshold} absences`} icon={Users} tone="text-violet-600" />
                                </div>
                                <div className="grid lg:grid-cols-2 gap-5">
                                    <Card title="Présences par période" icon={<UserCheck className="w-4 h-4" />}>
                                        <ResponsiveContainer width="100%" height={260}>
                                            <BarChart data={attendance.by_period}>
                                                <XAxis dataKey="name" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} />
                                                <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={30} allowDecimals={false} />
                                                <RTooltip />
                                                <Bar dataKey="present" name="Présents" stackId="a" fill={GREEN} />
                                                <Bar dataKey="absent" name="Absents" stackId="a" fill="#ef4444" />
                                                <Bar dataKey="late" name="Retards" stackId="a" fill={ORANGE} radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </Card>
                                    <Card title="Taux d'absence par classe" icon={<BarChart3 className="w-4 h-4" />}>
                                        {attendance.by_class.length === 0 ? <p className="text-sm text-gray-400 text-center py-8">—</p> : (
                                            <div className="space-y-2.5">
                                                {attendance.by_class.slice(0, 8).map((c) => (
                                                    <div key={c.name}>
                                                        <div className="flex items-center justify-between text-sm mb-1">
                                                            <span className="text-gray-700 dark:text-gray-300">{c.name}</span>
                                                            <span className="font-semibold">{c.absence_rate}%</span>
                                                        </div>
                                                        <div className="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                            <div className={`h-full rounded-full ${c.absence_rate >= 20 ? 'bg-red-400' : c.absence_rate >= 10 ? 'bg-orange-400' : 'bg-green-500'}`} style={{ width: `${Math.min(100, c.absence_rate)}%` }} />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </Card>
                                </div>
                            </>
                        )}
                    </div>
                )}

                {/* ---- Comparaisons pluriannuelles ---- */}
                {tab === 'comparaisons' && (
                    <div className="space-y-6">
                        {trends.series.length < 2 ? (
                            <div className="rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm px-4 py-3">
                                Les tendances se précisent avec au moins deux années académiques renseignées.
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                {trendKpis.map(({ key, label, higherIsBetter }) => {
                                    const d = metricDelta(key);
                                    if (!d) return null;
                                    const up = d.delta != null && d.delta > 0;
                                    const good = d.delta == null ? null : up === higherIsBetter;
                                    const deltaColor = good == null ? 'text-gray-400' : good ? 'text-emerald-600' : 'text-red-500';
                                    return (
                                        <div key={key} className="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-4 shadow-sm">
                                            <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">{label}</p>
                                            <p className="text-2xl font-bold mt-1 text-gray-900 dark:text-white">
                                                {isPct(key) ? `${d.value}%` : d.value}
                                            </p>
                                            <p className="text-xs mt-1 flex items-center gap-1">
                                                {d.delta == null ? (
                                                    <span className="text-gray-400">—</span>
                                                ) : (
                                                    <span className={`font-semibold ${deltaColor}`}>
                                                        {up ? '↑' : d.delta < 0 ? '↓' : '='} {d.delta > 0 ? '+' : ''}{d.delta}{isPct(key) ? ' pts' : ''}
                                                    </span>
                                                )}
                                                <span className="text-gray-400">· {d.year}</span>
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        <div className="grid lg:grid-cols-2 gap-5">
                            <Card title="Évolution de l'effectif" icon={<Users className="w-4 h-4" />}>
                                <ResponsiveContainer width="100%" height={260}>
                                    <AreaChart data={trends.series} margin={{ top: 8, right: 12, left: 0, bottom: 0 }}>
                                        <defs>
                                            <linearGradient id="effGrad" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stopColor={BLUE} stopOpacity={0.28} />
                                                <stop offset="100%" stopColor={BLUE} stopOpacity={0.02} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} vertical={false} />
                                        <XAxis dataKey="year" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} />
                                        <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={36} allowDecimals={false} />
                                        <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} />
                                        <Area type="monotone" dataKey="effectif" name="Effectif" stroke={BLUE} strokeWidth={2.5} fill="url(#effGrad)" dot={{ r: 3, fill: BLUE }} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </Card>
                            <Card title="Performance (%)" icon={<TrendingUp className="w-4 h-4" />}>
                                <ResponsiveContainer width="100%" height={260}>
                                    <LineChart data={trends.series} margin={{ top: 8, right: 12, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} vertical={false} />
                                        <XAxis dataKey="year" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} />
                                        <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={36} domain={[0, 100]} />
                                        <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} formatter={(v) => `${v}%`} />
                                        <Legend wrapperStyle={{ fontSize: 11 }} />
                                        <Line type="monotone" dataKey="reussite" name="Réussite" stroke={GREEN} strokeWidth={2.5} dot={{ r: 3, fill: GREEN }} />
                                        <Line type="monotone" dataKey="recouvrement" name="Recouvrement" stroke={BLUE} strokeWidth={2.5} dot={{ r: 3, fill: BLUE }} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </Card>
                        </div>

                        <Card title="Déperdition scolaire (%)" icon={<TrendingUp className="w-4 h-4" />}>
                            <ResponsiveContainer width="100%" height={240}>
                                <LineChart data={trendSeries} margin={{ top: 8, right: 12, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} vertical={false} />
                                    <XAxis dataKey="year" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} />
                                    <YAxis tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} width={36} />
                                    <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} formatter={(v) => `${v}%`} />
                                    <Legend wrapperStyle={{ fontSize: 11 }} />
                                    <Line type="monotone" dataKey="redoublement" name="Redoublement" stroke={ORANGE} strokeWidth={2.5} dot={{ r: 3, fill: ORANGE }} connectNulls={false} />
                                    <Line type="monotone" dataKey="abandon" name="Abandon" stroke={VIOLET} strokeWidth={2.5} dot={{ r: 3, fill: VIOLET }} connectNulls={false} />
                                </LineChart>
                            </ResponsiveContainer>
                            <p className="text-xs text-gray-400 mt-2">L&apos;année en cours n&apos;est pas tracée : les décisions de fin d&apos;année (redoublement, abandon) ne sont pas encore arrêtées.</p>
                        </Card>

                        <Card title="Tableau comparatif" icon={<BarChart3 className="w-4 h-4" />}>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead><tr className="text-left text-xs text-gray-500 uppercase border-b border-gray-100 dark:border-gray-700">
                                        <th className="py-2">Année</th><th className="py-2 text-right">Effectif</th><th className="py-2 text-right">% filles</th>
                                        <th className="py-2 text-right">Redoubl.</th><th className="py-2 text-right">Abandon</th><th className="py-2 text-right">Recouvr.</th>
                                        <th className="py-2 text-right">Réussite</th><th className="py-2 text-right">Admission</th></tr></thead>
                                    <tbody>{trendSeries.map((r) => {
                                        const pct = (v: number | string | null) => (v == null ? '—' : `${v}%`);
                                        return (
                                            <tr key={String(r.year)} className="border-b border-gray-50 dark:border-gray-700/50">
                                                <td className="py-2 font-medium">{r.year}{r.year === activeYearLabel && <span className="ml-2 text-xs text-blue-500">en cours</span>}</td>
                                                <td className="py-2 text-right">{r.effectif}</td><td className="py-2 text-right">{r.part_filles}%</td>
                                                <td className="py-2 text-right">{pct(r.redoublement)}</td><td className="py-2 text-right">{pct(r.abandon)}</td><td className="py-2 text-right">{pct(r.recouvrement)}</td>
                                                <td className="py-2 text-right">{pct(r.reussite)}</td><td className="py-2 text-right">{pct(r.admission)}</td>
                                            </tr>
                                        );
                                    })}</tbody>
                                </table>
                            </div>
                        </Card>
                    </div>
                )}

                {/* ---- Géographie ---- */}
                {tab === 'geographie' && (
                    <div className="space-y-6">
                        {geography.by_region.length === 0 ? (
                            <>
                                <div className="rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm px-4 py-3">
                                    Origine renseignée pour <strong>{geography.coverage}%</strong> des élèves ({geography.localized} / {geography.total}). Complétez la région/préfecture sur la fiche élève pour affiner.
                                </div>
                                <div className="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-card p-12 text-center text-gray-400">
                                    Aucune origine géographique renseignée.
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                    <Kpi label="Couverture" value={`${geography.coverage}%`} sub={`${geography.localized} / ${geography.total} élèves`} icon={MapPin} tone="text-blue-600" />
                                    <Kpi label="Grand Lomé" value={`${grandLomeShare}%`} sub="Golfe + Agoè-Nyivé" icon={Users} tone="text-violet-600" />
                                    <Kpi label="Régions" value={geography.by_region.length} sub="représentées" icon={Layers} tone="text-emerald-600" />
                                    <Kpi label="Préfectures" value={geography.by_prefecture.length} sub="d'origine" icon={UserCheck} tone="text-orange-600" />
                                </div>

                                <Card title="Origine des élèves — régions et préfectures" icon={<MapPin className="w-4 h-4" />}>
                                    {/* Hiérarchie région → préfecture : l'aire porte la magnitude, la teinte
                                        (rampe séquentielle) l'intensité, les libellés l'identité — jamais une
                                        couleur par région, indistinguable au-delà de trois ou quatre. */}
                                    <ResponsiveContainer width="100%" height={380}>
                                        <Treemap
                                            data={treemapData}
                                            dataKey="size"
                                            aspectRatio={4 / 3}
                                            stroke={theme.surface}
                                            isAnimationActive={false}
                                            content={<TreemapNode sequential={theme.sequential} surface={theme.surface} axis={theme.axis} tick={theme.tick} maxLeaf={maxLeaf} />}
                                        >
                                            <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} formatter={(v) => [`${v} élèves`, 'Effectif']} />
                                        </Treemap>
                                    </ResponsiveContainer>
                                    <div className="flex items-center gap-3 mt-3 text-xs text-gray-400">
                                        <span>Effectif&nbsp;:</span>
                                        <span className="flex items-center gap-1"><span className="inline-block w-3 h-3 rounded-sm" style={{ background: theme.sequential[0] }} /> faible</span>
                                        <span className="flex items-center gap-1"><span className="inline-block w-3 h-3 rounded-sm" style={{ background: theme.sequential[theme.sequential.length - 1] }} /> élevé</span>
                                    </div>
                                </Card>

                                <Card title="Effectif par région" icon={<BarChart3 className="w-4 h-4" />}>
                                    <ResponsiveContainer width="100%" height={220}>
                                        <BarChart layout="vertical" data={geography.by_region} margin={{ top: 4, right: 24, left: 8, bottom: 4 }}>
                                            <CartesianGrid strokeDasharray="3 3" stroke={theme.grid} horizontal={false} />
                                            <XAxis type="number" tick={{ fontSize: 11, fill: theme.tick }} axisLine={false} tickLine={false} allowDecimals={false} />
                                            <YAxis type="category" dataKey="name" width={104} tick={{ fontSize: 12, fill: theme.axis }} axisLine={false} tickLine={false} />
                                            <RTooltip contentStyle={theme.tooltip.contentStyle} itemStyle={theme.tooltip.itemStyle} formatter={(v) => [`${v} élèves`, 'Effectif']} />
                                            <Bar dataKey="total" fill={BLUE} radius={[0, 4, 4, 0]} maxBarSize={26}>
                                                <LabelList dataKey="total" position="right" style={{ fontSize: 11, fill: theme.tick }} />
                                            </Bar>
                                        </BarChart>
                                    </ResponsiveContainer>
                                </Card>
                            </>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
