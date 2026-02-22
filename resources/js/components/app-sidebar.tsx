import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { mainNavItems } from '@/types';
import AppLogo from './app-logo';

export function AppSidebar() {
    return (
        <Sidebar 
            collapsible="icon" 
            variant="inset"
            className="bg-blue-600 text-white [&_[data-sidebar=sidebar]]:bg-blue-600 [&_[data-sidebar=sidebar]]:text-white [&_[data-sidebar=header]]:bg-blue-600 [&_[data-sidebar=content]]:bg-blue-600"
        >
            <SidebarHeader className="px-3 py-4">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton 
                            size="sm" 
                            asChild
                            className="text-white hover:bg-blue-500/30"
                        >
                            <div>
                                <div className="flex items-center gap-2 [&_svg]:text-white">
                                    <AppLogo />
                                </div>
                            </div>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="[&_[data-sidebar=menu]]:text-white pt-2">
                <NavMain items={mainNavItems} />
            </SidebarContent>
        </Sidebar>
    );
}
