import { Link, usePage } from '@inertiajs/react';
import { ScanFace, ShieldCheck, Users } from 'lucide-react';
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
import { index as enrollmentsIndex, search as enrollmentsSearch } from '@/routes/enrollments';
import { index as usersIndex } from '@/routes/users';
import type { Auth, NavItem } from '@/types';

type PageProps = {
    auth: Auth;
};

export function AppSidebar() {
    const { auth } = usePage<PageProps>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Enrollments',
            href: enrollmentsIndex(),
            icon: Users,
        },
        {
            title: 'Search by face',
            href: enrollmentsSearch(),
            icon: ScanFace,
        },
        ...(auth.user?.role === 'admin'
            ? [
                  {
                      title: 'Users',
                      href: usersIndex(),
                      icon: ShieldCheck,
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
