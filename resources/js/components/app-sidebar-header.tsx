import { usePage } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { getFullName } from '@/hooks/use-initials';

export function AppSidebarHeader() {
    const { auth } = usePage().props;
    const fullName = getFullName(auth.user.firstname, auth.user.lastname);

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 bg-gray-50 px-6 dark:bg-gray-900">
            <div className="flex items-center gap-4">
                <SidebarTrigger className="-ml-1" />
            </div>

            <div className="flex items-center gap-4">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            className="relative h-10 w-10 rounded-full"
                        >
                            <Avatar className="h-10 w-10">
                                <AvatarImage
                                    src={auth.user.avatar}
                                    alt={fullName}
                                />
                                <AvatarFallback className="bg-blue-600 text-white">
                                    {fullName.split(' ').map(n => n[0]).join('')}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-56" align="end">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
