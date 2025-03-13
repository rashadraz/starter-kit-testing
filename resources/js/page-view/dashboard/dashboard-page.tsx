import { BarGraph } from '@/components/charts/bar-graph';
import { PieGraph } from '@/components/charts/pie-graph';
import DashboardCard from '@/components/page-components/dashboard/dashboard-card';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { format } from 'date-fns';
import { Circle, CircleX, Package, PackageCheck, Undo2 } from 'lucide-react';
import { useState } from 'react';

const DashboardPage = () => {
    const [fromDate, setFromDate] = useState<Date>();
    const [toDate, setToDate] = useState<Date>();
    const [filterType, setFilterType] = useState('all');

    const handleReset = () => {
        setFromDate(undefined);
        setToDate(undefined);
        setFilterType('all');
    };

    const handleSubmit = () => {
        // Implement your filter logic here
        console.log({
            fromDate: fromDate ? format(fromDate, 'yyyy-MM-dd') : '',
            toDate: toDate ? format(toDate, 'yyyy-MM-dd') : '',
            filterType,
        });
    };

    const active_captains_region_data = [
        { label: 'AL QASSIM', value: 0, fill: 'var(--chart-1)' },
        { label: 'Riyadh', value: 69, fill: 'var(--chart-2)' },
        { label: 'RUH', value: 0, fill: 'var(--chart-3)' },
        { label: 'test region', value: 0, fill: 'var(--chart-3)' },
        { label: 'testregion2.', value: 0, fill: 'var(--chart-3)' },
        { label: 'guyjgy', value: 1000, fill: 'var(--chart-3)' },
    ];

    const active_captains_vehicle_data = [
        { label: 'CAR', value: 55, fill: 'var(--chart-1)' },
        { label: 'VAN', value: 2, fill: 'var(--chart-2)' },
        { label: 'BIKE', value: 12, fill: 'var(--chart-3)' },
    ];

    const active_captains_shift_status_data = [
        { label: 'Online', value: 2, fill: 'var(--chart-2)' },
        { label: 'Offline', value: 67, fill: 'var(--chart-1)' },
    ];

    const active_and_inactive_captains_data = [
        { label: 'Active Captain', value: 50, fill: 'var(--chart-2)' },
        { label: 'InActive Captain', value: 30, fill: 'var(--chart-1)' },
    ];

    const online_captains_count_by_order_status_data = [
        { label: 'Order Accept', value: 10, fill: '#10b981' },
        { label: 'Reached Shop', value: 8, fill: '#eab308' },
        { label: 'Delivered Shop', value: 8, fill: '#0f7bb4' },
    ];

    return (
        <div>
            <div className="mb-6 rounded-lg border p-4 shadow-sm">
                <div className="grid gap-4 md:grid-cols-6">
                    <Select value={filterType} onValueChange={setFilterType}>
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select region" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="all">All Regions</SelectItem>
                                <SelectItem value="riyadh">Riyadh Region</SelectItem>
                                <SelectItem value="makkah">Makkah Region</SelectItem>
                                <SelectItem value="medina">Medina Region</SelectItem>
                                <SelectItem value="qassim">Al-Qassim Region</SelectItem>
                                <SelectItem value="eastern">Eastern Region</SelectItem>
                                <SelectItem value="asir">Asir Region</SelectItem>
                                <SelectItem value="tabuk">Tabuk Region</SelectItem>
                                <SelectItem value="hail">Hail Region</SelectItem>
                                <SelectItem value="jazan">Jazan Region</SelectItem>
                                <SelectItem value="najran">Najran Region</SelectItem>
                                <SelectItem value="bahah">Al-Bahah Region</SelectItem>
                                <SelectItem value="jouf">Al-Jouf Region</SelectItem>
                                <SelectItem value="northern">Northern Borders Region</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <div className="flex flex-col gap-2 w-auto">
                        <DatePicker  date={fromDate} onChange={(date) => setFromDate(date)} />
                        <div className="flex gap-1">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setFromDate((date) => (date ? new Date(date.setDate(date.getDate() + 1)) : new Date()))}
                            >
                                +D
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setFromDate((date) => (date ? new Date(date.setMonth(date.getMonth() + 1)) : new Date()))}
                            >
                                +M
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setFromDate((date) => (date ? new Date(date.setFullYear(date.getFullYear() + 1)) : new Date()))}
                            >
                                +Y
                            </Button>
                        </div>
                    </div>
                    <div className="flex flex-col gap-2">
                        <DatePicker  date={toDate} onChange={(date) => setToDate(date)} />
                        <div className="flex gap-1">
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setToDate((date) => (date ? new Date(date.setDate(date.getDate() - 1)) : new Date()))}
                            >
                                -D
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setToDate((date) => (date ? new Date(date.setMonth(date.getMonth() - 1)) : new Date()))}
                            >
                                -M
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setToDate((date) => (date ? new Date(date.setFullYear(date.getFullYear() - 1)) : new Date()))}
                            >
                                -Y
                            </Button>
                        </div>
                    </div>

                    {/* <div className="flex flex-col gap-2">
                        <Input type="date" value={fromDate} onChange={(e) => setFromDate(e.target.value)} placeholder="From Date" />
                        <div className="flex gap-1">
                            <Button size="sm" variant="outline" onClick={() => setFromDate(adjustDate(fromDate, { value: 1, unit: 'day' }, 'add'))}>
                                +D
                            </Button>
                            <Button size="sm" variant="outline" onClick={() => setFromDate(adjustDate(fromDate, { value: 1, unit: 'month' }, 'add'))}>
                                +M
                            </Button>
                            <Button size="sm" variant="outline" onClick={() => setFromDate(adjustDate(fromDate, { value: 1, unit: 'year' }, 'add'))}>
                                +Y
                            </Button>
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <Input type="date" value={toDate} onChange={(e) => setToDate(e.target.value)} placeholder="To Date" />
                        <div className="flex gap-1">
                            <Button size="sm" variant="outline" onClick={() => setToDate(adjustDate(toDate, { value: 1, unit: 'day' }, 'subtract'))}>
                                -D
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setToDate(adjustDate(toDate, { value: 1, unit: 'month' }, 'subtract'))}
                            >
                                -M
                            </Button>
                            <Button size="sm" variant="outline" onClick={() => setToDate(adjustDate(toDate, { value: 1, unit: 'year' }, 'subtract'))}>
                                -Y
                            </Button>
                        </div>
                    </div> */}

                    <div className="flex items-end gap-2">
                        <Button onClick={handleSubmit} className="bg-blue-500 text-white">
                            Submit
                        </Button>
                        <Button onClick={handleReset} variant="outline">
                            Reset
                        </Button>
                    </div>
                </div>
            </div>

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-6">
                <DashboardCard
                    title="Total Orders"
                    icon={<Package className="h-5 w-5 flex-shrink-0 text-blue-500" />}
                    value="45,231"
                    description="Total Orders"
                />

                <DashboardCard
                    title="Delivered Orders"
                    icon={<PackageCheck className="h-5 w-5 text-green-500" />}
                    value="2,350"
                    description="Orders Delivered"
                    color="text-green-500"
                />

                <DashboardCard
                    title="Cancelled"
                    icon={<CircleX className="h-5 w-5 text-red-500" />}
                    value="12,234"
                    description="Orders Cancelled"
                    color="text-red-500"
                />

                <DashboardCard
                    title="Return To Client"
                    icon={<Undo2 className="h-5 w-5 text-orange-500" />}
                    value="500"
                    description="Orders Returned"
                    color="text-orange-500"
                />

                <DashboardCard
                    title="Online Captains"
                    icon={<Circle className="h-5 w-5 text-green-500" fill="currentColor" />}
                    value="200"
                    description="Active now"
                    color="text-green-500"
                />

                <DashboardCard
                    title="Offline Captains"
                    icon={<Circle className="h-5 w-5 text-red-500" fill="currentColor" />}
                    value="100"
                    description="Currently inactive"
                />
            </div>
            <div className="grid gap-4 pt-10 md:grid-cols-2 lg:grid-cols-2">
                <PieGraph title="Active Captains By Region" data={active_captains_region_data} totalLabel="Total Captains" />
                <PieGraph title="Active Captains By Vehicle Type" data={active_captains_vehicle_data} totalLabel="Total Vehicles" />
                <PieGraph title="Captain By Shift Status" data={active_captains_shift_status_data} totalLabel="Total Captains" />
                <PieGraph title="Active and Inactive Captains" data={active_and_inactive_captains_data} totalLabel="Total Captains" />
            </div>
            <div className="grid grid-cols-1 gap-4 pt-10 lg:grid-cols-1">
                <BarGraph data={online_captains_count_by_order_status_data} title="Online Captains Count by Order Status" />
            </div>
        </div>
    );
};

export default DashboardPage;
