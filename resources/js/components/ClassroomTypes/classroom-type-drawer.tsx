import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { CreateDrawer } from '@/components/create-drawer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { route } from '@/helpers/route';
import { Textarea } from '@/components/ui/textarea';

interface ClassroomTypeData {
    id?: string;
    name: string;
    description: string | null;
    active: boolean;
}

interface ClassroomTypeDrawerProps {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    classroomType?: ClassroomTypeData | null;
    onSuccess?: () => void;
}

export function ClassroomTypeDrawer({
    isOpen,
    onOpenChange,
    classroomType,
    onSuccess,
}: Readonly<ClassroomTypeDrawerProps>) {
    const isEditing = !!classroomType?.id;

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: classroomType?.name || '',
        description: classroomType?.description || '',
        active: classroomType?.active ?? true,
    });

    useEffect(() => {
        if (isOpen && classroomType?.id) {
            setData({
                name: classroomType.name,
                description: classroomType.description || '',
                active: classroomType.active,
            });
        } else if (isOpen && !classroomType?.id) {
            reset();
        }
    }, [classroomType?.id, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditing && classroomType?.id) {
            put(route('classroom-types.update', classroomType.id), {
                onSuccess: () => {
                    reset();
                    onOpenChange(false);
                    onSuccess?.();
                },
            });
        } else {
            post(route('classroom-types.store'), {
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
            title={isEditing ? `Éditer: ${classroomType?.name}` : 'Nouveau type de classe'}
            description={
                isEditing
                    ? 'Modifiez les informations du type de classe'
                    : 'Complétez les informations pour créer un nouveau type de classe'
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
                        placeholder="Ex: Classe générale"
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
                        htmlFor="description"
                        className="block text-sm font-semibold text-gray-900"
                    >
                        Description
                    </label>
                    <Textarea
                        id="description"
                        placeholder="Description du type de classe..."
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        disabled={processing}
                        rows={3}
                        className="border-gray-300 bg-gray-50 focus:bg-white"
                    />
                    {errors.description && (
                        <p className="text-sm text-red-600 flex items-center gap-1">
                            ❌ {errors.description}
                        </p>
                    )}
                </div>

                <div className="flex items-center gap-3">
                    <input
                        id="active"
                        type="checkbox"
                        checked={data.active}
                        onChange={(e) => setData('active', e.target.checked)}
                        disabled={processing}
                        className="h-4 w-4 rounded border-gray-300 text-blue-600"
                    />
                    <label htmlFor="active" className="text-sm font-medium text-gray-700 cursor-pointer">
                        Actif
                    </label>
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
