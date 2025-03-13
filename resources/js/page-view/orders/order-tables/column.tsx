
import { ColumnDef } from '@tanstack/react-table';


import { Product } from '../orders-listing-page';
// import { CellAction } from './cell-action';
import { Eye } from 'lucide-react';

export const columns: ColumnDef<Product>[] = [
  {
    accessorKey: 'order_id',
    header: 'ORDER ID',
    // cell: ({ row }) => {
    //     const imageUrl = row.getValue('photo_url'); // Get image URL
    //     return (
    //       <div className="relative w-16 h-16">
    //         {imageUrl ? (
    //           <img
    //             src={imageUrl || ''} 
    //             alt={row.getValue('name') || 'Product Image'}
    //             className="rounded-lg object-cover w-full h-full"
    //           />
    //         ) : (
    //           <span className="text-gray-500">No Image</span>
    //         )}
    //       </div>
    //     );
    //   }
    // cell: ({ row }) => {
    //   return (
    //     <div className='relative aspect-square'>
      
    //       <image
    //         src={row.getValue('photo_url')}
    //         alt={row.getValue('name')}
    //         // fill
    //         className='rounded-lg'
    //       />
    //     </div>
    //   );
    // }
  },
  {
    accessorKey: 'client_id',
    header: 'Client ID'
  },
  {
    accessorKey: 'client_name',
    header: 'Client Name'
  },
  {
    accessorKey: 'shop_name',
    header: 'Shop Name'
  },
  {
    accessorKey: 'area',
    header: 'Area'
  },
  {
    accessorKey: 'zone',
    header: 'Zone'
  },
  {
    accessorKey: 'amount',
    header: 'Amount'
  },
  {
    accessorKey: 'type',
    header: 'Type'
  },
  {
    accessorKey: 'status',
    header: 'Status'
  },
  {
    accessorKey: 'assigned_captains',
    header: 'Assigned Captain'
  },
  {
    accessorKey: 'updated_at',
    header: 'Updated At'
  },
  {
    accessorKey: 'timer',
    header: 'Timer'
  },
  {
    id: 'actions',
    header: 'Action',
    cell: ({ row }) => {
        return (
          <button
            onClick={() => {
              // Add your view action logic here
              console.log('View details for:', row.original);
            }}
          >
           <Eye className='' /> 
          </button>
        );
    }
      

  },


//   {
//     id: 'actions',
//     cell: ({ row }) => <CellAction data={row.original} />
//   }
];
