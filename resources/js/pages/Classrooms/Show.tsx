import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Layers, Tag, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

interface ClassroomType {
    id: string;
    name: string;
}

interface Student {
    id: string;
    firstname: string;
    lastname: string;
    matricule: string;
}

interface Classroom {
    id: string;
    name: string;
    code: string;
    capacity: number;
    type?: ClassroomType | null;
    students?: Student[];
    created_at: string;
    updated_at: string;
}

interface ShowProps {
    classroom: Classroom;
}

export default function Show({ classroom }: Readonly<ShowProps>) {
    return (
        <AppLayout>
            <Head title={classroom.name} />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('classrooms.index')}>
                        <Button variant="ghost" size="sm" className="p-2">
                            <ArrowLeft className="w-4 h-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">
                            {classroom.name}
                        </h1>
                        <p className="text-gray-600 mt-2">
                            Code: <span className="font-semibold">{classroom.code}</span>
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white rounded-lg border p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Informations générales
                        </h2>
                        <div className="space-y-4">
                            <div>
                                <p className="text-sm text-gray-600">Code</p>
                                <p className="text-lg font-medium text-gray-900">
                                    {classroom.code}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Capacité</p>
                                <p className="text-lg font-medium text-gray-900">
                                    {classroom.capacity}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Type de classe</p>
                                {classroom.type ? (
                                    <p className="text-lg font-medium text-gray-900 flex items-center gap-2">
                                        <Tag className="w-4 h-4 text-blue-600" />
                                        {classroom.type.name}
                                    </p>
                                ) : (
                                    <p className="text-lg font-medium text-gray-400">—</p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg border p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Événements
                        </h2>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-600">Créée le</p>
                                <p className="text-sm font-medium text-gray-900">
                                    {new Date(classroom.created_at).toLocaleDateString('fr-FR', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Modifiée le</p>
                                <p className="text-sm font-medium text-gray-900">
                                    {new Date(classroom.updated_at).toLocaleDateString('fr-FR', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="bg-blue-50 rounded-lg p-6 flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                        <Users className="h-6 w-6 text-blue-600" />
                    </div>
                    <div>
                        <p className="text-sm text-blue-700">Nombre d'élèves</p>
                        <p className="text-2xl font-semibold text-blue-900">
                            {classroom.students?.length || 0} / {classroom.capacity}
                        </p>
                    </div>
                </div>

                {classroom.students && classroom.students.length > 0 && (
                    <div className="bg-white rounded-lg border p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Élèves inscrits ({classroom.students.length})
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {classroom.students.map((student) => (
                                <div key={student.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                        <Users className="h-5 w-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {student.firstname} {student.lastname}
                                        </p>
                                        <p className="text-sm text-gray-500">{student.matricule}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
