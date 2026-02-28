import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, Mail, MapPin, Phone, User as UserIcon, Shield } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

interface Permission {
    id: string;
    name: string;
    description: string | null;
}

interface Role {
    id: string;
    name: string;
    description: string | null;
    permissions: Permission[];
}

interface User {
    id: string;
    firstname: string;
    lastname: string;
    email: string;
    gender: string;
    birth_date: string | null;
    telephone: string | null;
    address: string | null;
    profile: string | null;
    natricule: string | null;
    roles: Role[];
    created_at: string;
    updated_at: string;
}

interface ShowProps {
    user: User;
}

export default function Show({ user }: Readonly<ShowProps>) {
    const formatDate = (date: string | null) => {
        if (!date) return 'Non renseigné';
        return new Date(date).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    return (
        <AppLayout>
            <Head title={`${user.firstname} ${user.lastname}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <button
                            onClick={() => router.get(route('users.index'))}
                            className="p-2 hover:bg-gray-100 rounded-lg transition"
                        >
                            <ArrowLeft className="w-5 h-5 text-gray-600" />
                        </button>
                        <div className="flex items-center gap-4">
                            <div className="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center">
                                <UserIcon className="w-8 h-8 text-white" />
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">
                                    {user.firstname} {user.lastname}
                                </h1>
                                {user.natricule && (
                                    <span className="inline-block mt-1 px-3 py-1 bg-gray-100 text-gray-700 text-sm font-medium rounded-full">
                                        {user.natricule}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    <Button
                        onClick={() => router.get(route('users.edit', user.id))}
                        className="bg-blue-600 hover:bg-blue-700"
                    >
                        Modifier
                    </Button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Colonne 1 & 2: Informations */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Informations personnelles */}
                        <div className="bg-white rounded-lg border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <UserIcon className="w-5 h-5 text-blue-600" />
                                Informations personnelles
                            </h2>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Prénom</p>
                                    <p className="font-medium text-gray-900 mt-1">{user.firstname}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Nom</p>
                                    <p className="font-medium text-gray-900 mt-1">{user.lastname}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Genre</p>
                                    <p className="font-medium text-gray-900 mt-1">
                                        {user.gender === 'M' ? 'Masculin' : 'Féminin'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 flex items-center gap-1">
                                        <Calendar className="w-4 h-4" />
                                        Date de naissance
                                    </p>
                                    <p className="font-medium text-gray-900 mt-1">{formatDate(user.birth_date)}</p>
                                </div>
                            </div>
                        </div>

                        {/* Coordonnées */}
                        <div className="bg-white rounded-lg border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <Mail className="w-5 h-5 text-blue-600" />
                                Coordonnées
                            </h2>
                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm text-gray-600 flex items-center gap-1">
                                        <Mail className="w-4 h-4" />
                                        Email
                                    </p>
                                    <p className="font-medium text-gray-900 mt-1">{user.email}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 flex items-center gap-1">
                                        <Phone className="w-4 h-4" />
                                        Téléphone
                                    </p>
                                    <p className="font-medium text-gray-900 mt-1">
                                        {user.telephone || 'Non renseigné'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 flex items-center gap-1">
                                        <MapPin className="w-4 h-4" />
                                        Adresse
                                    </p>
                                    <p className="font-medium text-gray-900 mt-1">
                                        {user.address || 'Non renseignée'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Profil */}
                        {user.profile && (
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Profil</h2>
                                <p className="text-gray-700 whitespace-pre-wrap">{user.profile}</p>
                            </div>
                        )}

                        {/* Timeline */}
                        <div className="bg-white rounded-lg border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Historique</h2>
                            <div className="space-y-3">
                                <div className="flex items-start gap-3">
                                    <div className="w-2 h-2 bg-blue-600 rounded-full mt-2"></div>
                                    <div>
                                        <p className="text-sm text-gray-600">Créé le</p>
                                        <p className="font-medium text-gray-900">{formatDate(user.created_at)}</p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="w-2 h-2 bg-gray-400 rounded-full mt-2"></div>
                                    <div>
                                        <p className="text-sm text-gray-600">Dernière modification</p>
                                        <p className="font-medium text-gray-900">{formatDate(user.updated_at)}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Colonne 3: Rôles et Permissions */}
                    <div className="lg:col-span-1 space-y-6">
                        {/* Rôles */}
                        <div className="bg-white rounded-lg border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <Shield className="w-5 h-5 text-blue-600" />
                                Rôles ({user.roles.length})
                            </h2>
                            {user.roles.length > 0 ? (
                                <div className="space-y-2">
                                    {user.roles.map((role) => (
                                        <div key={role.id} className="p-3 bg-blue-50 rounded-lg border border-blue-100">
                                            <p className="font-medium text-blue-900">{role.name}</p>
                                            {role.description && (
                                                <p className="text-sm text-blue-700 mt-1">{role.description}</p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-sm">Aucun rôle attribué</p>
                            )}
                        </div>

                        {/* Permissions */}
                        {user.roles.length > 0 && (
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Permissions
                                </h2>
                                <div className="space-y-4">
                                    {user.roles.map((role) => (
                                        <div key={role.id}>
                                            <p className="text-sm font-medium text-gray-900 mb-2">{role.name}</p>
                                            {role.permissions.length > 0 ? (
                                                <div className="space-y-1">
                                                    {role.permissions.map((permission) => (
                                                        <div key={permission.id} className="flex items-center gap-2 text-sm">
                                                            <div className="w-1.5 h-1.5 bg-blue-600 rounded-full"></div>
                                                            <span className="text-gray-700">{permission.name}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <p className="text-gray-500 text-sm ml-4">Aucune permission</p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
