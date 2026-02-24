import { ReactNode } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

interface CreateDrawerProps {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    children: ReactNode;
}

export function CreateDrawer({
    isOpen,
    onOpenChange,
    title,
    description,
    children,
}: CreateDrawerProps) {
    return (
        <Sheet open={isOpen} onOpenChange={onOpenChange}>
            <SheetContent side="right" className="w-full sm:w-[600px] md:w-[700px] p-0 sm:p-0">
                <div className="h-full overflow-y-auto">
                    <div className="sticky top-0 bg-white border-b p-6 sm:p-8">
                        <SheetHeader>
                            <SheetTitle className="text-2xl">{title}</SheetTitle>
                            {description && (
                                <SheetDescription className="text-base">
                                    {description}
                                </SheetDescription>
                            )}
                        </SheetHeader>
                    </div>
                    <div className="p-6 sm:p-8">
                        {children}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
