import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CreateDrawer } from '@/components/create-drawer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { route } from '@/helpers/route';
import { Search } from 'lucide-react';

interface ClassroomType {
    id: string;
    name: string;
}

interface ClassroomData {
    id?: string;
    name: string;
    code: string;
    capacity: number;
    classroom_type_id?: string | null;
    type?: ClassroomType | null;
}

interface ClassroomDrawerProps {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    classroom?: ClassroomData | null;
    onSuccess?: () => void;
    classroomTypes?: ClassroomType[];
}

export function ClassroomDrawer({
    isOpen,
    onOpenChange,
    classroom,
    onSuccess,
    classroomTypes = [],
}: Readonly<ClassroomDrawerProps>) {
    const isEditing = !!classroom?.id;
    const [searchTerm, setSearchTerm] = useState('');
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: classroom?.name || '',
        code: classroom?.code || '',
        capacity: classroom?.capacity ?? 40,
        classroom_type_id: classroom?.classroom_type_id || classroom?.type?.id || '',
    });

    const filteredTypes = classroomTypes.filter((type) =>
        type.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const selectedType = classroomTypes.find((type) => type.id === data.classroom_type_id);

    useEffect(() => {
        if (isOpen && classroom?.id) {
            setData({
                name: classroom.name,
                code: classroom.code,
                capacity: classroom.capacity ?? 40,
                classroom_type_id: classroom.classroom_type_id || classroom.type?.id || '',
            });
        } else if (isOpen && !classroom?.id) {
            reset();
            setSearchTerm('');
        }
    }, [classroom?.id, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditing && classroom?.id) {
            put(route('classrooms.update', classroom.id), {
                onSuccess: () => {
                    reset();
                    onOpenChange(false);
                    onSuccess?.();
                },
            });
        } else {
            post(route('classrooms.store'), {
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
            setSearchTerm('');
        }
    };

    return (
        <CreateDrawer
            isOpen={isOpen}
            onOpenChange={handleOpenChange}
            title={isEditing ? `Éditer: ${classroom?.name}` : 'Nouvelle classe'}
            description={
                isEditing
                    ? 'Modifiez les informations de la classe'
                    : 'Complétez les informations pour créer une nouvelle classe'
            }
        >
            <form onSubmit={handleSubmit} className="space-y-6">
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
                        placeholder="Ex: 6e A"
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
                        placeholder="Ex: 6A-2025"
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

                <div className="space-y-3">
                    <label
                        htmlFor="capacity"
                        className="block text-sm font-semibold text-gray-900"
                    >
                        Capacité *
                    </label>
                    <Input
                        id="capacity"
                        type="number"
                        min={1}
                        max={200}
                        placeholder="Ex: 40"
                        value={data.capacity}
                        onChange={(e) =>
                            setData(
                                'capacity',
                                e.target.value === '' ? 0 : Number(e.target.value)
                            )
                        }
                        disabled={processing}
                        className="h-10 border-gray-300 bg-gray-50 focus:bg-white"
                    />
                    {errors.capacity && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.capacity}
                        </p>
                    )}
                </div>

                <div className="space-y-3 relative">
                    <label className="block text-sm font-semibold text-gray-900">
                        Type de classe
                    </label>
                    <div className="relative">
                        <button
                            type="button"
                            onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                            disabled={processing}
                            className="w-full h-10 border-gray-300 bg-gray-50 hover:bg-white focus:bg-white disabled:opacity-50 disabled:cursor-not-allowed rounded-md border px-3 py-2 text-left text-sm flex items-center justify-between"
                        >
                            <span className={selectedType ? 'text-gray-900' : 'text-gray-500'}>
                                {selectedType?.name || 'Sélectionner un type...'}
                            </span>
                            <Search className="w-4 h-4 text-gray-400" />
                        </button>

                        {isDropdownOpen && (
                            <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-10">
                                <div className="p-2 border-b border-gray-200">
                                    <input
                                        type="text"
                                        placeholder="Rechercher un type..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div className="max-h-48 overflow-y-auto">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setData('classroom_type_id', '');
                                            setIsDropdownOpen(false);
                                        }}
                                        className="w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-100"
                                    >
                                        — Aucun type
                                    </button>
                                    {filteredTypes.length === 0 ? (
                                        <div className="px-3 py-2 text-sm text-gray-500">
                                            Aucun type trouvé
                                        </div>
                                    ) : (
                                        filteredTypes.map((type) => (
                                            <button
                                                key={type.id}
                                                type="button"
                                                onClick={() => {
                                                    setData('classroom_type_id', type.id);
                                                    setIsDropdownOpen(false);
                                                    setSearchTerm('');
                                                }}
                                                className={`w-full text-left px-3 py-2 text-sm transition-colors ${
                                                    data.classroom_type_id === type.id
                                                        ? 'bg-blue-100 text-blue-900 font-medium'
                                                        : 'text-gray-700 hover:bg-gray-100'
                                                }`}
                                            >
                                                {type.name}
                                            </button>
                                        ))
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                    {errors.classroom_type_id && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.classroom_type_id}
                        </p>
                    )}
                </div>

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
