import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { CreateDrawer } from '@/components/create-drawer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { route } from '@/helpers/route';
import { Textarea } from '@/components/ui/textarea';

interface SchoolData {
    id?: string;
    name: string;
    code: string;
    email: string | null;
    phone: string | null;
    address: string | null;
}

interface SchoolDrawerProps {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    school?: SchoolData | null;
    onSuccess?: () => void;
}

export function SchoolDrawer({
    isOpen,
    onOpenChange,
    school,
    onSuccess,
}: Readonly<SchoolDrawerProps>) {
    const isEditing = !!school?.id;

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: school?.name || '',
        code: school?.code || '',
        email: school?.email || '',
        phone: school?.phone || '',
        address: school?.address || '',
    });

    // Réinitialiser le formulaire quand school change ou le drawer s'ouvre
    useEffect(() => {
        if (isOpen && school?.id) {
            setData({
                name: school.name,
                code: school.code,
                email: school.email || '',
                phone: school.phone || '',
                address: school.address || '',
            });
        } else if (isOpen && !school?.id) {
            reset();
        }
    }, [school?.id, isOpen]);

    const handleSubmit = (e: React.SubmitEvent) => {
        e.preventDefault();

        if (isEditing && school?.id) {
            put(route('schools.update', school.id), {
                onSuccess: () => {
                    reset();
                    onOpenChange(false);
                    onSuccess?.();
                },
            });
        } else {
            post(route('schools.store'), {
                onSuccess: () => {
                    reset();
                    onOpenChange(false);
                    onSuccess?.();
                },
            });
        }
    };

    const handleOpenChange = (open: boolean) => {
        onOpenChange(open);
        if (!open) {
            reset();
        }
    };

    return (
        <CreateDrawer
            isOpen={isOpen}
            onOpenChange={handleOpenChange}
            title={isEditing ? `Éditer: ${school?.name}` : 'Nouvelle école'}
            description={
                isEditing
                    ? 'Modifiez les informations de l\'école'
                    : 'Complétez les informations pour créer une nouvelle école'
            }
        >
            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Name */}
                <div className="space-y-3">
                    <label
                        htmlFor="name"
                        className="block text-sm font-semibold text-gray-900"
                    >
                        Nom *
                    </label>
                    <Input
                        id="name"
                        type="text"
                        placeholder="Ex: Lycée Jean Jaurès"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        disabled={processing}
                        className="h-10 border-gray-300 bg-gray-50 focus:bg-white"
                    />
                    {errors.name && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.name}
                        </p>
                    )}
                </div>

                {/* Code */}
                <div className="space-y-3">
                    <label
                        htmlFor="code"
                        className="block text-sm font-semibold text-gray-900"
                    >
                        Code *
                    </label>
                    <Input
                        id="code"
                        type="text"
                        placeholder="Ex: LJJ001"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                        disabled={processing}
                        className="h-10 border-gray-300 bg-gray-50 focus:bg-white"
                    />
                    {errors.code && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.code}
                        </p>
                    )}
                </div>

                {/* Email */}
                <div className="space-y-3">
                    <label
                        htmlFor="email"
                        className="block text-sm font-semibold text-gray-900"
                    >
                        E-mail
                    </label>
                    <Input
                        id="email"
                        type="email"
                        placeholder="contact@ecole.com"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        disabled={processing}
                        className="h-10 border-gray-300 bg-gray-50 focus:bg-white"
                    />
                    {errors.email && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.email}
                        </p>
                    )}
                </div>

                {/* Phone */}
                <div className="space-y-3">
                    <label
                        htmlFor="phone"
                        className="block text-sm font-semibold text-gray-900"
                    >
                        Téléphone
                    </label>
                    <Input
                        id="phone"
                        type="tel"
                        placeholder="+33 1 23 45 67 89"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        disabled={processing}
                        className="h-10 border-gray-300 bg-gray-50 focus:bg-white"
                    />
                    {errors.phone && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.phone}
                        </p>
                    )}
                </div>

                {/* Address */}
                <div className="space-y-3">
                    <label
                        htmlFor="address"
                        className="block text-sm font-semibold text-gray-900"
                    >
                        Adresse
                    </label>
                    <Textarea
                        id="address"
                        placeholder="123 Rue de l'École, 75000 Paris"
                        value={data.address}
                        onChange={(e) =>
                            setData('address', e.target.value)
                        }
                        disabled={processing}
                        rows={3}
                        className="border-gray-300 bg-gray-50 focus:bg-white"
                    />
                    {errors.address && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.address}
                        </p>
                    )}
                </div>

                {/* Submit Button */}
                <div className="flex gap-3 pt-6 border-t border-gray-200">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => handleOpenChange(false)}
                        disabled={processing}
                        className="flex-1 border-gray-300 text-gray-700 hover:bg-gray-50"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white"
                    >
                        {processing
                            ? isEditing
                                ? 'Mise à jour...'
                                : 'Création...'
                            : isEditing
                              ? 'Mettre à jour'
                              : 'Créer'}
                    </Button>
                </div>
            </form>
        </CreateDrawer>
    );
}
