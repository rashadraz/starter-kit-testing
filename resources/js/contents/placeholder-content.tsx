
import { Card, CardContent } from '@/components/ui/card';
import { ReactNode } from 'react';
interface PlaceholderContentProps {
    children: ReactNode;
  }

export default function PlaceholderContent({ children }:PlaceholderContentProps) {
  return (
    <Card className="rounded-lg border-none mt-6 ">
    <CardContent>
      <div 
      // className="flex justify-center items-center min-h-[calc(100vh-56px-64px-20px-24px-56px-48px)] "
      >
        <div className="flex flex-col relative"> {children}</div>
      </div>
    </CardContent>
  </Card>
  );
}
