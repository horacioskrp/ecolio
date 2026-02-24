import { useState, type ReactNode } from 'react';

interface FormDrawerHandlers<T> {
    onOpenCreate: () => void;
    onOpenEdit: (item: T) => void;
}

interface FormDrawerRenderProps<T> {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    selectedItem: T | null;
    onSuccess?: () => void;
}

interface FormDrawerProps<T> {
    children: (handlers: FormDrawerHandlers<T>) => ReactNode;
    renderDrawer: (props: FormDrawerRenderProps<T>) => ReactNode;
    onSuccess?: () => void;
}

export function FormDrawer<T>({
    children,
    renderDrawer,
    onSuccess,
}: Readonly<FormDrawerProps<T>>) {
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [selectedItem, setSelectedItem] = useState<T | null>(null);

    const handleOpenCreateDrawer = () => {
        setSelectedItem(null);
        setIsDrawerOpen(true);
    };

    const handleOpenEditDrawer = (item: T) => {
        setSelectedItem(item);
        setIsDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setIsDrawerOpen(false);
        setSelectedItem(null);
    };

    return (
        <>
            {children({
                onOpenCreate: handleOpenCreateDrawer,
                onOpenEdit: handleOpenEditDrawer,
            })}

            {renderDrawer({
                isOpen: isDrawerOpen,
                onOpenChange: handleCloseDrawer,
                selectedItem,
                onSuccess,
            })}
        </>
    );
}
