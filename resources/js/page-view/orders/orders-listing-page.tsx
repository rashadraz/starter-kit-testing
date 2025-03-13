import { DataTable as OrderTable } from '@/components/ui/table/data-table';
import { columns } from './order-tables/column';
import OrderTableAction from './order-tables/order-table-action';

export type Product = {
    id: number,
    order_id: number,
    client_id: number,
    client_name: string,
    shop_name: string,
    area: string,
    zone: string,
    amount: number,
    type: string,
    status: string,
    assigned_captains: string,
    timer: string,
    updated_at: string,
 
};

const OrdersListingPage = () => {

    // Showcasing the use of search params cache in nested RSCs
    // const page = searchParamsCache.get('page');
    // const search = searchParamsCache.get('q');
    // const pageLimit = searchParamsCache.get('limit');
    // const categories = searchParamsCache.get('categories');

    // const filters = {
    //     page,
    //     limit: pageLimit,
    //     ...(search && { search }),
    //     ...(categories && { categories: categories }),
    // };


    const ordersData = [
      {
        id: 1,
        order_id: 12345678901,
        client_id: 98765432101,
        client_name: 'Alice Smith',
        shop_name: 'Super Mart',
        area: 'Downtown',
        zone: 'Zone A',
        amount: 1500,
        type: 'express',
        status: 'completed',
        assigned_captains: "rashad",
        timer: 'on-time',
        updated_at: '2025-03-12T10:15:30.123Z',
      },
      {
        id: 2,
        order_id: 22334455667,
        client_id: 33445566778,
        client_name: 'Bob Johnson',
        shop_name: 'Quick Stop',
        area: 'Uptown',
        zone: 'Zone B',
        amount: 800,
        type: 'fast',
        status: 'pending',
        assigned_captains: "ali",
        timer: 'delayed',
        updated_at: '2025-03-12T12:45:20.456Z',
      },
      {
        id: 3,
        order_id: 99887766554,
        client_id: 11223344556,
        client_name: 'Charlie Brown',
        shop_name: 'Grocery King',
        area: 'Suburb',
        zone: 'Zone C',
        amount: 2200,
        type: 'scheduled',
        status: 'assigned',
        assigned_captains: "rashad",
        timer: 'on-time',
        updated_at: '2025-03-12T14:30:10.789Z',
      },
      {
        id: 4,
        order_id: 55667788990,
        client_id: 66778899001,
        client_name: 'David Wilson',
        shop_name: 'Fresh Market',
        area: 'Midtown',
        zone: 'Zone D',
        amount: 1350,
        type: 'fast',
        status: 'delivered',
        assigned_captains: "umar",
        timer: 'delayed',
        updated_at: '2025-03-12T16:55:05.234Z',
      },
      {
        id: 5,
        order_id: 33445566778,
        client_id: 55667788990,
        client_name: 'Emily Davis',
        shop_name: 'Organic Mart',
        area: 'Old Town',
        zone: 'Zone E',
        amount: 1750,
        type: 'express',
        status: 'cancelled',
        assigned_captains: "rashad",
        timer: 'n/a',
        updated_at: '2025-03-12T18:20:40.678Z',
      },
      {
        id: 6,
        order_id: 77889900112,
        client_id: 99001122334,
        client_name: 'Frank Miller',
        shop_name: 'Daily Essentials',
        area: 'Harbor',
        zone: 'Zone F',
        amount: 950,
        type: 'fast',
        status: 'pending',
        assigned_captains: "khalid",
        timer: 'delayed',
        updated_at: '2025-03-12T19:45:15.890Z',
      },
      {
        id: 7,
        order_id: 11223344556,
        client_id: 33445566778,
        client_name: 'Grace Lee',
        shop_name: 'Hyper Mart',
        area: 'New City',
        zone: 'Zone G',
        amount: 2050,
        type: 'scheduled',
        status: 'assigned',
        assigned_captains: "rashad",
        timer: 'on-time',
        updated_at: '2025-03-12T21:10:50.321Z',
      },
      {
        id: 8,
        order_id: 88990011223,
        client_id: 11223344556,
        client_name: 'Hank Green',
        shop_name: 'Mega Store',
        area: 'Business Bay',
        zone: 'Zone H',
        amount: 1200,
        type: 'express',
        status: 'delivered',
        assigned_captains: "faris",
        timer: 'on-time',
        updated_at: '2025-03-12T22:27:52.619Z',
      },
      {
        id: 9,
        order_id: 99001122334,
        client_id: 22334455667,
        client_name: 'Isla Wright',
        shop_name: 'Market Hub',
        area: 'Tech Park',
        zone: 'Zone I',
        amount: 1100,
        type: 'fast',
        status: 'pending',
        assigned_captains: "rashad",
        timer: 'delayed',
        updated_at: '2025-03-12T23:50:30.987Z',
      },
      {
        id: 10,
        order_id: 22334455667,
        client_id: 99887766554,
        client_name: 'Jack Carter',
        shop_name: 'City Mall',
        area: 'Central District',
        zone: 'Zone J',
        amount: 1900,
        type: 'scheduled',
        status: 'assigned',
        assigned_captains: "rashad",
        timer: 'on-time',
        updated_at: '2025-03-13T01:15:20.456Z',
      },
    ];
    

    const totalOrders = ordersData.length;
    // const products: Product[] = data.products;

    return (
        <div className='flex flex-col gap-4'>
            {/* <OrderTableAction /> */}
            <OrderTable columns={columns} data={ordersData} totalItems={totalOrders} />
        </div>
    );
};

export default OrdersListingPage;
