
import { LayoutGrid, LogOut, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Link, usePage } from '@inertiajs/react';
import { FC } from 'react';
import { SharedData } from '@/types';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';

interface User {
    id?: string;
    name?: string;
    email?: string;
  }
  
  interface UserNavProps {
    user: User;
  }
  

  export const UserNav: FC<UserNavProps> = () => {
     const { auth } = usePage<SharedData>().props;
         const cleanup = useMobileNavigation();
  return (
    <DropdownMenu>
      <TooltipProvider disableHoverableContent>
        <Tooltip delayDuration={100}>
          <TooltipTrigger asChild>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" className="relative h-8 w-8 rounded-full">
                <Avatar className="h-8 w-8">
                  <AvatarImage src="#" alt="Avatar" />
                  <AvatarFallback className="bg-transparent">
                    {' '}
                    {auth?.user?.name ? auth?.user.name.charAt(0).toUpperCase() : ''}
                  </AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
          </TooltipTrigger>
          <TooltipContent side="bottom">Profile</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <DropdownMenuContent className="w-56" align="end" forceMount>
        <DropdownMenuLabel className="font-normal">
          <div className="flex flex-col space-y-1">
            <p className="text-sm font-medium leading-none">{auth?.user?.name}</p>
            <p className="text-xs leading-none text-muted-foreground">{auth?.user?.email}</p>
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
          <DropdownMenuItem className="hover:cursor-pointer" asChild>
            <Link href="/dashboard" className="flex items-center">
              <LayoutGrid className="w-4 h-4 mr-3 text-muted-foreground" />
              Dashboard
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem className="hover:cursor-pointer" asChild>
            <Link href="/account" className="flex items-center">
              <User className="w-4 h-4 mr-3 text-muted-foreground" />
              Account
            </Link>
          </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuItem className="hover:cursor-pointer" asChild>
  <Link 
    method="post" 
    href={route('logout')} 
    onClick={cleanup}
    className="flex w-full items-center"
  >
    <LogOut className="w-4 h-4 mr-3 text-muted-foreground" />
    Log out
  </Link>
</DropdownMenuItem>
       
        {/* <DropdownMenuItem className="hover:cursor-pointer" onClick={cleanup}>
          <LogOut className="w-4 h-4 mr-3 text-muted-foreground" />
          Log out
           <Link method="post" href={route('logout')} >

                </Link>
        </DropdownMenuItem> */}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
