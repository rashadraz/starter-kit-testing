import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';

import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent
} from '@/components/ui/chart';

const chartConfig = {
  value: {
    label: 'Count',
    color: 'var(--chart-1)'
  }
} satisfies ChartConfig;

interface BarChartData {
    label: string;
    value: number;
    fill?: string; 
  }
  
  // Define the props type
  interface BarGraphProps {
    data: BarChartData[];
    title: string;
  }

export function BarGraph({ data, title }:BarGraphProps) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className='flex justify-center'>{title}</CardTitle>
        </CardHeader>
        <CardContent className="px-2 sm:p-6">
          <ChartContainer
            config={chartConfig}
            className="aspect-auto h-[280px] w-full"
          >
            <BarChart
              data={data}
              margin={{ left: 12, right: 12, top: 12, bottom: 12 }}
            >
              <CartesianGrid vertical={false} />
              <XAxis dataKey="label" tickLine={false} axisLine={false} tickMargin={8} />
              <ChartTooltip content={<ChartTooltipContent className="w-[150px]" nameKey="label" />} />
              <Bar dataKey="value"  />
            </BarChart>
          </ChartContainer>
        </CardContent>
      </Card>
    );
  }