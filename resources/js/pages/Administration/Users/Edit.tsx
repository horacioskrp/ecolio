import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { route } from '@/helpers/route';
import AppLayout from '@/layouts/app-layout';

interface Role {
    id: string;
    name: string;
    description: string | null;
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
    roles: Role[];
}

interface EditProps {
    user: User;
    roles: Role[];
}

export default function Edit({ user, roles }: Readonly<EditProps>) {
    const [formData, setFormData] = useState({
        firstname: user.firstname,
        lastname: user.lastname,
        email: user.email,
        password: '',
        password_confirmation: '',
        gender: user.gender,
        birth_date: user.birth_date || '',
        telephone: user.telephone || '',
        address: user.address || '',
        profile: user.profile || '',
        roles: user.roles.map(role => role.id),
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        router.put(route('users.update', user.id), formData, {
            onError: (errors) => {
                setErrors(errors as Record<string, string>);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        });
    };

    const toggleRole = (roleId: string) => {
        setFormData(prev => ({
            ...prev,
            roles: prev.roles.includes(roleId)
                ? prev.roles.filter(id => id !== roleId)
                : [...prev.roles, roleId]
        }));
    };

    return (
        <AppLayout>
            <Head title={`Modifier ${user.firstname} ${user.lastname}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <button
                        onClick={() => router.get(route('users.index'))}
                        className="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <ArrowLeft className="w-5 h-5 text-gray-600" />
                    </button>
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">
                            Modifier {user.firstname} {user.lastname}
                        </h1>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Colonne 1 & 2: Informations de base */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Informations personnelles */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Informations personnelles</h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="firstname" className="block text-sm font-medium text-gray-900 mb-2">
                                            Prénom *
                                        </label>
                                        <Input
                                            id="firstname"
                                            type="text"
                                            value={formData.firstname}
                                            onChange={(e) => setFormData(prev => ({ ...prev, firstname: e.target.value }))}
                                            placeholder="Ex: Jean"
                                            className={errors.firstname ? 'border-red-500' : ''}
                                        />
                                        {errors.firstname && (
                                            <p className="text-red-600 text-sm mt-1">{errors.firstname}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="lastname" className="block text-sm font-medium text-gray-900 mb-2">
                                            Nom *
                                        </label>
                                        <Input
                                            id="lastname"
                                            type="text"
                                            value={formData.lastname}
                                            onChange={(e) => setFormData(prev => ({ ...prev, lastname: e.target.value }))}
                                            placeholder="Ex: Dupont"
                                            className={errors.lastname ? 'border-red-500' : ''}
                                        />
                                        {errors.lastname && (
                                            <p className="text-red-600 text-sm mt-1">{errors.lastname}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="gender" className="block text-sm font-medium text-gray-900 mb-2">
                                            Genre *
                                        </label>
                                        <select
                                            id="gender"
                                            value={formData.gender}
                                            onChange={(e) => setFormData(prev => ({ ...prev, gender: e.target.value }))}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="M">Masculin</option>
                                            <option value="F">Féminin</option>
                                        </select>
                                        {errors.gender && (
                                            <p className="text-red-600 text-sm mt-1">{errors.gender}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="birth_date" className="block text-sm font-medium text-gray-900 mb-2">
                                            Date de naissance
                                        </label>
                                        <Input
                                            id="birth_date"
                                            type="date"
                                            value={formData.birth_date}
                                            onChange={(e) => setFormData(prev => ({ ...prev, birth_date: e.target.value }))}
                                            className={errors.birth_date ? 'border-red-500' : ''}
                                        />
                                        {errors.birth_date && (
                                            <p className="text-red-600 text-sm mt-1">{errors.birth_date}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Coordonnées */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Coordonnées</h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="email" className="block text-sm font-medium text-gray-900 mb-2">
                                            Email *
                                        </label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={formData.email}
                                            onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
                                            placeholder="exemple@email.com"
                                            className={errors.email ? 'border-red-500' : ''}
                                        />
                                        {errors.email && (
                                            <p className="text-red-600 text-sm mt-1">{errors.email}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="telephone" className="block text-sm font-medium text-gray-900 mb-2">
                                            Téléphone
                                        </label>
                                        <Input
                                            id="telephone"
                                            type="text"
                                            value={formData.telephone}
                                            onChange={(e) => setFormData(prev => ({ ...prev, telephone: e.target.value }))}
                                            placeholder="+228 XX XX XX XX"
                                            className={errors.telephone ? 'border-red-500' : ''}
                                        />
                                        {errors.telephone && (
                                            <p className="text-red-600 text-sm mt-1">{errors.telephone}</p>
                                        )}
                                    </div>

                                    <div className="md:col-span-2">
                                        <label htmlFor="address" className="block text-sm font-medium text-gray-900 mb-2">
                                            Adresse
                                        </label>
                                        <Input
                                            id="address"
                                            type="text"
                                            value={formData.address}
                                            onChange={(e) => setFormData(prev => ({ ...prev, address: e.target.value }))}
                                            placeholder="123 Rue de la Paix"
                                            className={errors.address ? 'border-red-500' : ''}
                                        />
                                        {errors.address && (
                                            <p className="text-red-600 text-sm mt-1">{errors.address}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Sécurité */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Sécurité</h2>
                                <p className="text-sm text-gray-600 mb-4">
                                    Laissez ces champs vides pour conserver le mot de passe actuel.
                                </p>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="password" className="block text-sm font-medium text-gray-900 mb-2">
                                            Nouveau mot de passe
                                        </label>
                                        <Input
                                            id="password"
                                            type="password"
                                            value={formData.password}
                                            onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
                                            placeholder="••••••••"
                                            className={errors.password ? 'border-red-500' : ''}
                                        />
                                        {errors.password && (
                                            <p className="text-red-600 text-sm mt-1">{errors.password}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-900 mb-2">
                                            Confirmer le mot de passe
                                        </label>
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            value={formData.password_confirmation}
                                            onChange={(e) => setFormData(prev => ({ ...prev, password_confirmation: e.target.value }))}
                                            placeholder="••••••••"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Profil */}
                            <div className="bg-white rounded-lg border p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Profil</h2>
                                <div>
                                    <label htmlFor="profile" className="block text-sm font-medium text-gray-900 mb-2">
                                        Description du profil
                                    </label>
                                    <textarea
                                        id="profile"
                                        value={formData.profile}
                                        onChange={(e) => setFormData(prev => ({ ...prev, profile: e.target.value }))}
                                        placeholder="Décrivez le profil de l'utilisateur..."
                                        rows={4}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                    {errors.profile && (
                                        <p className="text-red-600 text-sm mt-1">{errors.profile}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Colonne 3: Rôles */}
                        <div className="lg:col-span-1">
                            <div className="bg-white rounded-lg border p-6 sticky top-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Rôles</h2>
                                <div className="space-y-3">
                                    {roles.map((role) => (
                                        <label
                                            key={role.id}
                                            className="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={formData.roles.includes(role.id)}
                                                onChange={() => toggleRole(role.id)}
                                                className="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                            />
                                            <div className="flex-1">
                                                <p className="font-medium text-gray-900">{role.name}</p>
                                                {role.description && (
                                                    <p className="text-sm text-gray-500 mt-1">{role.description}</p>
                                                )}
                                            </div>
                                        </label>
                                    ))}
                                </div>
                                {errors.roles && (
                                    <p className="text-red-600 text-sm mt-2">{errors.roles}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Boutons */}
                    <div className="flex gap-3">
                        <Button
                            type="submit"
                            disabled={isSubmitting}
                            className="bg-blue-600 hover:bg-blue-700"
                        >
                            {isSubmitting ? 'Mise à jour...' : 'Mettre à jour'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.get(route('users.index'))}
                        >
                            Annuler
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
