import { cn } from '@/lib/utils';
import { useStore } from '@/hooks/use-store';
import { useSidebarToggle } from '@/hooks/use-sidebar-toggle';
import { ReactNode } from 'react';
import { Footer } from '../footer/footer';
import { Sidebar } from '@/components/layout/sidebar';

interface AdminPanelLayoutProps {
    children: ReactNode;
  }
  
  interface SidebarState {
    isOpen: boolean;
  }

export default function AdminPanelLayout({ children }: AdminPanelLayoutProps) {
  const sidebar = useStore<SidebarState,SidebarState | undefined>(useSidebarToggle, (state) => state);

  if (!sidebar) return null;

  return (
    <>
      <Sidebar />
      <main
        className={cn(
          'min-h-[calc(100vh_-_56px)] bg-zinc-50 dark:bg-zinc-900 transition-[margin-left] ease-in-out duration-300',
          sidebar?.isOpen === false ? 'lg:ml-[90px]' : 'lg:ml-72'
        )}>
        {children}
      </main>
      <footer
        className={cn(
          'transition-[margin-left] ease-in-out duration-300',
          sidebar?.isOpen === false ? 'lg:ml-[90px]' : 'lg:ml-72'
        )}>
        <Footer />
      </footer>
    </>
  );
}
