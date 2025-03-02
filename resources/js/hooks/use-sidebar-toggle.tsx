import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';

interface SideBarState{
    isOpen:boolean;
    setIsOpen:()=>void;
}
export const useSidebarToggle = create<SideBarState>()(
  persist(
    (set, get) => ({
      isOpen: true,
      setIsOpen: () => {
        set({ isOpen: !get().isOpen });
      }
    }),
    {
      name: 'sidebarOpen',
      storage: createJSONStorage(() => localStorage)
    }
  )
);
