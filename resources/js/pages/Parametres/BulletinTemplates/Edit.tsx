import type { FormDataConvertible } from '@inertiajs/core';
import { Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, LayoutGrid, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

// Alias de type (et non `interface`) : seuls les alias reçoivent une signature
// d'index implicite, indispensable pour envoyer ces objets dans une requête
// Inertia (`FormDataConvertible`).
type Column = { key: string; label: string; width: number | string; type: string; source: string | null };
type Options = {
    show_class_stats?: boolean;
    nb_text?: string;
    signataire_titulaire?: string;
    signataire_chef?: string;
    [k: string]: FormDataConvertible;
};
interface EvalType { id: string; name: string; category: string }
interface Preset { name: string; columns: Column[]; options: Options }

interface Props {
    columns: Column[];
    options: Options;
    columnTypes: Record<string, string>;
    noteSources: Record<string, string>;
    evaluationTypes: EvalType[];
    presets: Preset[];
    school: { name: string };
}

const uid = () => `col_${Date.now()}_${Math.floor(Math.random() * 1000)}`;

export default function Edit({ columns: initialColumns, options: initialOptions, columnTypes, noteSources, evaluationTypes, presets, school }: Readonly<Props>) {
    const [columns, setColumns] = useState<Column[]>(initialColumns);
    const [options, setOptions] = useState<Options>(initialOptions);
    const [saving, setSaving] = useState(false);

    const patch = (i: number, p: Partial<Column>) => setColumns((c) => c.map((col, j) => (j === i ? { ...col, ...p } : col)));
    const add = () => setColumns((c) => [...c, { key: uid(), label: 'Colonne', width: 10, type: 'text', source: null }]);
    const remove = (i: number) => setColumns((c) => c.filter((_, j) => j !== i));
    const move = (i: number, dir: -1 | 1) => setColumns((c) => {
        const next = [...c];
        const j = i + dir;
        if (j < 0 || j >= next.length) return c;
        [next[i], next[j]] = [next[j], next[i]];
        return next;
    });

    const loadPreset = (name: string) => {
        const preset = presets.find((p) => p.name === name);
        if (preset) { setColumns(preset.columns); setOptions({ ...options, ...preset.options }); }
    };

    const save = () => {
        setSaving(true);
        router.post(route('bulletin-templates.update'), { columns, options }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const totalWidth = columns.reduce((s, c) => s + (parseFloat(String(c.width)) || 0), 0);

    return (
        <AppLayout>
            <Head title="Modèle de bulletin" />
            <div className="w-full space-y-6">
                <div className="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 className="text-4xl font-bold tracking-tight text-gray-900 flex items-center gap-3">
                            <LayoutGrid className="h-7 w-7 text-blue-600 shrink-0" /> Modèle de bulletin
                        </h1>
                        <p className="mt-2 text-gray-500">Composez les colonnes du bulletin de {school.name}. Le jeton <code className="font-mono">{'{periode}'}</code> devient « Trim. » ou « Sem. » selon le type de classe.</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Select onValueChange={loadPreset}>
                            <SelectTrigger className="w-52"><SelectValue placeholder="Charger un preset…" /></SelectTrigger>
                            <SelectContent>
                                {presets.map((p) => <SelectItem key={p.name} value={p.name}>{p.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <Button onClick={save} disabled={saving} className="bg-blue-600 hover:bg-blue-700 gap-2">
                            <Save className="w-4 h-4" /> {saving ? 'Enregistrement…' : 'Enregistrer'}
                        </Button>
                    </div>
                </div>

                {/* Colonnes */}
                <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-3">
                    <div className="flex items-center justify-between">
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">Colonnes ({columns.length})</p>
                        <span className={`text-xs ${Math.round(totalWidth) === 100 ? 'text-emerald-600' : 'text-amber-600'}`}>Largeur totale : {Math.round(totalWidth)}%</span>
                    </div>

                    <div className="space-y-2">
                        {columns.map((col, i) => (
                            <div key={col.key} className="flex flex-wrap items-center gap-2 rounded-lg ring-1 ring-slate-100 p-2">
                                <div className="flex flex-col">
                                    <button onClick={() => move(i, -1)} className="text-gray-400 hover:text-gray-700"><ArrowUp className="w-3.5 h-3.5" /></button>
                                    <button onClick={() => move(i, 1)} className="text-gray-400 hover:text-gray-700"><ArrowDown className="w-3.5 h-3.5" /></button>
                                </div>
                                <Input value={col.label} onChange={(e) => patch(i, { label: e.target.value })} placeholder="Libellé" className="h-9 flex-1 min-w-40" />
                                <Select value={col.type} onValueChange={(v) => patch(i, { type: v, source: v === 'note' ? (col.source ?? 'moyenne') : null })}>
                                    <SelectTrigger className="h-9 w-44"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(columnTypes).map(([v, label]) => <SelectItem key={v} value={v}>{label}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                                {col.type === 'note' && (
                                    <Select value={col.source ?? 'moyenne'} onValueChange={(v) => patch(i, { source: v })}>
                                        <SelectTrigger className="h-9 w-48"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(noteSources).map(([v, label]) => <SelectItem key={v} value={v}>{label}</SelectItem>)}
                                            {evaluationTypes.map((t) => <SelectItem key={t.id} value={`type:${t.id}`}>Type : {t.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                )}
                                <div className="flex items-center gap-1">
                                    <Input type="number" min={1} max={100} value={col.width}
                                        onChange={(e) => patch(i, { width: e.target.value })} className="h-9 w-16" />
                                    <span className="text-xs text-gray-400">%</span>
                                </div>
                                <Button variant="outline" size="sm" className="border-red-200 text-red-500 hover:bg-red-50" onClick={() => remove(i)}>
                                    <Trash2 className="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        ))}
                    </div>

                    <Button variant="outline" size="sm" className="gap-1.5" onClick={add}>
                        <Plus className="w-3.5 h-3.5" /> Ajouter une colonne
                    </Button>
                </div>

                {/* Options du pied */}
                <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 space-y-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">Pied de bulletin</p>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={Boolean(options.show_class_stats)} onCheckedChange={(v) => setOptions({ ...options, show_class_stats: Boolean(v) })} />
                        Afficher les statistiques de la classe (moyenne la plus forte / faible / générale)
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={Boolean(options.show_period_recap)} onCheckedChange={(v) => setOptions({ ...options, show_period_recap: Boolean(v) })} />
                        Afficher le récapitulatif inter-périodes + moyenne annuelle
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox checked={Boolean(options.show_discipline)} onCheckedChange={(v) => setOptions({ ...options, show_discipline: Boolean(v) })} />
                        Afficher le bloc discipline (retards, absences, décision du conseil)
                    </label>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700">Signataire (gauche)</label>
                            <Input value={options.signataire_titulaire ?? ''} onChange={(e) => setOptions({ ...options, signataire_titulaire: e.target.value })} />
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700">Signataire (droite)</label>
                            <Input value={options.signataire_chef ?? ''} onChange={(e) => setOptions({ ...options, signataire_chef: e.target.value })} />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700">Mention légale (N.B.)</label>
                        <Input value={options.nb_text ?? ''} onChange={(e) => setOptions({ ...options, nb_text: e.target.value })} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
