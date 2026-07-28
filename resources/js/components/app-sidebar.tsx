import { Link } from '@inertiajs/react';
import { ScanFace, Shield, ShieldCheck, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCan } from '@/hooks/use-can';
import { index as enrollmentsIndex, search as enrollmentsSearch } from '@/routes/enrollments';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const can = useCan();

    const mainNavItems: NavItem[] = [
        ...(can('enrollments.view')
            ? [
                  {
                      title: 'Enrollments',
                      href: enrollmentsIndex(),
                      icon: Users,
                  } satisfies NavItem,
              ]
            : []),
        ...(can('enrollments.search')
            ? [
                  {
                      title: 'Search by face',
                      href: enrollmentsSearch(),
                      icon: ScanFace,
                  } satisfies NavItem,
              ]
            : []),
        ...(can('users.view')
            ? [
                  {
                      title: 'Users',
                      href: usersIndex(),
                      icon: ShieldCheck,
                  } satisfies NavItem,
              ]
            : []),
        ...(can('roles.view')
            ? [
                  {
                      title: 'Roles',
                      href: rolesIndex(),
                      icon: Shield,
                  } satisfies NavItem,
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={enrollmentsIndex()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
