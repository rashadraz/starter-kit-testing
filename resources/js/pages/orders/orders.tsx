import { ContentLayout } from '@/components/layout/content-layout';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import PlaceholderContent from '@/contents/placeholder-content';
import AppLayout from '@/layouts/app-layout';
import OrdersListingPage from '@/page-view/orders/orders-listing-page';

// import { type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';
const orders = () => {
    return (
        <AppLayout>
            <ContentLayout title="Orders">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink asChild>
                                <Link href="/">Home</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage>Orders</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
                <PlaceholderContent>
                    <OrdersListingPage></OrdersListingPage>
                </PlaceholderContent>
            </ContentLayout>
        </AppLayout>
    );
};

export default orders;
