import * as React from 'react';
import { Label, Pie, PieChart } from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartContainer, ChartLegend, ChartLegendContent, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';

interface PieGraphProps {
    title: string;
    data: { label: string; value: number; fill: string }[];
    totalLabel: string;
}

export function PieGraph({ title, data, totalLabel }: PieGraphProps) {
    const [hiddenLabels, setHiddenLabels] = React.useState<Set<string>>(new Set());

    // Filter visible data by checking if it's in hiddenLabels
    const visibleData = data.map((item) => ({
        ...item,
        value: hiddenLabels.has(item.label) ? 0 : item.value, // Keep label, but hide value
    }));

    const totalValue = React.useMemo(() => {
        return data.reduce((acc, curr) => (hiddenLabels.has(curr.label) ? acc : acc + curr.value), 0);
    }, [hiddenLabels, data]);


    // Generate chart config dynamically based on `data`
    const chartConfig = React.useMemo(() => {
        return data.reduce(
            (config, item) => {
                config[item.label] = {
                    label: item.label,
                    color: item.fill,
                    // color: `hsl(var(--chart-${index + 1}))`, // Dynamic color assignment
                };
                return config;
            },
            {} as Record<string, { label: string; color: string }>,
        );
    }, [data]);

    // Format data with dynamic colors
    const formattedData = visibleData.map((item) => ({
        ...item,
        fill: chartConfig[item.label]?.color || 'hsl(var(--chart-default))', // Default color if not found
    }));

    const handleLegendClick = (label: string) => {
        setHiddenLabels((prev) => {
            const newSet = new Set(prev);
            if (newSet.has(label)) {
                newSet.delete(label); // Show label again
            } else {
                newSet.add(label); // Hide label
            }
            return new Set(newSet);
        });
    };

    return (
        <Card className="flex flex-col">
            <CardHeader className="items-center pb-0">
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className="flex-1 pb-0">
                <ChartContainer config={chartConfig} className="mx-auto aspect-square max-h-[360px]">
                    <PieChart>
                        <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                        <Pie data={formattedData} dataKey="value" nameKey="label" innerRadius={60} strokeWidth={5}>
                            <Label
                                content={({ viewBox }) => {
                                    if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                                        return (
                                            <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle" dominantBaseline="middle">
                                                <tspan x={viewBox.cx} y={viewBox.cy} className="fill-foreground text-3xl font-bold">
                                                    {totalValue.toLocaleString()}
                                                </tspan>
                                                <tspan x={viewBox.cx} y={(viewBox.cy || 0) + 24} className="fill-muted-foreground">
                                                    {totalLabel}
                                                </tspan>
                                            </text>
                                        );
                                    }
                                }}
                            />
                        </Pie>
 
                        <ChartLegend
                            content={({ payload }) => (
                                <ChartLegendContent
                                    className="-translate-y-2 cursor-pointer flex-wrap gap-2 [&>*]:basis-1/4 [&>*]:justify-center"
                                    payload={payload}
                                    onClick={(event, label) => {
                                        event.preventDefault(); // Prevent bubbling issues
                                        handleLegendClick(label); // Ensure label is passed correctly
                                      }}

                                />
                            )}
                        />
                      
                    </PieChart>
                </ChartContainer>
            </CardContent>
    
        </Card>
    );
}
