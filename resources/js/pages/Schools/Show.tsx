import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

interface School {
    id: string;
    name: string;
    code: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    created_at: string;
    updated_at: string;
}

interface ShowProps {
    school: School;
}

export default function Show({ school }: Readonly<ShowProps>) {
    return (
        <AppLayout>
            <Head title={school.name} />

            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div className="flex items-center gap-4">
                        <Link href={route('schools.index')}>
                            <Button variant="ghost" size="sm" className="p-2">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                {school.name}
                            </h1>
                            <p className="text-gray-600 mt-2">
                                Code: <span className="font-semibold">{school.code}</span>
                            </p>
                        </div>
                    </div>
                    <Link href={route('schools.edit', school.id)}>
                        <Button className="gap-2">
                            <Pencil className="w-4 h-4" />
                            Éditer
                        </Button>
                    </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* General Info */}
                    <div className="bg-white rounded-lg border p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Informations générales
                        </h2>
                        <div className="space-y-4">
                            <div>
                                <p className="text-sm text-gray-600">Code</p>
                                <p className="text-lg font-medium text-gray-900">
                                    {school.code}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">E-mail</p>
                                <p className="text-lg font-medium text-gray-900">
                                    {school.email || '-'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">
                                    Téléphone
                                </p>
                                <p className="text-lg font-medium text-gray-900">
                                    {school.phone || '-'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Address & Timeline */}
                    <div className="space-y-6">
                        {/* Address */}
                        {school.address && (
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Adresse
                                </h2>
                                <p className="text-gray-700 whitespace-pre-wrap">
                                    {school.address}
                                </p>
                            </div>
                        )}

                        {/* Timeline */}
                        <div className="bg-white rounded-lg border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                Événements
                            </h2>
                            <div className="space-y-3">
                                <div>
                                    <p className="text-sm text-gray-600">
                                        Créée le
                                    </p>
                                    <p className="text-sm font-medium text-gray-900">
                                        {new Date(
                                            school.created_at
                                        ).toLocaleDateString('fr-FR', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">
                                        Modifiée le
                                    </p>
                                    <p className="text-sm font-medium text-gray-900">
                                        {new Date(
                                            school.updated_at
                                        ).toLocaleDateString('fr-FR', {
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
                </div>
            </div>
        </AppLayout>
    );
}
