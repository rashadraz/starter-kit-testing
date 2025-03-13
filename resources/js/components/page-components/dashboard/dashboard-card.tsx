import { ReactNode } from "react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";

interface DashboardCardProps {
  title: string;
  icon: ReactNode;
  value: string | number;
  description: string;
  color?: string; // Text color for the description
}

const DashboardCard: React.FC<DashboardCardProps> = ({ title, icon, value, description, color }) => {
  return (
    <Card className="transform transition-all duration-300 hover:scale-105 hover:shadow-lg h-[180px]">
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 min-h-[48px]">
        <CardTitle className="text-sm font-medium tracking-wider text-gray-600 uppercase dark:text-white">
          {title}
        </CardTitle>
        {icon}
      </CardHeader>
      <CardContent>
        <div className="flex flex-col">
          <div className="text-3xl font-bold text-gray-900 dark:text-white">{value}</div>
          <p className={`mt-1 text-xs ${color || "text-gray-500"} dark:${color || "text-white"}`}>
            {description}
          </p>
        </div>
      </CardContent>
    </Card>
  );
};

export default DashboardCard;
