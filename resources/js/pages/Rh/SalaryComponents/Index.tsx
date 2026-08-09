import { Head, router, useForm } from '@inertiajs/react';
import {
    Coins, Plus, Pencil, Trash2, Search, ChevronLeft, ChevronRight,
} from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { IconButton } from '@/components/icon-button';
import { Input } from '@/components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { useMoney } from '@/helpers/money';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

interface Component {
    id: string;
    name: string;
    code: string | null;
    type: 'earning' | 'deduction';
    default_amount: number | null;
    is_default: boolean;
    active: boolean;
}

interface Paginated<T> { data: T[]; current_page: number; last_page: number; from: number; to: number; total: number; }

interface Props {
    components: Paginated<Component>;
    perPage: number;
    filters: { search?: string; type?: string };
}

interface FormData {
    name: string; code: string; type: string; default_amount: string; is_default: boolean; active: boolean;
}

const PER_PAGE_OPTIONS = [10, 25, 50, 100];
const emptyForm: FormData = { name: '', code: '', type: 'earning', default_amount: '', is_default: false, active: true };

export default function Index({ components, perPage, filters }: Readonly<Props>) {
    const fmt = useMoney();
    const [deletingId, setDeletingId] = useState<string | null>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');

    const form = useForm<FormData>({ ...emptyForm });

    const apply = () => router.get(route('salary-components.index'), {
        search: search || undefined, type: type || undefined, per_page: String(perPage),
    }, { preserveState: true, replace: true });
    const clear = () => { setSearch(''); setType(''); router.get(route('salary-components.index'), {}, { preserveState: true, replace: true }); };
    const goToPage = (page: number) => router.get(route('salary-components.index'), { ...filters, per_page: String(perPage), page: String(page) }, { preserveState: true, replace: true });
    const changePerPage = (value: number) => router.get(route('salary-components.index'), { ...filters, per_page: String(value), page: '1' }, { preserveState: true, replace: true });

    const openCreate = () => {
        form.setData({ ...emptyForm });
        form.clearErrors();
        setEditingId(null);
        setModalOpen(true);
    };
    const openEdit = (c: Component) => {
        form.setData({
            name: c.name, code: c.code ?? '', type: c.type,
            default_amount: c.default_amount != null ? String(c.default_amount) : '',
            is_default: c.is_default, active: c.active,
        });
        form.clearErrors();
        setEditingId(c.id);
        setModalOpen(true);
    };

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { setModalOpen(false); form.reset(); } };
        if (editingId) form.put(route('salary-components.update', editingId), opts);
        else form.post(route('salary-components.store'), opts);
    };

    const selectCls = "px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500";
    const hasFilters = !!(search || type);

    return (
        <AppLayout>
            <Head title="Rubriques de paie" />
            <div className="space-y-5 w-full">
                <div className="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
                            <Coins className="w-7 h-7 text-amber-500" /> Rubriques de paie
                        </h1>
                        <p className="text-sm text-gray-500 mt-0.5">Gains et retenues réutilisables. Les rubriques « auto » sont appliquées à la génération d'un cycle.</p>
                    </div>
                    <Button className="gap-2 bg-blue-600 hover:bg-blue-700" onClick={openCreate}>
                        <Plus className="w-4 h-4" /> Nouvelle rubrique
                    </Button>
                </div>

                {/* Filtres */}
                <div className="bg-white rounded-lg p-4 shadow-sm">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="relative flex-1 min-w-[220px]">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <Input value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => e.key === 'Enter' && apply()} placeholder="Nom, code..." className="pl-10 border-gray-300" />
                        </div>
                        <select value={type} onChange={e => setType(e.target.value)} className={selectCls}>
                            <option value="">Tous les types</option>
                            <option value="earning">Gains</option>
                            <option value="deduction">Retenues</option>
                        </select>
                        <Button onClick={apply} className="bg-blue-600 hover:bg-blue-700 text-white gap-2"><Search className="w-4 h-4" /> Rechercher</Button>
                        {hasFilters && <Button variant="outline" onClick={clear} className="border-gray-300 text-gray-700">Réinit.</Button>}
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100 gap-4 flex-wrap">
                        <p className="text-sm text-gray-600"><span className="font-semibold">{components.total}</span> rubrique(s){hasFilters && <span className="text-blue-500 ml-2">— filtrées</span>}</p>
                        <div className="flex items-center gap-2 text-sm text-gray-600">
                            <span>Lignes par page :</span>
                            <select value={perPage} onChange={e => changePerPage(Number(e.target.value))} className="h-8 px-2 border border-gray-300 rounded-md bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {PER_PAGE_OPTIONS.map(n => <option key={n} value={n}>{n}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-gray-50">
                                <TableRow className="border-b border-gray-200">
                                    <TableHead className="font-semibold text-gray-900">Rubrique</TableHead>
                                    <TableHead className="font-semibold text-gray-900">Code</TableHead>
                                    <TableHead className="font-semibold text-gray-900">Type</TableHead>
                                    <TableHead className="text-right font-semibold text-gray-900">Montant par défaut</TableHead>
                                    <TableHead className="font-semibold text-gray-900">Application</TableHead>
                                    <TableHead className="font-semibold text-gray-900">Statut</TableHead>
                                    <TableHead className="text-center font-semibold text-gray-900 w-24">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {components.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-12 text-gray-500">
                                            <div className="flex flex-col items-center gap-2">
                                                <Coins className="w-12 h-12 text-gray-300" />
                                                <p className="text-lg">Aucune rubrique</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    components.data.map(c => (
                                        <TableRow key={c.id} className="border-b border-gray-100 hover:bg-blue-50/40 transition-colors">
                                            <TableCell className="font-semibold text-gray-900">{c.name}</TableCell>
                                            <TableCell className="text-gray-500">{c.code ?? '—'}</TableCell>
                                            <TableCell>
                                                {c.type === 'earning'
                                                    ? <span className="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Gain</span>
                                                    : <span className="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700">Retenue</span>}
                                            </TableCell>
                                            <TableCell className="text-right text-gray-700">{c.default_amount != null ? fmt(c.default_amount) : '—'}</TableCell>
                                            <TableCell>
                                                {c.is_default
                                                    ? <span className="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Automatique</span>
                                                    : <span className="text-xs text-gray-400">Manuelle</span>}
                                            </TableCell>
                                            <TableCell>
                                                {c.active
                                                    ? <span className="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700">Active</span>
                                                    : <span className="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-gray-200 text-gray-600">Inactive</span>}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <IconButton label="Modifier" icon={<Pencil className="w-4 h-4" />} onClick={() => openEdit(c)} />
                                                    <IconButton label="Supprimer" icon={<Trash2 className="w-4 h-4" />} className="text-red-600 border-red-200 hover:bg-red-50" onClick={() => setDeletingId(c.id)} />
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                        {components.total > 0 && <p className="text-xs text-gray-400">{components.from}–{components.to} sur {components.total}</p>}
                        {components.last_page > 1 && (
                            <div className="flex items-center gap-1.5">
                                <Button size="sm" variant="outline" className="h-8 w-8 p-0" disabled={components.current_page === 1} onClick={() => goToPage(components.current_page - 1)}><ChevronLeft className="w-4 h-4" /></Button>
                                <span className="text-xs text-gray-500 px-2">{components.current_page} / {components.last_page}</span>
                                <Button size="sm" variant="outline" className="h-8 w-8 p-0" disabled={components.current_page === components.last_page} onClick={() => goToPage(components.current_page + 1)}><ChevronRight className="w-4 h-4" /></Button>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal création / édition */}
            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingId ? 'Modifier la rubrique' : 'Nouvelle rubrique'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-900 mb-1.5">Nom *</label>
                                <Input value={form.data.name} onChange={e => form.setData('name', e.target.value)} placeholder="Ex: Prime de transport" className={form.errors.name ? 'border-red-500' : ''} />
                                {form.errors.name && <p className="text-red-600 text-sm mt-1">{form.errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-900 mb-1.5">Type *</label>
                                <Select value={form.data.type} onValueChange={(v) => form.setData('type', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="earning">Gain</SelectItem>
                                        <SelectItem value="deduction">Retenue</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-900 mb-1.5">Code</label>
                                <Input value={form.data.code} onChange={e => form.setData('code', e.target.value)} placeholder="Ex: TRANSPORT" />
                            </div>
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-900 mb-1.5">Montant par défaut</label>
                                <Input type="number" min={0} value={form.data.default_amount} onChange={e => form.setData('default_amount', e.target.value)} placeholder="Optionnel" />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <Checkbox checked={form.data.is_default} onCheckedChange={c => form.setData('is_default', c === true)} />
                                Appliquer automatiquement à chaque bulletin
                            </label>
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <Checkbox checked={form.data.active} onCheckedChange={c => form.setData('active', c === true)} />
                                Active
                            </label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setModalOpen(false)}>Annuler</Button>
                            <Button type="submit" disabled={form.processing} className="bg-blue-600 hover:bg-blue-700">
                                {form.processing ? 'Enregistrement...' : (editingId ? 'Enregistrer' : 'Ajouter')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog open={!!deletingId} onOpenChange={() => setDeletingId(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer cette rubrique ?</AlertDialogTitle>
                        <AlertDialogDescription>Les bulletins déjà générés ne sont pas affectés (leurs lignes sont figées).</AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="flex justify-end gap-2">
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction className="bg-red-600 hover:bg-red-700" onClick={() => {
                            if (deletingId) router.delete(route('salary-components.destroy', deletingId), { preserveScroll: true, onFinish: () => setDeletingId(null) });
                        }}>Supprimer</AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
