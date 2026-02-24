import { Head, router } from '@inertiajs/react';
import { Plus, Pencil, Trash2, Search, Tag, CheckCircle2, Eye } from 'lucide-react';
import { useState } from 'react';
import { FormDrawer } from '@/components/form-drawer';
import { ClassroomTypeDrawer } from '@/components/ClassroomTypes/classroom-type-drawer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface ClassroomType {
    id: string;
    name: string;
    description: string | null;
    active: boolean;
}

interface PaginatedClassroomTypes {
    data: ClassroomType[];
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

interface IndexProps {
    classroomTypes: PaginatedClassroomTypes;
    activeCount: number;
    message?: string;
}

export default function Index({ classroomTypes, activeCount, message }: Readonly<IndexProps>) {
    const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    const handleDelete = (typeId: string) => {
        setIsDeleting(true);
        router.delete(route('classroom-types.destroy', typeId), {
            onSuccess: () => {
                setDeleteConfirm(null);
                setIsDeleting(false);
            },
            onError: () => {
                setIsDeleting(false);
            },
        });
    };

    const handleSearch = () => {
        router.get(route('classroom-types.index'), { search: searchQuery }, { preserveState: true });
    };

    const handleClearSearch = () => {
        setSearchQuery('');
        router.get(route('classroom-types.index'), { search: '' }, { preserveState: true });
    };

    const statsCards = [
        {
            title: 'Total des types',
            value: classroomTypes.total,
            icon: Tag,
            bgColor: 'bg-blue-50',
            textColor: 'text-blue-600',
            borderColor: 'border-blue-200',
        },
        {
            title: 'Types actifs',
            value: activeCount,
            icon: CheckCircle2,
            bgColor: 'bg-green-50',
            textColor: 'text-green-600',
            borderColor: 'border-green-200',
        },
        {
            title: 'Page',
            value: `${classroomTypes.current_page}/${classroomTypes.last_page}`,
            icon: Plus,
            bgColor: 'bg-purple-50',
            textColor: 'text-purple-600',
            borderColor: 'border-purple-200',
        },
    ];

    return (
        <FormDrawer<ClassroomType>
            onSuccess={() => router.reload()}
            renderDrawer={({ isOpen, onOpenChange, selectedItem, onSuccess }) => (
                <ClassroomTypeDrawer
                    isOpen={isOpen}
                    onOpenChange={onOpenChange}
                    classroomType={selectedItem}
                    onSuccess={onSuccess}
                />
            )}
        >
            {({ onOpenCreate, onOpenEdit }) => (
                <AppLayout>
                    <Head title="Types de classes" />

                    <div className="space-y-6">
                        <div className="flex items-start justify-between">
                            <div>
                                <h1 className="text-4xl font-bold tracking-tight text-gray-900">
                                    Types de classes
                                </h1>
                                <p className="mt-2 text-lg text-gray-600">
                                    Gérez les types de classes disponibles
                                </p>
                            </div>
                            <Button
                                onClick={onOpenCreate}
                                className="gap-2 bg-blue-600 hover:bg-blue-700"
                            >
                                <Plus className="w-5 h-5" />
                                Nouveau type
                            </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {statsCards.map((stat, index) => {
                                const Icon = stat.icon;
                                return (
                                    <div
                                        key={index}
                                        className={`${stat.bgColor} rounded-lg p-6 transition-all hover:shadow-md shadow-sm`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">
                                                    {stat.title}
                                                </p>
                                                <p className={`text-3xl font-bold ${stat.textColor} mt-2`}>
                                                    {stat.value}
                                                </p>
                                            </div>
                                            <Icon className={`w-12 h-12 ${stat.textColor} opacity-20`} />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {message && (
                            <div className="bg-green-50 text-green-800 px-4 py-3 rounded-lg flex items-center gap-3 shadow-sm">
                                <CheckCircle2 className="w-5 h-5 flex-shrink-0" />
                                <span>{message}</span>
                            </div>
                        )}

                        <div className="bg-white rounded-lg p-4 shadow-sm">
                            <div className="flex gap-4">
                                <div className="flex-1 relative">
                                    <Search className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                                    <Input
                                        type="text"
                                        placeholder="Rechercher un type..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="pl-10 bg-gray-50 border-gray-300"
                                    />
                                </div>
                                <Button
                                    onClick={handleSearch}
                                    className="bg-blue-600 hover:bg-blue-700 text-white gap-2"
                                >
                                    <Search className="w-4 h-4" />
                                    Rechercher
                                </Button>
                                {searchQuery && (
                                    <Button
                                        variant="outline"
                                        onClick={handleClearSearch}
                                        className="border-gray-300 text-gray-700 hover:bg-gray-50"
                                    >
                                        Réinitialiser
                                    </Button>
                                )}
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader className="bg-gray-50">
                                        <TableRow className="border-b border-gray-200">
                                            <TableHead className="font-semibold text-gray-900">Nom</TableHead>
                                            <TableHead className="font-semibold text-gray-900">Description</TableHead>
                                            <TableHead className="font-semibold text-gray-900">État</TableHead>
                                            <TableHead className="text-center font-semibold text-gray-900 w-28">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {classroomTypes.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={4}
                                                    className="text-center py-12 text-gray-500"
                                                >
                                                    <div className="flex flex-col items-center gap-2">
                                                        <Tag className="w-12 h-12 text-gray-300" />
                                                        <p className="text-lg">Aucun type trouvé</p>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            classroomTypes.data.map((type) => (
                                                <TableRow key={type.id} className="border-b border-gray-100 hover:bg-blue-50/40 transition-colors">
                                                    <TableCell className="font-semibold text-gray-900">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                                                <Tag className="h-5 w-5 text-blue-600" />
                                                            </div>
                                                            <span>{type.name}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-gray-600">
                                                        {type.description ? (
                                                            <span className="line-clamp-2">{type.description}</span>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-gray-600">
                                                        <span
                                                            className={`inline-flex items-center px-2 py-1 rounded-full text-sm font-medium ${
                                                                type.active
                                                                    ? 'bg-green-100 text-green-800'
                                                                    : 'bg-gray-100 text-gray-800'
                                                            }`}
                                                        >
                                                            {type.active ? '✓ Actif' : 'Inactif'}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <div className="flex gap-2 justify-center">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                                                onClick={() =>
                                                                    router.visit(route('classroom-types.show', type.id))
                                                                }
                                                            >
                                                                <Eye className="w-4 h-4" />
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                                                onClick={() => onOpenEdit(type)}
                                                            >
                                                                <Pencil className="w-4 h-4" />
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="border-red-300 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                                onClick={() => setDeleteConfirm(type.id)}
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </div>

                        {classroomTypes.last_page > 1 && (
                            <div className="flex items-center justify-between bg-white rounded-lg p-4 shadow-sm">
                                <div className="text-sm text-gray-600">
                                    Affichage de <span className="font-semibold">{classroomTypes.from}</span> à{' '}
                                    <span className="font-semibold">{classroomTypes.to}</span> sur{' '}
                                    <span className="font-semibold">{classroomTypes.total}</span> types
                                </div>
                                <div className="flex gap-2">
                                    {classroomTypes.current_page > 1 && (
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                router.get(
                                                    route('classroom-types.index'),
                                                    { page: classroomTypes.current_page - 1 },
                                                    { preserveState: true }
                                                )
                                            }
                                        >
                                            ← Précédent
                                        </Button>
                                    )}
                                    <span className="flex items-center px-4 text-sm font-medium text-gray-700">
                                        Page {classroomTypes.current_page} sur {classroomTypes.last_page}
                                    </span>
                                    {classroomTypes.current_page < classroomTypes.last_page && (
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                router.get(
                                                    route('classroom-types.index'),
                                                    { page: classroomTypes.current_page + 1 },
                                                    { preserveState: true }
                                                )
                                            }
                                        >
                                            Suivant →
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    <AlertDialog
                        open={deleteConfirm !== null}
                        onOpenChange={(open) => {
                            if (!open) setDeleteConfirm(null);
                        }}
                    >
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Supprimer le type</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Êtes-vous sûr de vouloir supprimer ce type de classe ?
                                    Cette action est irréversible.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <div className="flex gap-3 justify-end">
                                <AlertDialogCancel disabled={isDeleting}>
                                    Annuler
                                </AlertDialogCancel>
                                <AlertDialogAction
                                    onClick={() => deleteConfirm && handleDelete(deleteConfirm)}
                                    disabled={isDeleting}
                                    className="bg-red-600 hover:bg-red-700"
                                >
                                    {isDeleting ? 'Suppression...' : 'Supprimer'}
                                </AlertDialogAction>
                            </div>
                        </AlertDialogContent>
                    </AlertDialog>
                </AppLayout>
            )}
        </FormDrawer>
    );
}
