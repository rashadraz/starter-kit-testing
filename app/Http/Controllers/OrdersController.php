<?php
namespace App\Http\Controllers\Logistics;

use App\CancellationReason;
use App\Captain;
use App\Client;
use App\ClientShop;
use App\DeliveryType;
use App\Filter\OrderFilter;
use App\GeneralExport;
use App\Jobs\ThirdPartyOrderExportJob;
use App\Order;
use App\OrderStatus;
use App\Region;
use App\Ticket;
use App\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class OrdersController
{

    public function index(OrderFilter $request)
    {
        $company_id_3pl = session('company_id_3pl');
        $filter_statuses = $request->request()->get('status');
        $orders = Order::select('orders.code', 'orders.client_order_id', 'orders.amount', 'orders.delivery_charge', 'orders.delivery_date', 'orders.created_at', 'orders.status_id', 'orders.id', 'orders.delivery_time', 'orders.client_id', 'orders.captain_id', 'orders.zone_id', 'orders.region_id', 'orders.shopname', 'orders.delivery_type', 'orders.scheduled_delivery_time_slot_id', 'orders.dispatch_at')
            ->with([
                'shop:id,name,express_time,zone_id',
                'shop.zone:id,name',
                'shop.region:regions.id,regions.name',
                'timeSlot',
                'progress:id,name',
                'captain:id,phone_number,user_id',
                'captain.user:id,name',
                'captain.captainThirdParty',
            ])
            ->with([
                'openTicket' => function ($query) {
                    $query->withCount('notUserSeenMessages');
                },
                'openComplaint' => function ($query) {
                    $query->withCount('notUserSeenMessages');
                },
            ])
            ->where(function ($query) {
                $query->where([
                    ['delivery_type', '=', DeliveryType::SCHEDULES],
                    ['dispatch_at', '<=', now()->format('Y-m-d H:i:s')],
                ])
                    ->orWhere('delivery_type', '=', DeliveryType::EXPRESS);
            })
            ->when(
                $filter_statuses && empty(array_diff(is_array($filter_statuses) ? $filter_statuses : [$filter_statuses], [OrderStatus::ORDER_PACKAGE, OrderStatus::ASSIGN_ATTEMPTS])),
                function ($query) {
                    $query->with('package.package');
                }
            )
            ->when(
                $filter_statuses && empty(array_diff(is_array($filter_statuses) ? $filter_statuses : [$filter_statuses], [OrderStatus::NEW_ORDER])),
                function ($query) {
                    $query->whereHas('shop', function ($query) {
                        $query->where('auto_assignable', 0);
                    });
                }
            )
            ->withClient()
            ->belongsTo3pl($company_id_3pl)
            ->filter($request)
            ->orderBy('orders.id', 'desc')
            ->paginate(100)
            ->withQueryString();

        $data = [
            'on_going_orders_count' => Order::belongsTo3pl($company_id_3pl)->filter($request, [], ['status'])->whereIn('status_id', [OrderStatus::ACCEPT, OrderStatus::START_RIDE, OrderStatus::REACHED_SHOP, OrderStatus::PICKED, OrderStatus::PICKED_UP, OrderStatus::SHIPPED, OrderStatus::REACHED_DESTINATION, OrderStatus::REROUTED])->toBase()->count(),
            'complaints_orders_count' => Order::belongsTo3pl($company_id_3pl)->filter($request, [], ['status'])->whereIn('status_id', [OrderStatus::TICKET_RAISED, OrderStatus::PENDING])->toBase()->count(),
            'client_return_orders_count' => Order::belongsTo3pl($company_id_3pl)->filter($request, [], ['status'])->whereIn('status_id', [OrderStatus::RETURN_TO_CLIENT])->toBase()->count(),
            'request_for_cancel_orders_count' => Order::belongsTo3pl($company_id_3pl)->filter($request, [], ['status'])->whereIn('status_id', [OrderStatus::REQUEST_FOR_CANCEL])->toBase()->count(),
        ];

        if ($request->request()->ajax()) {
            return [
                'order_list' => view('orders.partials.order_list', compact('orders'))->render(),
                'orders_count' => view('orders.partials.orders_count', compact('data'))->render(),
            ];
        }

        $captains = Captain::query()
            ->select('id')
            ->withName()
            ->active()
            ->belongsTo3pl($company_id_3pl)
            ->toBase()
            ->get();

        $order_statuses = OrderStatus::
            logisticStatuses()
            ->toBase()
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        return view('logistics.orders.index', compact('orders', 'captains', 'data', 'order_statuses'));
    }

    // public function streamView()
    // {
    //     $company_id_3pl = session('company_id_3pl');
    //     $filter_regions = request('regions') ? request('regions') : [];
    //     $filter_zone = request('zone') ? request('zone') : false;
    //     $filter_client = request('client') ? request('client') : false;
    //     $filter_client_branch = request('client_branch') ? request('client_branch') : false;
    //     $filter_status = request('status') ? request('status') : false;
    //     $filter_captain = request('captain') ? request('captain') : false;
    //     $filter_captain_state = request('captain_state') ? request('captain_state') : false;

    //     $remove_no_order_shops = request('remove_no_order_shops') ? request('remove_no_order_shops') : false;

    //     $filter_regions_data = Region::get();
    //     $filter_online_captains_data = Captain::with('user', 'location.zone', 'currentShift', 'currentOrder')
    //         ->belongsTo3pl($company_id_3pl)
    //         ->online()->orderBy('firstname')->get();
    //     $filter_zones_data = Zone::query()
    //         ->active()
    //         ->whereNotNull('polygon')->get();
    //     $filter_client_shops_data = ClientShop::whereHas('client', function ($query) {
    //         $query->isActive();
    //     })
    //         ->isActive()
    //         ->get();
    //     $filter_clients_data = Client::with('user')->isActive()->has('shops')->get();

    //     $regions = Region::query()
    //         ->when($filter_regions, function ($query, $filter_regions) {
    //             $query->whereIn('id', $filter_regions);
    //         })
    //         ->when($filter_zone, function ($query, $filter_zone) {
    //             $query->whereHas('zones', function ($query) use ($filter_zone) {
    //                 $query->where('id', $filter_zone);
    //             });
    //         })
    //         ->get();

    //     $online_captains = Captain::online()
    //         ->with('user', 'location.zone', 'currentOrder.client.user', 'user', 'currentShift', 'currentOrder.shop', 'vehicle.vehicleType')
    //         ->withCount('currentOrder')
    //         ->belongsTo3pl($company_id_3pl)
    //         ->whereHas('regions', function ($query) use ($regions) {
    //             $query->whereIn('region_id', $regions->pluck('id')->toArray());
    //         })
    //         ->when($filter_captain, function ($query, $filter_captain) {
    //             $query->where('id', $filter_captain);
    //         })
    //         ->when($filter_captain_state, function ($query, $captain_state) {
    //             if ($captain_state === 'free') {
    //                 $query->onlineFree();
    //             }

    //             if ($captain_state === 'busy') {
    //                 $query->whereHas('currentOrder');
    //             }

    //             if ($captain_state === 'no_update') {
    //                 $query->idle();
    //             }
    //         })
    //         ->orderBy('firstname')
    //         ->get()
    //         ->map(function ($captain) {
    //             $captain->append('online_state');
    //             return $captain;
    //         });

    //     $captain_states = [
    //         'free' => 'Free',
    //         'busy' => 'Busy',
    //         'no_update' => 'No Update',
    //     ];

    //     $zones = Zone::query()
    //         ->active()
    //         ->whereNotNull('polygon')
    //         ->whereIn('region_id', $regions->pluck('id')->toArray())
    //         ->when($filter_zone, function ($query, $filter_zone) {
    //             $query->where('id', $filter_zone);
    //         })
    //         ->get();

    //     $client_shops = ClientShop::query()
    //         ->isActive()
    //         ->whereHas('client', function ($query) {
    //             $query->isActive();
    //         })
    //         ->when($filter_regions, function ($query, $filter_regions) {
    //             $query->whereHas('zone', function ($query) use ($filter_regions) {
    //                 $query->whereIn('region_id', $filter_regions);
    //             });
    //         })
    //         ->when($filter_zone, function ($query, $filter_zone) {
    //             $query->where('zone_id', $filter_zone);
    //         })
    //         ->when($filter_client, function ($query, $filter_client) {
    //             $query->where('client_id', $filter_client);
    //         })
    //         ->when($filter_client_branch, function ($query, $filter_client_branch) {
    //             $query->where('id', $filter_client_branch);
    //         })
    //         ->when($remove_no_order_shops, function ($query) {
    //             $query->whereHas('newOrders');
    //         })
    //         ->withCount('newOrders')
    //         ->get();

    //     $clients = Client::query()
    //         ->isActive()
    //         ->has('shops')
    //         ->when($filter_client, function ($query, $filter_client) {
    //             $query->where('id', $filter_client);
    //         })
    //         ->when($filter_client_branch, function ($query) use ($client_shops) {
    //             $query->whereIn('id', $client_shops->pluck('id')->toArray());
    //         })
    //         ->get();

    //     $total_free_captains = Captain::query()
    //         ->belongsTo3pl($company_id_3pl)
    //         ->whereHas('regions', function ($query) use ($regions) {
    //             $query->whereIn('region_id', $regions->pluck('id')->toArray());
    //         })
    //         ->whereHas('captainThirdParty', function ($query) use ($company_id_3pl) {
    //             $query->where('third_party_logistic_company_id', $company_id_3pl);
    //         })
    //         ->when($filter_zone, function ($query, $filter_zone) {
    //             $query->whereHas('region.zones', function ($query) use ($filter_zone) {
    //                 $query->where('id', $filter_zone);
    //             });
    //         })
    //         ->onlineFree()
    //         ->count();
    //     $total_busy_captains = Captain::query()
    //         ->online()
    //         ->belongsTo3pl($company_id_3pl)
    //         ->whereHas('regions', function ($query) use ($regions) {
    //             $query->whereIn('region_id', $regions->pluck('id')->toArray());
    //         })
    //         ->whereHas('captainThirdParty', function ($query) use ($company_id_3pl) {
    //             $query->where('third_party_logistic_company_id', $company_id_3pl);
    //         })
    //         ->when($filter_zone, function ($query, $filter_zone) {
    //             $query->whereHas('region.zones', function ($query) use ($filter_zone) {
    //                 $query->where('id', $filter_zone);
    //             });
    //         })
    //         ->whereHas('currentOrder')
    //         ->count();

    //     $new_total_orders = Order::query()
    //         ->where('status_id', OrderStatus::NEW_ORDER)
    //         ->when($filter_zone, function ($query, $filter_zone) {
    //             $query->where('zone_id', $filter_zone);
    //         })
    //         ->when($filter_regions, function ($query, $filter_regions) {
    //             $query->whereIn('region_id', $filter_regions);
    //         })
    //         ->count();

    //     $data = [
    //         'online_captains' => $online_captains,
    //         'captain_states' => $captain_states,
    //         'regions' => $regions,
    //         'zones' => $zones,
    //         'clients' => $clients,
    //         'client_shops' => $client_shops,

    //         'total_free_captains' => $total_free_captains,
    //         'total_busy_captains' => $total_busy_captains,
    //         'total_orders' => $new_total_orders,

    //         'open_tickets' => Ticket::open()->count(),
    //         'pending_orders' => Order::where('status_id', OrderStatus::PENDING)->count(),
    //         'captains' => Captain::active()->belongsTo3pl($company_id_3pl)->count(),
    //         'captains_online_free' => Captain::active()->belongsTo3pl($company_id_3pl)->onlineFree()->whereHas('captainThirdParty', function ($query) use ($company_id_3pl) {
    //             $query->where('third_party_logistic_company_id', $company_id_3pl);
    //         })->count(),
    //         'captains_online_busy' => Captain::active()->onlineBusy()->belongsTo3pl($company_id_3pl)->count(),
    //         'captains_offline' => Captain::active()->offline()->belongsTo3pl($company_id_3pl)->count(),

    //         'filter_regions_data' => $filter_regions_data,
    //         'filter_online_captains_data' => $filter_online_captains_data,
    //         'filter_zones_data' => $filter_zones_data,
    //         'filter_client_shops_data' => $filter_client_shops_data,
    //         'filter_clients_data' => $filter_clients_data,
    //         'user_prefered_map_style' => Cache::get('user_prefered_map_style-' . auth()->id()),
    //         'preference' => [
    //             'zone_layer_show' => Cache::get('zone_layer_show_' . auth()->id(), true) === 'true' || Cache::get('zone_layer_show_' . auth()->id(), true) === true ? true : false,
    //             'remove_no_order_shop' => Cache::get('remove_no_order_shop_' . auth()->id(), true) === 'true' || Cache::get('remove_no_order_shop_' . auth()->id(), true) === true ? true : false,
    //         ],
    //     ];

    //     return view('logistics.orders.streamline-view', $data);
    // }

    // public function show($id)
    // {
    //     $company_id_3pl = session('company_id_3pl');
    //     $array = [];
    //     $location = [];
    //     $order = Order::select('orders.*')
    //         ->belongsTo3pl($company_id_3pl)
    //         ->with([
    //             'captain',
    //             'client',
    //             'items',
    //             'logsExecpt.progress',
    //             'logsExecpt.createdBy',
    //             'addresses',
    //             'shop:id,name'
    //         ])
    //         ->find($id);

    //     if (!$order) {
    //         abort(404);
    //     }

    //     $order_statuses = OrderStatus::orderBy('priority')->orderBy('id')->get();

    //     $order_cancellation_reasons = CancellationReason::active()->get();

    //     $ticket = Ticket::where('order_id', $order->id)->where('type', Ticket::TYPE_TICKET)->with('messages', 'captain')->latest()->first();
    //     $pending_ticket = Ticket::where('order_id', $order->id)->where('type', Ticket::TYPE_PENDING)->with('messages', 'captain')->latest()->first();
    //     $client_ticket = Ticket::where('order_id', $order->id)->where('type', Ticket::TYPE_CLIENT)->with('messages.sender.client')->latest()->first();

    //     return view('logistics.orders.show', compact('order', 'order_statuses', 'order_cancellation_reasons', 'ticket', 'pending_ticket', 'client_ticket'));
    // }

    // // function for export
    // public function export(Request $request)
    // {
    //     $exports = new GeneralExport();
    //     $exports->export_type = 'order report';
    //     $exports->status = 'pending';
    //     $exports->email_id = $request->email;
    //     $exports->created_by = Auth::user()->id;
    //     $exports->save();

    //     $company_id_3pl = session('company_id_3pl');
    //     dispatch(new ThirdPartyOrderExportJob($exports, '3pl-order-export', 1, $request->all(), $company_id_3pl));

    //     return response()->json(['status' => 'success', 'export_id' => $exports->id, 'batch_id' => null]);
    // }

}
