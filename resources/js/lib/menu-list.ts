import { MenuGroup } from '@/types';
import { Users, Settings, LayoutGrid } from 'lucide-react';


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
          submenus: [{
            href: '/dashboard-1',
            label: 'Dispatcher',
            active: pathname.includes('/dispatcher'),
            icon: LayoutGrid,
           
          }]
        }
      ]
    },
    {
      groupLabel: 'Contents',
      menus: []
    },
    {
      groupLabel: 'Settings',
      menus: [
        {
          href: '/users',
          label: 'Users',
          active: pathname.includes('/users'),
          icon: Users,
          submenus: []
        },
        {
          href: '/account',
          label: 'Account',
          active: pathname.includes('/account'),
          icon: Settings,
          submenus: []
        }
      ]
    }
  ];
}