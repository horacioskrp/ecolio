import { Head, router } from '@inertiajs/react';
import { Plus, Pencil, Trash2, Search, Shield, CheckCircle2, Users, Eye, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

interface Role {
    id: string;
    name: string;
    description: string | null;
    permissions_count: number;
    created_at: string;
}

interface PaginatedRoles {
    data: Role[];
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

interface IndexProps {
    roles: PaginatedRoles;
    message?: string;
    filters: {
        search?: string;
    };
}

export default function Index({ roles, message, filters }: Readonly<IndexProps>) {
    const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');

    const handleDelete = (roleId: string) => {
        setIsDeleting(true);
        router.delete(route('roles.destroy', roleId), {
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
        router.get(route('roles.index'), { search: searchQuery }, { 
            preserveScroll: true,
            replace: true 
        });
    };

    const handleClearSearch = () => {
        setSearchQuery('');
        router.get(route('roles.index'), {}, { 
            preserveScroll: true,
            replace: true 
        });
    };

    // Stat Cards
    const statsCards = [
        {
            title: 'Rôles totaux',
            value: roles.total,
            icon: Shield,
            bgColor: 'bg-blue-50',
            textColor: 'text-blue-600',
            borderColor: 'border-blue-200',
        },
        {
            title: 'Moyenne de permissions',
            value: roles.total > 0 
                ? (roles.data.reduce((sum, role) => sum + role.permissions_count, 0) / roles.data.length).toFixed(0)
                : 0,
            icon: CheckCircle2,
            bgColor: 'bg-green-50',
            textColor: 'text-green-600',
            borderColor: 'border-green-200',
        },
        {
            title: 'Page',
            value: `${roles.current_page}/${roles.last_page}`,
            icon: Users,
            bgColor: 'bg-purple-50',
            textColor: 'text-purple-600',
            borderColor: 'border-purple-200',
        },
    ];

    return (
        <AppLayout>
            <Head title="Rôles" />

            <div className="space-y-6">
                {/* Header avec titre et bouton */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-4xl font-bold tracking-tight text-gray-900">
                            Rôles et Permissions
                        </h1>
                        <p className="mt-2 text-lg text-gray-600">
                            Gérez les rôles et leurs permissions
                        </p>
                    </div>
                    <Button 
                        onClick={() => router.get(route('roles.create'))}
                        className="gap-2 bg-blue-600 hover:bg-blue-700"
                    >
                        <Plus className="w-5 h-5" />
                        Nouveau rôle
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {statsCards.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <div
                                key={stat.title}
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

                {/* Message d'alerte */}
                {message && (
                    <div className="bg-green-50 text-green-800 px-4 py-3 rounded-lg flex items-center gap-3 shadow-sm">
                        <CheckCircle2 className="w-5 h-5 flex-shrink-0" />
                        <span>{message}</span>
                    </div>
                )}

                {/* Barre de recherche améliorée */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-100">
                    <div className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex-1 relative group">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-blue-400 group-focus-within:text-blue-600 transition" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher un rôle par nom ou description..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                    className="pl-10 pr-10 bg-gray-50 border-gray-200 focus:border-blue-300 focus:bg-white transition"
                                />
                                {searchQuery && (
                                    <button
                                        onClick={handleClearSearch}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                )}
                            </div>
                            <Button
                                onClick={handleSearch}
                                className="bg-blue-600 hover:bg-blue-700 text-white gap-2 shrink-0 transition"
                            >
                                <Search className="w-4 h-4" />
                                Rechercher
                            </Button>
                        </div>
                        {searchQuery && (
                            <p className="text-sm text-gray-500 mt-3">
                                Résultats pour : <span className="font-semibold text-gray-700">« {searchQuery} »</span>
                            </p>
                        )}
                    </div>
                </div>

                {/* Tableau des rôles */}
                <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-gray-50">
                                <TableRow className="border-b border-gray-200">
                                    <TableHead className="font-semibold text-gray-900">Nom</TableHead>
                                    <TableHead className="font-semibold text-gray-900">Description</TableHead>
                                    <TableHead className="font-semibold text-gray-900">Permissions</TableHead>
                                    <TableHead className="text-center font-semibold text-gray-900 w-24">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-center py-12 text-gray-500"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Shield className="w-12 h-12 text-gray-300" />
                                                <p className="text-lg">Aucun rôle trouvé</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    roles.data.map((role) => (
                                        <TableRow key={role.id} className="border-b border-gray-100 hover:bg-blue-50/40 transition-colors">
                                            <TableCell className="font-semibold text-gray-900">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                                        <Shield className="h-5 w-5 text-blue-600" />
                                                    </div>
                                                    <span>{role.name}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-gray-600 max-w-xs truncate">
                                                {role.description || <span className="text-gray-400">-</span>}
                                            </TableCell>
                                            <TableCell className="text-gray-600">
                                                <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-medium">
                                                    {role.permissions_count}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <div className="flex gap-2 justify-center">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                                        onClick={() => router.visit(route('roles.show', role.id))}
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                                        onClick={() => router.get(route('roles.edit', role.id))}
                                                    >
                                                        <Pencil className="w-4 h-4" />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="border-red-300 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                        onClick={() => setDeleteConfirm(role.id)}
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

                {/* Pagination améliorée */}
                {roles.last_page > 1 && (
                    <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-gray-600">
                                Affichage <span className="font-semibold">{roles.from}</span> à <span className="font-semibold">{roles.to}</span> sur <span className="font-semibold">{roles.total}</span> rôles
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={roles.current_page === 1}
                                    onClick={() => router.get(route('roles.index'), { 
                                        page: roles.current_page - 1, 
                                        search: filters.search 
                                    }, { preserveScroll: true })}
                                    className="border-gray-300 text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ChevronLeft className="w-4 h-4 mr-1" />
                                    Précédent
                                </Button>
                                <div className="flex gap-1">
                                    {Array.from({ length: Math.min(5, roles.last_page) }, (_, i) => {
                                        let page = i + 1;
                                        if (roles.last_page > 5 && roles.current_page > 3) {
                                            page = roles.current_page - 2 + i;
                                        }
                                        if (page > roles.last_page) return null;
                                        return (
                                            <Button
                                                key={page}
                                                variant={page === roles.current_page ? 'default' : 'outline'}
                                                size="sm"
                                                onClick={() => router.get(route('roles.index'), { 
                                                    page, 
                                                    search: filters.search 
                                                }, { preserveScroll: true })}
                                                className={page === roles.current_page 
                                                    ? 'bg-blue-600 hover:bg-blue-700 text-white' 
                                                    : 'border-gray-300 text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300'
                                                }
                                            >
                                                {page}
                                            </Button>
                                        );
                                    })}
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={roles.current_page === roles.last_page}
                                    onClick={() => router.get(route('roles.index'), { 
                                        page: roles.current_page + 1, 
                                        search: filters.search 
                                    }, { preserveScroll: true })}
                                    className="border-gray-300 text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Suivant
                                    <ChevronRight className="w-4 h-4 ml-1" />
                                </Button>
                            </div>
                            <p className="text-sm text-gray-600">
                                Page <span className="font-semibold">{roles.current_page}</span> sur <span className="font-semibold">{roles.last_page}</span>
                            </p>
                        </div>
                    </div>
                )}
            </div>

            {/* Dialog de confirmation suppression */}
            <AlertDialog open={!!deleteConfirm} onOpenChange={() => setDeleteConfirm(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer le rôle</AlertDialogTitle>
                        <AlertDialogDescription>
                            Êtes-vous sûr de vouloir supprimer ce rôle ? Cette action est irréversible.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="flex gap-3 justify-end">
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
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
    );
}
