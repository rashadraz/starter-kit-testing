'use client';

import { DatePicker } from '@/components/ui/date-picker';
import { DataTableFilterBox } from '@/components/ui/table/data-table-filter-box';
import { DataTableResetFilter } from '@/components/ui/table/data-table-reset-filter';
import { DataTableSearch } from '@/components/ui/table/data-table-search';
import { CATEGORY_OPTIONS, useProductTableFilters } from './use-order-table-filters';


export default function OrderTableAction() {

    const STATUS_CARDS = [
      { label: 'Ongoing', value: 'ongoing', statusIds: [1, 2, 3] },
      { label: 'Cancellation', value: 'cancellation', statusIds: [4, 5] },
      { label: 'Delivered', value: 'delivered', statusIds: [6] },
      { label: 'All', value: 'all', statusIds: [] },
    ];



    const {
        categoriesFilter,
        setCategoriesFilter,
        isAnyFilterActive,
        resetFilters,
        searchQuery,
        setPage,
        setSearchQuery,
        fromDate,
        toDate,
        setFromDate,
        setToDate,
    } = useProductTableFilters();
    return (
        <div className="flex flex-wrap items-center gap-4">
           <div className="flex gap-4">
                {STATUS_CARDS.map((status) => (
                    <button
                        key={status.value}
                        className={`px-4 py-2 rounded-lg border `}
                      
                    >
                        {status.label}
                    </button>
                ))}
            </div>
            <DataTableSearch searchKey="Client Id" searchQuery={searchQuery} setSearchQuery={setSearchQuery} setPage={setPage} />

            {/* <DataTableFilterBox
                filterKey="categories"
                title="Categories"
                options={CATEGORY_OPTIONS}
                setFilterValue={setCategoriesFilter}
                filterValue={categoriesFilter}
                multiple
            /> */}
            <DataTableFilterBox
                filterKey="captain_id"
                title="Select Captain"
                options={CATEGORY_OPTIONS}
                setFilterValue={setCategoriesFilter}
                filterValue={categoriesFilter}
            />
            <DataTableFilterBox
                filterKey="type"
                title="Select Order Type"
                options={CATEGORY_OPTIONS}
                setFilterValue={setCategoriesFilter}
                filterValue={categoriesFilter}
            />
            <DataTableFilterBox
                filterKey="status_id"
                title="Select Order Status"
                options={CATEGORY_OPTIONS}
                setFilterValue={setCategoriesFilter}
                filterValue={categoriesFilter}
            />
            <DatePicker label="From Date" date={toDate || new Date()} onChange={setToDate} />
            <DatePicker label="To Date" date={fromDate || new Date()} onChange={setFromDate} />
            <DataTableResetFilter isFilterActive={isAnyFilterActive} onReset={resetFilters} />
        </div>
    );
}
