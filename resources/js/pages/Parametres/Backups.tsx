import { Head, router, useForm } from '@inertiajs/react';
import {
    AlertCircle, AlertTriangle, Archive, CheckCircle2, Clock, Cloud, Database, DatabaseBackup,
    Download, HardDrive, Lock, Play, Trash2, Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel,
    AlertDialogContent, AlertDialogDescription, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

interface BackupRow {
    id: string; filename: string; format: string; disk: string; size: number;
    status: string; error: string | null; scheduled: boolean;
    locked?: boolean; label?: string | null;
    created_by: string | null; created_at: string | null;
}
interface AcademicYearRow { id: string; year: string; archived: boolean; }
interface Settings { frequency: string; time: string; day_of_week: string; formats: string; retention: string; }
interface Props { backups: BackupRow[]; settings: Settings; storageDriver: string; academicYears: AcademicYearRow[]; }

const DAYS = [
    { v: '1', l: 'Lundi' }, { v: '2', l: 'Mardi' }, { v: '3', l: 'Mercredi' },
    { v: '4', l: 'Jeudi' }, { v: '5', l: 'Vendredi' }, { v: '6', l: 'Samedi' }, { v: '7', l: 'Dimanche' },
];

const fmtSize = (b: number) => b > 1048576 ? `${(b / 1048576).toFixed(1)} Mo` : `${Math.max(1, Math.round(b / 1024))} Ko`;

export default function Backups({ backups, settings, storageDriver, academicYears }: Readonly<Props>) {
    const [deleteId, setDeleteId] = useState<string | null>(null);
    const [runFormats, setRunFormats] = useState<string[]>(['json', 'sql']);
    const [running, setRunning] = useState(false);

    const [archiveYear, setArchiveYear] = useState<string>('');
    const [archiving, setArchiving] = useState(false);
    const runArchive = () => {
        if (!archiveYear) return;
        setArchiving(true);
        router.post(route('backups.archive'), { academic_year_id: archiveYear }, {
            preserveScroll: true,
            onSuccess: () => setArchiveYear(''),
            onFinish: () => setArchiving(false),
        });
    };

    const restoreInputRef = useRef<HTMLInputElement>(null);
    const [restoreFile, setRestoreFile] = useState<File | null>(null);
    const [restoreConfirm, setRestoreConfirm] = useState(false);
    const [restoring, setRestoring] = useState(false);

    const runRestore = () => {
        if (!restoreFile) return;
        setRestoring(true);
        router.post(route('backups.restore'), { file: restoreFile }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { setRestoreFile(null); if (restoreInputRef.current) restoreInputRef.current.value = ''; },
            onFinish: () => { setRestoring(false); setRestoreConfirm(false); },
        });
    };

    const toggle = (list: string[], set: (v: string[]) => void, f: string) =>
        set(list.includes(f) ? list.filter(x => x !== f) : [...list, f]);

    const runNow = () => {
        if (runFormats.length === 0) return;
        setRunning(true);
        router.post(route('backups.store'), { formats: runFormats }, {
            preserveScroll: true,
            onFinish: () => setRunning(false),
        });
    };

    const { data, setData, post, processing } = useForm({
        frequency: settings.frequency || 'none',
        time: settings.time || '02:00',
        day_of_week: settings.day_of_week || '1',
        formats: (settings.formats || 'json,sql').split(',').filter(Boolean),
        retention: settings.retention || '10',
    });

    const saveSchedule = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('backups.schedule'), { preserveScroll: true });
    };

    const isRemote = storageDriver === 's3';

    return (
        <AppLayout>
            <Head title="Sauvegardes" />
            <div className="w-full max-w-5xl mx-auto space-y-6">

                <div>
                    <h1 className="text-3xl font-bold tracking-tight text-gray-900 flex items-center gap-2">
                        <DatabaseBackup className="w-7 h-7 text-blue-600" /> Sauvegardes
                    </h1>
                    <p className="mt-2 text-gray-500">Sauvegardez la base de données (JSON / SQL) et planifiez des sauvegardes automatiques.</p>
                </div>

                {/* Destination de stockage */}
                <div className={`rounded-2xl p-4 ring-1 flex items-center gap-3 ${isRemote ? 'bg-emerald-50 ring-emerald-100' : 'bg-amber-50 ring-amber-100'}`}>
                    {isRemote ? <Cloud className="w-5 h-5 text-emerald-600" /> : <HardDrive className="w-5 h-5 text-amber-600" />}
                    <div className="text-sm">
                        <p className="font-semibold text-gray-900">
                            Destination : {isRemote ? 'Dépôt distant (S3 / R2)' : 'Stockage local du serveur'}
                        </p>
                        <p className="text-gray-500">
                            {isRemote
                                ? 'Les sauvegardes sont envoyées sur votre stockage cloud configuré.'
                                : 'Configurez un stockage S3/R2 dans « Fichiers & Stockage » pour externaliser les sauvegardes.'}
                        </p>
                    </div>
                </div>

                {/* Sauvegarde manuelle */}
                <div className="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-5">
                    <h2 className="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <Play className="w-4 h-4 text-blue-600" /> Sauvegarder maintenant
                    </h2>
                    <div className="flex flex-wrap items-center gap-6">
                        <div className="flex items-center gap-4">
                            {['json', 'sql'].map(f => (
                                <label key={f} className="flex items-center gap-2 text-sm cursor-pointer">
                                    <Checkbox checked={runFormats.includes(f)} onCheckedChange={() => toggle(runFormats, setRunFormats, f)} />
                                    <span className="uppercase font-medium text-gray-700">{f}</span>
                                </label>
                            ))}
                        </div>
                        <Button onClick={runNow} disabled={running || runFormats.length === 0} className="bg-blue-600 hover:bg-blue-700 gap-2">
                            <Database className="w-4 h-4" /> {running ? 'Sauvegarde…' : 'Lancer la sauvegarde'}
                        </Button>
                    </div>
                </div>

                {/* Archive d'année scolaire */}
                <div className="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-5">
                    <h2 className="font-semibold text-gray-900 flex items-center gap-2 mb-1">
                        <Archive className="w-4 h-4 text-cyan-600" /> Archiver une année scolaire
                    </h2>
                    <p className="text-sm text-gray-500 mb-4">
                        Génère un instantané complet <strong>verrouillé</strong> et conservé à long terme (exclu de la rétention automatique).
                        L'archive est aussi créée automatiquement à la clôture d'une année.
                    </p>
                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-1.5 min-w-56">
                            <label className="text-sm font-medium text-gray-700">Année scolaire</label>
                            <Select value={archiveYear} onValueChange={setArchiveYear}>
                                <SelectTrigger><SelectValue placeholder="Choisir une année…" /></SelectTrigger>
                                <SelectContent>
                                    {academicYears.map(y => (
                                        <SelectItem key={y.id} value={y.id} disabled={y.archived}>
                                            {y.year}{y.archived ? ' — déjà archivée' : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <Button onClick={runArchive} disabled={archiving || !archiveYear} className="bg-cyan-600 hover:bg-cyan-700 gap-2">
                            <Archive className="w-4 h-4" /> {archiving ? 'Archivage…' : "Archiver l'année"}
                        </Button>
                    </div>
                </div>

                {/* Planification */}
                <form onSubmit={saveSchedule} className="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-5 space-y-4">
                    <h2 className="font-semibold text-gray-900 flex items-center gap-2">
                        <Clock className="w-4 h-4 text-indigo-600" /> Planification automatique
                    </h2>
                    <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700">Fréquence</label>
                            <Select value={data.frequency} onValueChange={v => setData('frequency', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Désactivée</SelectItem>
                                    <SelectItem value="daily">Quotidienne</SelectItem>
                                    <SelectItem value="weekly">Hebdomadaire</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700">Heure</label>
                            <Input type="time" value={data.time} onChange={e => setData('time', e.target.value)} />
                        </div>
                        {data.frequency === 'weekly' && (
                            <div className="space-y-1.5">
                                <label className="text-sm font-medium text-gray-700">Jour</label>
                                <Select value={data.day_of_week} onValueChange={v => setData('day_of_week', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {DAYS.map(d => <SelectItem key={d.v} value={d.v}>{d.l}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium text-gray-700">Rétention (nb conservés)</label>
                            <Input type="number" min={0} max={365} value={data.retention} onChange={e => setData('retention', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700">Formats générés</label>
                        <div className="flex items-center gap-4">
                            {['json', 'sql'].map(f => (
                                <label key={f} className="flex items-center gap-2 text-sm cursor-pointer">
                                    <Checkbox checked={data.formats.includes(f)} onCheckedChange={() => setData('formats', data.formats.includes(f) ? data.formats.filter(x => x !== f) : [...data.formats, f])} />
                                    <span className="uppercase font-medium text-gray-700">{f}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                    <p className="text-xs text-gray-400">
                        La planification nécessite que le planificateur Laravel tourne sur le serveur
                        (<code>php artisan schedule:run</code> via cron, chaque minute).
                    </p>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700">Enregistrer la planification</Button>
                    </div>
                </form>

                {/* Restauration */}
                <div className="bg-white rounded-2xl shadow-sm ring-1 ring-red-100 p-5 space-y-4">
                    <h2 className="font-semibold text-gray-900 flex items-center gap-2">
                        <Upload className="w-4 h-4 text-red-600" /> Restaurer la base de données
                    </h2>
                    <div className="flex items-start gap-3 rounded-xl bg-red-50 ring-1 ring-red-100 p-3 text-sm">
                        <AlertTriangle className="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                        <p className="text-red-700">
                            La restauration <strong>écrase les données actuelles</strong> avec le contenu du fichier.
                            Une sauvegarde de sécurité (JSON) est créée automatiquement avant l'opération.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <input
                            ref={restoreInputRef}
                            type="file"
                            accept=".json,.sql,.gz,.dump"
                            onChange={e => setRestoreFile(e.target.files?.[0] ?? null)}
                            className="block text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:text-red-700 file:text-sm hover:file:bg-red-100"
                        />
                        <Button
                            onClick={() => setRestoreConfirm(true)}
                            disabled={!restoreFile || restoring}
                            className="bg-red-600 hover:bg-red-700 gap-2"
                        >
                            <Upload className="w-4 h-4" /> {restoring ? 'Restauration…' : 'Restaurer'}
                        </Button>
                    </div>
                    <p className="text-xs text-gray-400">Formats acceptés : .json, .sql, .gz (compressé) ou .dump (PostgreSQL) issus de cette application — max 200 Mo.</p>
                </div>

                {/* Historique */}
                <div className="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
                    <h2 className="font-semibold text-gray-900 px-5 pt-5 pb-3">Historique des sauvegardes</h2>
                    <Table>
                        <TableHeader className="bg-gray-50">
                            <TableRow>
                                <TableHead>Fichier</TableHead>
                                <TableHead>Format</TableHead>
                                <TableHead>Taille</TableHead>
                                <TableHead>Origine</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead className="text-center w-28">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {backups.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="py-12 text-center text-gray-400">Aucune sauvegarde pour le moment.</TableCell></TableRow>
                            ) : backups.map(b => (
                                <TableRow key={b.id} className="hover:bg-gray-50">
                                    <TableCell className="font-mono text-xs text-gray-700">
                                        {b.filename}
                                        {b.locked && (
                                            <span className="ml-2 inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2 py-0.5 font-sans text-[11px] font-medium text-cyan-700 ring-1 ring-cyan-100">
                                                <Lock className="w-3 h-3" /> {b.label ?? 'Archive'}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell><span className="text-xs font-bold uppercase text-blue-600">{b.format}</span></TableCell>
                                    <TableCell className="text-sm text-gray-600">{b.status === 'completed' ? fmtSize(b.size) : '—'}</TableCell>
                                    <TableCell className="text-xs text-gray-500">{b.scheduled ? 'Planifiée' : (b.created_by ?? 'Manuelle')}</TableCell>
                                    <TableCell>
                                        {b.status === 'completed' ? (
                                            <span className="inline-flex items-center gap-1 text-xs text-emerald-600"><CheckCircle2 className="w-3.5 h-3.5" /> OK</span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 text-xs text-red-600" title={b.error ?? ''}><AlertCircle className="w-3.5 h-3.5" /> Échec</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm text-gray-600">{b.created_at}</TableCell>
                                    <TableCell>
                                        <div className="flex justify-center gap-1.5">
                                            {b.status === 'completed' && (
                                                <a href={route('backups.download', b.id)}>
                                                    <Button variant="outline" size="sm" className="gap-1 text-xs"><Download className="w-3.5 h-3.5" /></Button>
                                                </a>
                                            )}
                                            <Button variant="outline" size="sm" className="border-red-200 text-red-500 hover:bg-red-50" onClick={() => setDeleteId(b.id)}>
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <AlertDialog open={restoreConfirm} onOpenChange={setRestoreConfirm}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Confirmer la restauration ?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Toutes les données actuelles seront remplacées par le contenu de
                            « {restoreFile?.name} ». Une sauvegarde de sécurité est créée avant. Cette action est irréversible.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="flex justify-end gap-2">
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction className="bg-red-600 hover:bg-red-700" onClick={runRestore}>
                            Restaurer maintenant
                        </AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer cette sauvegarde ?</AlertDialogTitle>
                        <AlertDialogDescription>Le fichier sera définitivement supprimé du stockage.</AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="flex justify-end gap-2">
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction className="bg-red-600 hover:bg-red-700" onClick={() => deleteId && router.delete(route('backups.destroy', deleteId), { preserveScroll: true, onSuccess: () => setDeleteId(null) })}>
                            Supprimer
                        </AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
