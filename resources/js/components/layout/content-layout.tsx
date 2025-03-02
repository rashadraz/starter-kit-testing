import { FC, ReactNode } from 'react';
import { Navbar } from './navbar';

interface ContentLayoutProps {
  title: string;
  children: ReactNode;
}

export const ContentLayout: FC<ContentLayoutProps> = ({ title, children }) => {
  return (
    <div>
      <Navbar title={title} />
      <div className=" pt-8 pb-8 px-4 sm:px-8">{children}</div>
    </div>
  );
};