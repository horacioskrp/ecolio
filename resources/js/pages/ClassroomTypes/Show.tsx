import { Head, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Tag, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface Classroom {
    id: string;
    name: string;
    code: string;
    capacity: number;
    active: boolean;
}

interface ClassroomType {
    id: string;
    name: string;
    description: string | null;
    active: boolean;
    created_at: string;
    updated_at: string;
    classrooms: Classroom[];
}

interface ShowProps {
    classroomType: ClassroomType;
}

export default function Show({ classroomType }: Readonly<ShowProps>) {
    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout>
            <Head title={classroomType.name} />

            <div className="space-y-6">
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="sm"
                            className="border-gray-300"
                            onClick={() => router.visit(route('classroom-types.index'))}
                        >
                            <ArrowLeft className="w-4 h-4" />
                        </Button>
                        <div>
                            <div className="flex items-center gap-3 mb-2">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <Tag className="h-6 w-6 text-blue-600" />
                                </div>
                                <h1 className="text-4xl font-bold tracking-tight text-gray-900">
                                    {classroomType.name}
                                </h1>
                            </div>
                            {classroomType.description && (
                                <p className="text-lg text-gray-600">{classroomType.description}</p>
                            )}
                        </div>
                    </div>
                    <Button
                        variant="outline"
                        className="border-gray-300 text-gray-700 hover:bg-gray-50"
                        onClick={() =>
                            router.visit(route('classroom-types.edit', classroomType.id))
                        }
                    >
                        Modifier
                    </Button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">État</p>
                                <p className="text-3xl font-bold text-gray-900 mt-2">
                                    {classroomType.active ? '✓ Actif' : 'Inactif'}
                                </p>
                            </div>
                            <CheckCircle2
                                className={`w-12 h-12 ${classroomType.active ? 'text-green-600' : 'text-gray-400'}`}
                            />
                        </div>
                    </div>

                    <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Classes associées</p>
                                <p className="text-3xl font-bold text-gray-900 mt-2">
                                    {classroomType.classrooms.length}
                                </p>
                            </div>
                            <Tag className="w-12 h-12 text-blue-600 opacity-20" />
                        </div>
                    </div>

                    <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <p className="text-sm font-medium text-gray-600 mb-3">Créé le</p>
                        <p className="text-sm text-gray-600">
                            {formatDate(classroomType.created_at)}
                        </p>
                        <p className="text-sm font-medium text-gray-600 mt-4 mb-2">Dernière modification</p>
                        <p className="text-sm text-gray-600">
                            {formatDate(classroomType.updated_at)}
                        </p>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100">
                        <h2 className="text-2xl font-bold text-gray-900">
                            Classes utilisant ce type
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Affichage de toutes les classes assignées à ce type
                        </p>
                    </div>

                    {classroomType.classrooms.length === 0 ? (
                        <div className="p-12 flex items-center justify-center">
                            <div className="text-center">
                                <AlertCircle className="w-12 h-12 text-gray-300 mx-auto mb-2" />
                                <p className="text-lg text-gray-500">
                                    Aucune classe n'utilise ce type pour le moment
                                </p>
                                <Button
                                    className="mt-4 bg-blue-600 hover:bg-blue-700"
                                    onClick={() => router.visit(route('classrooms.index'))}
                                >
                                    Créer une classe
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader className="bg-gray-50">
                                    <TableRow className="border-b border-gray-200">
                                        <TableHead className="font-semibold text-gray-900">
                                            Nom
                                        </TableHead>
                                        <TableHead className="font-semibold text-gray-900">
                                            Code
                                        </TableHead>
                                        <TableHead className="font-semibold text-gray-900">
                                            Capacité
                                        </TableHead>
                                        <TableHead className="font-semibold text-gray-900">
                                            État
                                        </TableHead>
                                        <TableHead className="text-center font-semibold text-gray-900">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {classroomType.classrooms.map((classroom) => (
                                        <TableRow
                                            key={classroom.id}
                                            className="border-b border-gray-100 hover:bg-blue-50/40 transition-colors"
                                        >
                                            <TableCell className="font-semibold text-gray-900">
                                                {classroom.name}
                                            </TableCell>
                                            <TableCell className="text-gray-600">
                                                <code className="bg-gray-100 px-2 py-1 rounded text-sm">
                                                    {classroom.code}
                                                </code>
                                            </TableCell>
                                            <TableCell className="text-gray-600">
                                                {classroom.capacity} places
                                            </TableCell>
                                            <TableCell className="text-gray-600">
                                                <span
                                                    className={`inline-flex items-center px-2 py-1 rounded-full text-sm font-medium ${
                                                        classroom.active
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-gray-100 text-gray-800'
                                                    }`}
                                                >
                                                    {classroom.active ? '✓ Active' : 'Inactive'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                                    onClick={() =>
                                                        router.visit(
                                                            route('classrooms.show', classroom.id)
                                                        )
                                                    }
                                                >
                                                    Voir
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
