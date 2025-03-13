<?php
namespace App;

//use DateTime;

use App\Cache\Order as CacheOrder;
use App\Events\NewOrder;
use App\Services\Map;
use App\Services\Position;
use App\Traits\Filter;
use function Illuminate\Events\queueable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Order extends Model
{
    use Filter, HasFactory;
    protected $guarded = [];
    protected $casts = [
        'delivery_date' => 'datetime',
        'extra_fields' => 'array',
        'meta' => 'array',
    ];

    const ORDER_PAYMENT_MODE_AUTO = 'Auto';
    const ORDER_PAYMENT_MODE_MANUAL = 'Manual';
    const ORDER_PAYMENT_MODE_PRE_PAID = 'Pre Paid';
    const ORDER_PAYMENT_MODE_SWIPING_MACHINE = 'Swiping Machine';

    const ORDER_TYPES_OFFLINE = 'Offline';
    const ORDER_TYPES_ONLINE = 'Online';
    const ORDER_TYPES_IMPORT = 'Import';
    const ORDER_TYPE_SHIPMENT = 'shipment';
    const ORDER_TYPE_RETURN = 'return';

    const USER_TYPE_ADMIN = 'Admin';
    const USER_TYPE_CUSTOMER = 'Customer';

    const SOURCE_FARAWALA = 'Farawala';
    const SOURCE_NOON = 'Noon';
    const SOURCE_OTHER = 'Other';

    const DELIVERY_TYPE_FAST = 'Fast';
    const DELIVERY_TYPE_SCHEDULE = 'Scheduled';

    const DELIVERY_PAYMENT_TYPE_PRE_PAID = 'Pre Paid';

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(queueable(function (Order $order) {
            NewOrder::dispatch($order);
        }));
    }

    public function order_belongs()
    {
        return $this->belongsTo('App\OrderLog', 'id', 'order_id');
    }

    public function captain()
    {
        return $this->hasOne('App\Captain', 'id', 'captain_id');
    }

    public function orderStatus()
    {
        return $this->belongsTo('App\OrderStatus', 'status_id', 'id');
    }

    public function client()
    {
        return $this->hasOne('App\Client', 'id', 'client_id');
    }

    public function payment()
    {
        return $this->belongsTo('App\OrderPayment', 'id', 'order_id')->latest();
    }
    public function payin()
    {
        // return $this->payment()->sum('pos_amount')
        return $this->hasMany('App\OrderPayment', 'id', 'order_id')->selectRaw('sum(pos_amount) as sum_amount');
    }

    public function items()
    {
        return $this->hasMany('App\OrderItem', 'order_id');
    }

    public function scopeBelongsToMe($query)
    {
        if (auth()->user() !== null) {
            $client = auth()->user()->employeeClient->pluck('id');

            if ($client->isNotEmpty()) {
                $query->whereIn('orders.client_id', $client);
            }

            if (auth()->user()->data_permission == User::DATA_PERMISSION_BRANCH_BASED) {
                return $query->whereIn('orders.shopname', auth()->user()->dataPermission()->pluck('id'));
            }

            if (auth()->user()->data_permission == User::DATA_PERMISSION_ZONE_BASED) {
                $permissionable_zones = EmpPermissionZonesBranch::query()
                    ->select('zone_id')
                    ->where('emp_permission_zones_branches.user_id', auth()->id());
                return $query
                    ->join(DB::raw('client_shops AS zone_based_shops'), function (JoinClause $join) {
                        $join->on('orders.shopname', '=', 'zone_based_shops.id');
                    })
                    ->joinSub($permissionable_zones, 'permissionable_zones', function (JoinClause $join) {
                        $join->on('zone_based_shops.zone_id', '=', 'permissionable_zones.zone_id');
                    });
            }

            if (auth()->user()->data_permission == User::DATA_PERMISSION_CLIENT_BASED) {
                return $query->whereIn('orders.client_id', auth()->user()->dataPermission()->pluck('id'));
            }

            if (auth()->user()->data_permission == User::DATA_PERMISSION_REGION_BASED) {
                $permissionable_quadrants = EmpPermissionZonesBranch::query()
                    ->select(DB::raw('DISTINCT zones.id as zone_id'))
                    ->join('regions', 'regions.quadrant_id', 'emp_permission_zones_branches.quadrant_id')
                    ->join('zones', 'zones.region_id', 'regions.id')
                    ->where('emp_permission_zones_branches.user_id', auth()->id());
                return $query
                    ->join(DB::raw('client_shops AS region_based_shops'), function (JoinClause $join) {
                        $join->on('orders.shopname', '=', 'region_based_shops.id');
                    })
                    ->joinSub($permissionable_quadrants, 'permissionable_quadrants', function (JoinClause $join) {
                        $join->on('region_based_shops.zone_id', '=', 'permissionable_quadrants.zone_id');
                    });
            }
        }

        return $query;
    }

    public function scopeBelongsTo3pl($query, $company_id = null)
    {
        if (!$company_id) {
            $company_id = session('company_id_3pl');
        }

        return $query->join('captains', 'orders.captain_id', '=', 'captains.id')
            ->join('captains_third_party_logistic', function (JoinClause $join) use ($company_id) {
                $join->on('captains.id', '=', 'captains_third_party_logistic.captain_id')
                    ->where('captains_third_party_logistic.third_party_logistic_company_id', $company_id);
            });
    }

    public function scopeBelongsToUser($query, User $user)
    {
        $clientIds = $user->employeeClient->pluck('id');

        if ($clientIds->isNotEmpty()) {
            $query->whereIn('orders.client_id', $clientIds);
        }

        if ($user->data_permission == User::DATA_PERMISSION_BRANCH_BASED) {
            return $query->whereIn('orders.shopname', $user->userDataPermission($user)->pluck('id'));
        }

        if ($user->data_permission == User::DATA_PERMISSION_ZONE_BASED) {
            return $query->join(DB::raw('client_shops AS zone_based_shops'), function ($join) use ($user) {
                $join->on('orders.shopname', '=', 'zone_based_shops.id')
                    ->whereIn('zone_based_shops.zone_id', $user->userDataPermission($user)->pluck('id'));
            });
        }

        if ($user->data_permission == User::DATA_PERMISSION_CLIENT_BASED) {
            return $query->whereIn('orders.client_id', $user->userDataPermission($user)->pluck('id'));
        }

        return $query;
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'id', 'order_id');
    }

    public function logs()
    {
        return $this->belongsTo('App\OrderLog', 'id', 'order_id');
    }

    public function lastPending()
    {
        return $this->logs()->where('status_id', OrderStatus::PENDING)->orderBy('id', 'desc')->first();
    }

    public function deliveredOrderLogs()
    {
        return $this->belongsTo('App\OrderLog', 'id', 'order_id')->where('status_id', OrderStatus::DELIVERED);
    }

    public function logsExecpt()
    {
        return $this->hasMany('App\OrderLog', 'order_id', 'id');
    }

    public function pickedOrders()
    {
        return $this->belongsToMany(Captain::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function latest_note()
    {
        return $this->hasOne(Note::class)->latestOfMany();
    }

    public function package()
    {
        return $this->hasOne(PackageOrder::class);
    }

    public function progress(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id', 'id')->latest();
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function latestAddress()
    {
        return $this->hasOne(OrderAddress::class)->orderBy('id', 'desc');
    }

    public function setClass()
    {
        return OrderStatus::getBadgeClass($this->attributes['status_id']);
    }

    public function getTime($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }

        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        $time = substr($string, $ini, $len);
        return date("H:i:s", strtotime($time));
    }

    public function shopPayment()
    {
        return $this->hasOne('App\ShopPayment', 'order_id', 'id')->latest();
    }

    public function captainPayment()
    {
        return $this->hasOne(CaptainOrderPayment::class, 'order_id');
    }

    public function zone()
    {
        return $this->hasOne('App\Zone', 'id', 'zone_id')->with('region');
    }

    public function region()
    {
        return $this->hasOne('App\Region', 'id', 'region_id');
    }

    public function captainCommission()
    {
        return $this->hasOne(CaptainCommission::class);
    }

    public function thirdPartyCommission()
    {
        return $this->hasOne(ThirdPartyCommission::class);
    }

    public function endTime()
    {
        [$start_time, $end_time] = $this->remainingTime();
        return $end_time;
    }

    public function remainingTime()
    {
        $delivery_date = $this->attributes['delivery_date'];
        $created_at = now()->parse($this->attributes['created_at']);

        $delivery_type = $this->attributes['delivery_type'];

        if ($delivery_type == Order::DELIVERY_TYPE_FAST) {
            $express_time = $this->shop->express_time ?? 60;
            $start_time = $created_at;
            $end_time = $created_at->copy()->addMinutes($express_time);
        }

        if ($delivery_type == Order::DELIVERY_TYPE_SCHEDULE) {
            $delivery_date = $this->attributes['dispatch_at'];
            $date = now()->parse($delivery_date)->format('Y-m-d');
            $time_slot = $this->timeSlot;
            $start_time = now()->parse($date . ' ' . (isset($time_slot->start_time) ? $time_slot->start_time->format('H:i:s') : '00:00:00'));
            $end_time = now()->parse($date . ' ' . (isset($time_slot->end_time) ? $time_slot->end_time->format('H:i:s') : '00:00:00'));
        }

        return [$start_time, $end_time];
    }

    public function progressBar()
    {
        $order_status = $this->attributes['status_id'];

        $end_time = null;
        $start_time = null;

        if (in_array($order_status, [OrderStatus::CANCEL, OrderStatus::CANCEL_REQUEST_ACCEPTED, OrderStatus::FORYOU_RETURN_ACCEPTED])) {
            return [
                'width' => 100,
                'color' => 'bg-success',
                'text' => '100 %',
            ];
        }

        [$start_time, $end_time] = $this->remainingTime();

        if (now()->greaterThan($end_time) && $order_status != OrderStatus::DELIVERED) {
            return [
                'width' => 100,
                'color' => 'bg-danger',
                'text' => 'Delayed',
            ];
        }

        if ($order_status == OrderStatus::DELIVERED) {
            if ($this->lastLog && $this->lastLog->created_at->greaterThan($end_time)) {
                return [
                    'width' => 100,
                    'color' => 'bg-danger',
                    'text' => 'Delayed',
                ];
            }
            return [
                'width' => 100,
                'color' => 'bg-success',
                'text' => '100 %',
            ];
        }

        if (now()->greaterThan($start_time)) {
            $currentDifference = $start_time->floatDiffInSeconds(now());
            $difference = $start_time->floatDiffInSeconds($end_time);
            $progress = (int) (100 / ($difference / $currentDifference));

            return [
                'width' => $progress,
                'color' => ($progress > 75 ? 'bg-secondary' : $progress > 25) ? 'bg-info' : 'bg-success',
                'text' => $progress . ' %',
            ];
        }

        return [
            'width' => 0,
            'color' => 'bg-primary',
            'text' => '',
        ];
    }

    public function renderProgressBar()
    {
        $progress = $this->progressBar();
        return '<div class="progress">
                    <div class="progress-bar ' . $progress['color'] . '" role="progressbar" style="width: ' . $progress['width'] . '%" aria-valuenow="' . $progress['width'] . '" aria-valuemin="0" aria-valuemax="100">' . $progress['text'] . '</div>
                </div>';
    }

    public function SplitTime($StartTime, $EndTime, $Duration = "20")
    {
        $ReturnArray = [];                    // Define output
        $StartTime = strtotime($StartTime); //Get Timestamp
        $EndTime = strtotime($EndTime);   //Get Timestamp

        $AddMins = $Duration * 60;

        while ($StartTime <= $EndTime) //Run loop
        {
            $ReturnArray[] = date("Y-m-d G:i:s", $StartTime);
            $StartTime += $AddMins; //Endtime check
        }
        return $ReturnArray;
    }

    public function lastLog()
    {
        return $this->hasOne(OrderLog::class, 'id', 'last_log_id');
    }

    public function scopeWithLastLog($query, $additional_relation = null)
    {
        $query->addSelect([
            'last_log_id' => OrderLog::select('id')
                ->whereColumn('orders.id', 'order_logs.order_id')
                ->latest()
                ->take(1),
        ])
            ->with('lastLog')
            ->when($additional_relation, function ($query, $relation) {
                $query->with($relation);
            });
    }

    public function scopeWithAssignAttempts($query)
    {
        $query->addSelect([
            'assign_attempts_count' => OrderLog::select(DB::raw('count(*)'))
                ->whereColumn('orders.id', 'order_logs.order_id')
                ->where('order_logs.status_id', OrderStatus::ASSIGN_ATTEMPTS),
            'last_assign_attempt_note' => OrderLog::select('note')
                ->whereColumn('orders.id', 'order_logs.order_id')
                ->where('order_logs.status_id', OrderStatus::ASSIGN_ATTEMPTS)
                ->latest('order_logs.id')
                ->take(1),
        ])
            ->orderBy('assign_attempts_count', 'desc');
    }

    public function scopeWithCaptainArrivalTimeAtStore($query, $order)
    {
        $query->addSelect([
            'eta' => OrderEstimatedArrivalTime::select('estimated_arrival_time_at_store')
                ->where('order_estimated_arrival_times.order_id', $order->id)
                ->whereRaw('estimated_arrival_time_at_store_updated_at > ?', [now()->subSeconds(30)])
                ->where('order_estimated_arrival_times.captain_id', $order->captain_id)
                ->take(1),
        ]);
    }

    public function scopeWithCaptainArrivalTimeAtDestination($query, $order)
    {
        $query->addSelect([
            'eta' => OrderEstimatedArrivalTime::select('estimated_arrival_time_at_customer')
                ->where('order_estimated_arrival_times.order_id', $order->id)
                ->whereRaw('estimated_arrival_time_at_customer_updated_at > ?', [now()->subSeconds(30)])
                ->where('order_estimated_arrival_times.captain_id', $order->captain_id)
                ->take(1),
        ]);
    }

    public function log()
    {
        return $this->hasMany('App\OrderLog', 'order_id', 'id');
    }

    public function shop()
    {

        return $this->hasOne('App\ClientShop', 'id', 'shopname');
    }

    public function storeDistance()
    {
        return $this->hasOne(OrderStore::class, 'order_id', 'id');
    }

    public function deliveryCode()
    {
        return $this->hasOne(OrderDeliveryOtp::class, 'order_id', 'id')->orderBy('id', 'desc');
    }

    public function orderDeliveryCharge()
    {
        return $this->hasOne(OrderDeliveryCharge::class, 'order_id', 'id');
    }

    public function generateDeliveryCode()
    {
        if (!($this->client->send_otp_for_prepaid_orders || $this->client->send_otp_for_cod_orders)) {
            return false;
        }

        if ($delivery_code = $this->deliveryCode) {
            $delivery_code->delete();
        }

        $otp = rand(1000, 9999);

        $delivery_otp = new OrderDeliveryOtp(['otp' => Hash::make($otp), 'created_at' => now()]);

        $this->deliveryCode()->save($delivery_otp);

        return $otp;
    }

    public function timeSlot()
    {
        return $this->belongsTo(ClientShopTimeSlot::class, 'scheduled_delivery_time_slot_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'order_id', 'id');
    }

    public function openTickets()
    {
        return $this->hasMany(Ticket::class, 'order_id', 'id')->whereNull('closed_at');
    }

    public function openTicket()
    {
        return $this->hasOne(Ticket::class, 'order_id', 'id')->whereNull('closed_at');
    }

    public function openCaptainTicket()
    {
        return $this->hasOne(Ticket::class, 'order_id', 'id')
            ->whereIn('tickets.type', [Ticket::TYPE_PENDING, Ticket::TYPE_TICKET])
            ->latest('id')
            ->whereNull('closed_at');
    }

    public function openComplaint()
    {
        return $this->hasOne(Ticket::class, 'order_id', 'id')->where('tickets.type', Ticket::TYPE_CLIENT)->whereNull('closed_at');
    }

    public function getCodeAttribute($value)
    {
        return sprintf("%'03d", $this->id);
    }

    public function scopeReadyToDispatch($query, $delivery_type = null)
    {
        $query
            ->leftJoin('time_slots', 'orders.scheduled_delivery_time_slot_id', '=', 'time_slots.id')
            ->whereIn('status_id', [OrderStatus::NEW_ORDER, OrderStatus::ORDER_PACKAGE, OrderStatus::ASSIGN_ATTEMPTS])
            ->where(function ($query) use ($delivery_type) {
                if ($delivery_type == Order::DELIVERY_TYPE_FAST || $delivery_type == null) {
                    $query->where('delivery_type', static::DELIVERY_TYPE_FAST);
                }

                if ($delivery_type == Order::DELIVERY_TYPE_SCHEDULE || $delivery_type == null) {
                    $query->orWhere(function ($query) {
                        $query->where('delivery_type', static::DELIVERY_TYPE_SCHEDULE)
                            ->where('dispatch_at', '<', now()->format('Y-m-d H:i:s'));
                    });
                }
            });
    }

    public function scopeAutoAssignable($query)
    {
        $query->leftJoin('client_shops', 'orders.shopname', '=', 'client_shops.id')
            ->where('client_shops.auto_assignable', 1);
    }

    public function scopeToDispatch($query)
    {
        $query->where('status_id', OrderStatus::NEW_ORDER);
    }

    public function scopeDispatchDelay($query)
    {
        $query->where('orders.created_at', '<', now()->subMinutes(1));
    }

    public function scopeStatus($query, $status_id)
    {
        if (is_array($status_id)) {
            return $query->whereIn('orders.status_id', $status_id);
        }
        return $query->where('orders.status_id', $status_id);
    }

    public function scopeWithRegionZone($query)
    {
        $query->addSelect([
            'zone_name' => Zone::select('name')->whereColumn('orders.zone_id', 'zones.id'),
            'region_name' => Region::select('name')->whereColumn('orders.region_id', 'regions.id'),
        ]);
    }

    public function scopeWithCaptain($query)
    {
        $query->addSelect([
            "captain_name" => Captain::select(DB::raw("CONCAT(firstname, ' ', lastname)"))->whereColumn('orders.captain_id', 'captains.id'),
        ]);
    }
    public function scopeWithCaptainIqamaNo($query)
    {
        $query->addSelect([
            'iqama_number' => Captain::select('iqama_number')
                ->whereColumn('orders.captain_id', 'captains.id')
                ->limit(1),
        ]);
    }

    public function scopeWithClient($query)
    {
        $query->addSelect([
            'client_name' => Client::select('users.name')
                ->leftJoin('users', 'users.id', 'clients.user_id')
                ->whereColumn('orders.client_id', 'clients.id'),
            'client_image' => Client::select('company_logo_path')->whereColumn('orders.client_id', 'clients.id'),
        ]);
    }

    public function scopeWithShop($query)
    {
        $query->addSelect([
            'shop_name' => ClientShop::select('name')->whereColumn('orders.shopname', 'client_shops.id'),
            'shop_location' => ClientShop::select('location')->whereColumn('orders.shopname', 'client_shops.id'),
        ]);
    }

    public function scopeWithShopRegionAndZone($query)
    {
        $query->addSelect([
            'shop_area' => Zone::select('zones.name')
                ->leftJoin('client_shops', 'client_shops.zone_id', 'zones.id')
                ->whereColumn('client_shops.id', 'orders.shopname'),
            'shop_zone' => ClientShop::select('regions.name')
                ->leftJoin('zones', 'zones.id', 'client_shops.zone_id')
                ->leftJoin('regions', 'regions.id', 'zones.region_id')
                ->whereColumn('client_shops.id', 'orders.shopname'),

        ]);
    }

    public function scopeWithLastLocation($query)
    {
        $query->addSelect([
            'logged_location' => OrderAddress::select(DB::raw('CONCAT(latitude, ",", longitude) as location'))->whereColumn('order_id', 'orders.id')->orderBy('id', 'desc')->limit(1),
        ]);
    }

    public function scopeOpen($query)
    {
        return $query
            ->whereIn('status_id', OrderStatus::OPEN_STATUSES);
    }

    public function scopeWithOpenTicket($query, $type, $name = 'open_ticket')
    {
        $query->addSelect([
            $name => Ticket::select('id')
                ->whereColumn('order_id', 'orders.id')
                ->when($type, function ($query, $type) {
                    return $query->where('tickets.type', $type);
                })
                ->whereNull('closed_at')
                ->limit(1),
        ]);
    }

    public function reroutable()
    {
        if ($this->lastPending() instanceof OrderLog && $this->lastPending()->reason_id == OrderPendingReason::REASON_FOR_REROUTE) {
            return true;
        }

        return false;
    }

    public function isRerouted()
    {
        $has_rerouted = $this->logs()->whereIn('status_id', [OrderStatus::REROUTED, OrderStatus::RELOCATED])->first();

        if ($has_rerouted) {
            return true;
        }

        return false;
    }

    public function isReachedDestination()
    {
        $has_reached_destination = $this->logs()->whereIn('status_id', [OrderStatus::REACHED_DESTINATION])->first();

        if ($has_reached_destination) {
            return true;
        }

        return false;
    }

    public function finished()
    {
        return in_array($this->status_id, [OrderStatus::DELIVERED, OrderStatus::CLIENT_RETURN_ACCEPTED, OrderStatus::CANCEL, OrderStatus::CANCEL_REQUEST_ACCEPTED]);
    }

    public function reDispatching()
    {
        $previous_log = $this->logsExecpt()->latest()->skip(1)->first();
        if ($previous_log && in_array($previous_log->status_id, [OrderStatus::DELIVERED, OrderStatus::CLIENT_RETURN_ACCEPTED, OrderStatus::CANCEL, OrderStatus::CANCEL_REQUEST_ACCEPTED]) && !in_array($this->status_id, [OrderStatus::DELIVERED, OrderStatus::CLIENT_RETURN_ACCEPTED, OrderStatus::CANCEL, OrderStatus::CANCEL_REQUEST_ACCEPTED])) {
            return true;
        }

        return false;
    }

    public function wasPreviousDelivered()
    {
        $previous_log = $this->logsExecpt()->latest()->skip(1)->first();
        if ($previous_log && in_array($previous_log->status_id, [OrderStatus::DELIVERED, OrderStatus::CLIENT_RETURN_ACCEPTED])) {
            return true;
        }
        return false;
    }

    public function previousCaptain()
    {
        $previous_logs = $this->logsExecpt()->latest()->get();

        return $previous_logs->first(function ($log) {
            return $log->captain_id;
        })->captain_id ?? null;
    }

    public function actionable()
    {
        return !in_array($this->status_id, [OrderStatus::DELIVERED, OrderStatus::CANCEL, OrderStatus::CLIENT_RETURN_ACCEPTED]);
    }

    public function complaintRaisable()
    {
        return !in_array($this->status_id, [OrderStatus::DELIVERED, OrderStatus::CANCEL, OrderStatus::CLIENT_RETURN_ACCEPTED]);
    }

    public function clientCancelable()
    {
        return in_array($this->status_id, [OrderStatus::NEW_ORDER, OrderStatus::ORDER_PACKAGE, OrderStatus::ASSIGN_ATTEMPTS, OrderStatus::NOT_ASSIGNED, OrderStatus::START_RIDE, OrderStatus::REACHED_SHOP, OrderStatus::ACCEPT]);
    }

    public function returnedToClient()
    {
        return $this->status_id == OrderStatus::RETURN_TO_CLIENT;
    }

    public function reAssignable()
    {
        return !in_array($this->status_id, [OrderStatus::DELIVERED, OrderStatus::CANCEL, OrderStatus::CLIENT_RETURN_ACCEPTED, OrderStatus::CLIENT_RETURN_DECLINE]);
    }
    public function scopeWithinDateRange($query, $startDate, $endDate, $column = 'orders.created_at')
    {
        return $query->whereBetween($column, [$startDate, $endDate]);
    }
    public function canceledOrderLogs()
    {
        return $this->belongsTo('App\OrderLog', 'id', 'order_id')->whereIn('order_logs.status_id', [OrderStatus::CANCEL, OrderStatus::CANCEL_REQUEST_ACCEPTED, OrderStatus::REQUEST_FOR_CANCEL])->latest();
    }

    public function estimatedArrivalTimes()
    {
        return $this->hasMany(OrderEstimatedArrivalTime::class);
    }

    public function acceptingRemainingTime()
    {
        $order_sended_at = $this->log()->latest()->where('status_id', OrderStatus::WAITING_FOR_ACCEPTING)->first()->created_at ?? now();

        return now()->diffInSeconds($order_sended_at->addMinutes(\App\Captain::CAPTAIN_ACCEPTING_TIME_IN_MINUTES), false);
    }

    public function updateCache()
    {

        if (in_array($this->status_id, OrderStatus::FINISHED)) {
            (new CacheOrder())->delete($this->id);
            return;
        }

        [$delivery_start_at, $delivery_finish_at] = $this->remainingTime();

        $data = [
            "id" => $this->id,
            "client_order_id" => $this->client_order_id,
            "client_id" => $this->client_id,
            "client_name" => $this->client->user->name,
            "shop_id" => $this->shop->id,
            "shop_name" => $this->shop->name,
            "zone_id" => $this->shop->zone->id ?? null,
            "zone" => $this->shop->zone->name ?? null,
            "area_id" => $this->shop->region->id ?? null,
            "area" => $this->shop->region->name ?? null,
            "region_id" => $this->shop->region->quadrant->id ?? null,
            "region" => $this->shop->region->quadrant->name ?? null,
            "amount" => $this->amount,
            "delivery_type" => $this->delivery_type,
            "delivery_date" => $this->delivery_date->timestamp,
            "delivery_start_at" => $delivery_start_at->timestamp,
            "delivery_finish_at" => $delivery_finish_at->timestamp,
            "status_id" => $this->status_id,
            "auto_assignable" => $this->shop->auto_assignable ? "true" : "false",
            "has_open_complaint" => $this->openComplaint ? "true" : "false",
            "captain_id" => $this->captain_id ?? null,
            "progress" => [
                "id" => $this->progress->id,
                "status" => $this->progress->name,
                "class" => $this->progress->status_class,
            ],
        ];

        if (
            in_array($this->status_id, [OrderStatus::NEW_ORDER, OrderStatus::ORDER_PACKAGE, OrderStatus::ASSIGN_ATTEMPTS]) &&
            $this->shop->auto_assignable == 1
        ) {
            $dispatch_after = ($this->package && $this->package->package) ? $this->package->package->dispatch_after : null;
            $data['dispatch_after'] = $dispatch_after ? $dispatch_after->timestamp : null;
        }

        if ($this->captain_id) {
            $data['captain'] = [
                "id" => $this->captain_id,
                "name" => $this->captain->user->name,
                "phone" => $this->captain->phone_number,
            ];
        }

        if ($this->openCaptainTicket) {
            $data['open_captain_ticket'] = [
                "id" => $this->openCaptainTicket->id,
                "unread_messages" => $this->openCaptainTicket->not_user_seen_messages_count,
            ];
        }

        if ($this->openComplaint) {
            $data['open_complaint'] = [
                "id" => $this->openComplaint->id,
                "unread_messages" => $this->openComplaint->not_user_seen_messages_count,
            ];
        }

        (new CacheOrder)->update($this->id, $data);
    }

    public function getFormattedCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->created_at)->format('m-d-Y h:i:s A');
    }

    public function getFormattedDeliveredAtAttribute()
    {
        return \Carbon\Carbon::parse($this->delivery_date)->format('m-d-Y h:i:s A');
    }

    public function simplifiedClientOrderId()
    {
        if ($this->client_id === Client::MC_DONALDS) {
            return \Illuminate\Support\Str::of($this->client_order_id)->explode('_')->last();
        }

        if ($this->client_id === Client::DOMINOS_PIZZA) {
            return \Illuminate\Support\Str::of($this->client_order_id)->explode('-')->last();
        }

        return $this->client_order_id;
    }


    public function scopeExcludeQuadrants($query, $column = null)
    {
        $parsed_column = $column ?? 'shop_region.quadrant_id';
        return $query
            ->when(!$column, function ($query) {
                return $query->leftJoin('client_shops', 'client_shops.id', 'orders.shopname')
                    ->leftJoin('zones as shop_zone', 'shop_zone.id', 'client_shops.zone_id')
                    ->leftJoin('regions as shop_region', 'shop_region.id', 'shop_zone.region_id');
            })
            ->whereNotIn($parsed_column, Quadrant::HIDEABLE_ON_GRAPH);

    }
    public function etaShopToDestination(Captain $captain)
    {

        $shop = $this->shop;
        $destination = $this->location;

        if (!$captain) {
            $captain = $this->captain;
        }

        $captain_location = $captain->location;

        if (!$destination || !$captain_location) {
            return -1;
        }

        return Cache::remember("order-{$this->id}-captain-{$captain->id}-to-destination-eta", 30, function () use ($captain, $captain_location, $destination) {
            $captainLocation = new Position($captain_location->longitude, $captain_location->latitude);
            $destinationLocation = new Position(...collect(explode(',', $destination))->reverse()->toArray());

            $estimated_time = (new Map('google'))
                ->direction($captainLocation, $destinationLocation)
                ->duration();

            $estimations = $this->estimatedArrivalTimes()->where('captain_id', $captain->id)->first();

            if ($estimations) {
                OrderEstimatedArrivalTime::query()
                    ->where([
                        'order_id' => $this->id,
                        'captain_id' => $captain->id,
                    ])
                    ->update([
                        'estimated_arrival_time_at_customer' => $estimated_time,
                        'estimated_arrival_time_at_customer_updated_at' => now(),
                    ]);
            } else {
                $this->estimatedArrivalTimes()->create([
                    'captain_id' => $captain->id,
                    'estimated_arrival_time_at_customer' => $estimated_time,
                    "estimated_arrival_time_at_customer_updated_at" => now(),
                ]);
            }

            return $estimated_time;
        });
    }

    public function etaCaptainToShop(Captain $captain)
    {
        if (!in_array($this->status_id, [...OrderStatus::NOT_ASSIGNED_ORDER, OrderStatus::ACCEPT, OrderStatus::START_RIDE])) {
            return -1;
        }

        if (!$captain) {
            $captain = $this->captain;
        }

        if (!$captain) {
            return $this->shop->getAvgCaptainToShopDuration();
        }

        $captain_location = $captain->location;

        $shop = $this->shop->location;

        if (!$captain_location || !$shop) {
            return $this->shop->getAvgCaptainToShopDuration();
        }

        return Cache::remember("order-{$this->id}-captain-{$captain->id}-to-shop-eta", 30, function () use ($captain, $captain_location, $shop) {
            $captainLocation = new Position($captain_location->longitude, $captain_location->latitude);
            $shopLocation = new Position(...collect(explode(',', $shop))->reverse()->toArray());

            $estimated_time = (new Map('google'))
                ->direction($captainLocation, $shopLocation)
                ->duration();

            $estimations = $this->estimatedArrivalTimes()->where('captain_id', $captain->id)->first();

            if ($estimations) {
                OrderEstimatedArrivalTime::query()
                    ->where([
                        'order_id' => $this->id,
                        'captain_id' => $captain->id,
                    ])
                    ->update([
                        'estimated_arrival_time_at_store' => $estimated_time,
                        'estimated_arrival_time_at_store_updated_at' => now(),
                    ]);
            } else {
                $this->estimatedArrivalTimes()->create([
                    'captain_id' => $captain->id,
                    'estimated_arrival_time_at_store' => $estimated_time,
                    'estimated_arrival_time_at_store_updated_at' => now(),
                ]);
            }

            return $estimated_time;

        });
    }

    public function averagePickupDuration()
    {
        return $this->shop->getAvgPickupDuration();
    }

    public function captainHandOverTime()
    {
        return 60;
    }

    public function eta()
    {
        if (!$this->location) {
            return -1;
        }

        return Cache::remember('order-eta-' . $this->id, 200, function () {
            $order_assign_time = 0;
            $captain_reaching_time = 0;
            $captain_pickup_time = 0;
            $captain_reaching_time_at_destination = 0;
            $captain_delivery_time = 0;

            if (in_array($this->status_id, [...OrderStatus::NOT_ASSIGNED_ORDER, OrderStatus::ACCEPT, OrderStatus::REACHED_SHOP, OrderStatus::ACCEPT, OrderStatus::START_RIDE, OrderStatus::REACHED_SHOP, OrderStatus::PICKED])) {
                $captain_reaching_time_at_destination = $this->etaShopToDestination();

                if (in_array($this->status_id, [...OrderStatus::NOT_ASSIGNED_ORDER, OrderStatus::ACCEPT, OrderStatus::REACHED_SHOP, OrderStatus::ACCEPT, OrderStatus::START_RIDE, OrderStatus::REACHED_SHOP])) {
                    $captain_pickup_time = $this->averagePickupDuration(); // 60
                    if ($captain_pickup_time > $captain_reaching_time) {
                        $captain_pickup_time = $captain_pickup_time - $captain_reaching_time;
                    } else {
                        $captain_pickup_time = 0;
                    }
                }

                if (in_array($this->status_id, [...OrderStatus::NOT_ASSIGNED_ORDER, OrderStatus::ACCEPT, OrderStatus::START_RIDE])) {
                    $captain_reaching_time = $this->etaCaptainToShop(); //120
                }

                if (in_array($this->status_id, OrderStatus::NOT_ASSIGNED_ORDER)) {
                    $order_assign_time = $this->shop->getAvgOrderAssignTime();
                }

                if (in_array($this->status_id, [OrderStatus::REACHED_DESTINATION])) {
                    $captain_delivery_time = $this->captainHandOverTime();
                }
            }

            return $order_assign_time + $captain_reaching_time + $captain_pickup_time + $captain_reaching_time_at_destination + $captain_delivery_time;
        });
    }
}
