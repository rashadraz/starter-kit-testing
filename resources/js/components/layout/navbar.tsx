import { SheetMenu } from './sheet-menu';
import { ModeToggle } from './mode-toggle';
import { UserNav } from './user-nav';
import { FC } from 'react';
// import { getCurrentUser } from '@/lib/session';

interface NavbarProps {
    title: string;
  }
  
  interface User {
    // Add user properties here based on your requirements
    // For example:
    id?: string;
    name?: string;
    email?: string;
  }


  export const Navbar: FC<NavbarProps> = ({ title }) => {
  const user:User = {};
  return (
    <header className="sticky top-0 z-10 w-full bg-background/95 shadow backdrop-blur supports-[backdrop-filter]:bg-background/60 dark:shadow-secondary">
      <div className="mx-4 sm:mx-8 flex h-14 items-center">
        <div className="flex items-center space-x-4 lg:space-x-0">
          <SheetMenu />
          <h1 className="font-bold">{title}</h1>
        </div>
        <div className="flex flex-1 items-center space-x-2 justify-end">
          <ModeToggle />
          <UserNav user={user} />
        </div>
      </div>
    </header>
  );
}
