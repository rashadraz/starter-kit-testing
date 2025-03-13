import { MenuGroup } from '@/types';
import { ChartColumnIncreasing, LayoutGrid, Settings, Users } from 'lucide-react';

export function getMenuList(pathname: string): MenuGroup[] {
    return [
        {
            groupLabel: '',
            menus: [
                {
                    href: '/dashboard',
                    label: 'Dashboard',
                    active: pathname.includes('/dashboard'),
                    icon: LayoutGrid,
                    submenus: [],
                },
                {
                  href: '/orders',
                  label: 'Orders',
                  active: pathname.includes('/orders'),
                  icon: ChartColumnIncreasing,
                  submenus: [],
              },
            ],
        },
        {
            groupLabel: 'Dispatcher',
            menus: [],
        },
        {
            groupLabel: 'Settings',
            menus: [
                {
                    href: '/users',
                    label: 'Users',
                    active: pathname.includes('/users'),
                    icon: Users,
                    submenus: [],
                },
                {
                    href: '/account',
                    label: 'Account',
                    active: pathname.includes('/account'),
                    icon: Settings,
                    submenus: [],
                },
            ],
        },
    ];
}
