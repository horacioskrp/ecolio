import { useState, ReactNode } from 'react';
import { SchoolDrawer } from '@/components/Schools/school-drawer';

interface School {
    id: string;
    name: string;
    code: string;
    email: string | null;
    phone: string | null;
    address: string | null;
}

interface FormDrawerProps {
    children: (handlers: {
        onOpenCreate: () => void;
        onOpenEdit: (school: School) => void;
    }) => ReactNode;
    onSuccess?: () => void;
}

export function FormDrawer({ children, onSuccess }: FormDrawerProps) {
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [selectedSchool, setSelectedSchool] = useState<School | null>(null);

    const handleOpenCreateDrawer = () => {
        setSelectedSchool(null);
        setIsDrawerOpen(true);
    };

    const handleOpenEditDrawer = (school: School) => {
        setSelectedSchool(school);
        setIsDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setIsDrawerOpen(false);
        setSelectedSchool(null);
    };

    return (
        <>
            {children({
                onOpenCreate: handleOpenCreateDrawer,
                onOpenEdit: handleOpenEditDrawer,
            })}

            <SchoolDrawer
                isOpen={isDrawerOpen}
                onOpenChange={handleCloseDrawer}
                school={selectedSchool}
                onSuccess={onSuccess}
            />
        </>
    );
}
