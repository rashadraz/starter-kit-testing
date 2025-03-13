<?php
namespace App;

use App\Traits\Logable;
use App\Traits\OtpVerifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Captain extends Model
{
    use OtpVerifiable, Logable;
    const STATUS_ACTIVE = 'Active';
    const STATUS_INACTIVE = 'Inactive';
    const STATUS_LEAVE = 'Leave';
    const STATUS_BANNED = 'Banned';
    const STATUS_REQUEST = 'Request';

    const JOB_TYPE_FULLTIME = 'Full-Time';
    const JOB_TYPE_PART_TIME = 'Part Time';
    const JOB_TYPE_THIRD_PARTY = 'Third Party';

    const CAPTAIN_ACCEPTING_TIME_IN_MINUTES = 1;

    protected $guarded = [];

    protected $appends = ['profile_pic_path'];
    public $with = [];

    protected $casts = [
        'iqama_expiry_date' => 'date',
        'licence_expiry_date' => 'date',
        'rent_valid_from' => 'date',
        'date_of_joining' => 'date',
    ];

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function vehicle()
    {
        return $this->belongsTo('App\Vehicle', 'id', 'assigned_to');
    }

    public function zone()
    {
        return $this->hasOne('App\Zone', 'id', 'zone_id');
    }

    public function shopPayments()
    {
        return $this->hasMany(ShopPayment::class, "captain_id");
    }

    public function orders()
    {
        return $this->hasMany('App\Order', 'captain_id', 'id')->with('payment', 'shopPayment');
    }

    public function deliveredOrders()
    {
        return $this->hasMany('App\Order', 'captain_id', 'id')->where('status_id', OrderStatus::DELIVERED);
    }

    public function todayFinishedOrders()
    {
        return $this->deliveredOrders()
            ->where(function ($query) {
                if (now()->greaterThan(now()->parse(now()->format('Y-m-d') . ' ' . '9:00'))) {
                    $from_time = now()->format('Y-m-d') . ' ' . '8:00';
                    $to_time = now()->addDay()->format('Y-m-d') . ' ' . '7:59';
                } else {
                    $from_time = now()->subDay()->format('Y-m-d') . ' ' . '8:00';
                    $to_time = now()->format('Y-m-d') . ' ' . '7:59';
                }
                $query->whereBetween('delivery_date', [$from_time, $to_time]);
            });
    }

    public function ordersByStatus($status_id = null)
    {
        return $this->hasMany(Order::class, 'captain_id', 'id')
            ->when($status_id, function ($query, $status_id) {
                $query->where('status_id', $status_id);
            });
    }

    public function shifts()
    {
        return $this->hasMany('App\ShiftStatus', 'captain_id', 'id');
    }

    public function activeToday()
    {
        return $this->shifts()
            ->where(function ($query) {
                $query->where('shift_start', '>=', now()->format('Y-m-d') . ' 00:00:00')
                    ->orWhere('shift_end', '>=', now()->format('Y-m-d') . ' 00:00:00');
            });
    }

    public function activeTodaySeconds(): int
    {
        if (!$this->currentShift) {
            return 0;
        }

        return $this
            ->activeToday()
            ->select(DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(COALESCE(shift_end, now()), shift_start))) as seconds'))
            ->first()
            ->seconds ?? 0;
    }

    public function currentShift()
    {
        return $this->hasOne(ShiftStatus::class)->where('shift_end', null);
    }

    public function currentOrder()
    {
        return $this->hasMany(Order::class)
            ->whereNotIn('status_id', [
                OrderStatus::DELIVERED,
                OrderStatus::CANCEL_REQUEST_ACCEPTED,
                OrderStatus::CANCEL,
                OrderStatus::CLIENT_RETURN_ACCEPTED,
                OrderStatus::RETURN_TO_FORYOU,
                OrderStatus::FORYOU_RETURN_ACCEPTED,
                OrderStatus::RETURN_TO_CLIENT,
                OrderStatus::CLIENT_RETURN_DECLINE,
            ]);
    }

    public function autoAssignPriority()
    {
        return $this->belongsTo(AutoAssignPriority::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Country::class, 'nationality_id');
    }

    public function employmentType()
    {
        return $this->belongsTo(CaptainEmploymentType::class, 'captain_employment_type_id');
    }

    public function lastCommission()
    {
        return $this->hasOne(CaptainCommission::class)->latest('id');
    }

    public function commissions()
    {
        return $this->hasMany(CaptainCommission::class);
    }

    public function filterCommissions()
    {

        return $this->hasMany(CaptainCommission::class);

    }

    public function vehicleRents()
    {
        return $this->hasMany(CaptainVehicleRent::class);
    }

    public function vehicleRentSettlement()
    {
        return $this->hasMany(CaptainVehicleRentSettlement::class);
    }

    public function loginOtp()
    {
        return $this->morphOne(Otp::class, 'verifiable')->latest();
    }

    public function banningHistories()
    {
        return $this->hasMany(CaptainBanningHistory::class);
    }

    public function updatedHistories()
    {
        return $this->hasMany(CaptainUpdatedLog::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(CaptainUpdatedLog::class)->where('mode', '=', '1');
    }

    public function autoAssignHistories()
    {
        return $this->hasMany(CaptainUpdatedLog::class)->where('mode', '=', '2');
    }

    public function scopeOnline($query)
    {
        return $query->has('currentShift');
    }

    public function isOnline()
    {
        return $this->currentShift()->exists();
    }

    public function todayActiveSeconds()
    {
        if (now()->greaterThan(now()->parse(now()->format('Y-m-d') . ' ' . '9:00'))) {
            $from_time = now()->format('Y-m-d') . ' ' . '8:00:00';
            $to_time = now()->addDay()->format('Y-m-d') . ' ' . '7:59:59';
        } else {
            $from_time = now()->subDay()->format('Y-m-d') . ' ' . '8:00:00';
            $to_time = now()->format('Y-m-d') . ' ' . '7:59:59';
        }

        $shifts = $this
            ->shifts()
            ->where(function ($query) use ($from_time, $to_time) {
                $query->whereBetween('shift_start', [$from_time, $to_time])
                    ->orWhereBetween('shift_end', [$from_time, $to_time]);
            })
            ->get();
        $active_seconds = 0;
        foreach ($shifts as $shift) {
            $active_seconds += $shift->activeSeconds($from_time, $to_time);
        }
        return $active_seconds;
    }

    public function scopeCommissionedCaptain($query)
    {
        return $query->whereIn('captain_employment_type_id', [CaptainEmploymentType::FREELANCER_OWN_VEHICLE, CaptainEmploymentType::RENTED_FREELANCER, CaptainEmploymentType::SPONSORED]);
    }

    public function scopeAllCommissionedCaptain($query)
    {
        return $query->whereIn('captain_employment_type_id', [
            CaptainEmploymentType::FREELANCER_OWN_VEHICLE,
            CaptainEmploymentType::RENTED_FREELANCER,
            CaptainEmploymentType::SPONSORED,
            CaptainEmploymentType::THIRD_PARTY,
        ]);
    }

    public function scopeRentalCaptain($query)
    {
        return $query->where('captain_employment_type_id', CaptainEmploymentType::RENTED_FREELANCER);
    }

    public function scopeOffline($query)
    {
        return $query->doesntHave('currentShift');
    }

    public function scopeOnlineFree($query)
    {
        $query->has('currentShift')
            ->doesntHave('currentOrder');
    }

    public function scopeOnlineBusy($query)
    {
        $query->has('currentShift')
            ->has('currentOrder');
    }

    public function scopeIdle($query)
    {
        $query->whereHas('location', function ($q) {
            $q->where('last_updated_at', '<', now()->subHour());
        });
    }

    public function scopeBelongsToMe($query)
    {
        if (in_array(auth()->user()->data_permission, [User::DATA_PERMISSION_ZONE_BASED, User::DATA_PERMISSION_REGION_BASED])) {
            return $query->whereHas('regions', function ($query) {
                $query->belongsToMe();
            });
        }

        if (auth()->user()->data_permission == User::DATA_PERMISSION_BRANCH_BASED) {
            $zone_id = auth()->user()->dataPermission()->pluck('zone_id')->unique();
            return $query->whereHas('region.zones', function ($query) use ($zone_id) {
                $query->whereIn('id', $zone_id);
            });
        }

        if (auth()->user()->data_permission == User::DATA_PERMISSION_CLIENT_BASED) {
            $zone_id = DB::table('client_shop_zones')
                ->select('client_shop_zones.zone_id')
                ->whereRaw("
                                client_shop_id IN
                                    (SELECT id FROM client_shops WHERE client_shops.client_id IN (" . auth()->user()->dataPermission()->pluck('id')->join(',') . ") )"
                )->distinct()->pluck('zone_id');

            return $query->whereHas('region.zones', function ($query) use ($zone_id) {
                $query->whereIn('id', $zone_id);
            });
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('captains.status', self::STATUS_ACTIVE);
    }

    public function scopeInActive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeShiftStaredBefore($query, $time)
    {
        $query->whereHas('currentShift', function ($query) use ($time) {
            $query->where('shift_start', '<', $time);
        });
    }

    public function scopeHasReminderPaused($query, $reminder_type, $time)
    {
        $query->whereDoesntHave('reminders', function ($query) use ($reminder_type, $time) {
            $query
                ->where('reminder_type', $reminder_type)
                ->whereNotNull('pause_upto')
                ->where('pause_upto', '>', $time);
        });
    }

    public function scopeBelongsTo3pl($query, $company_id = null)
    {
        if (!$company_id) {
            $company_id = session('company_id_3pl');
        }

        return $query->whereHas('captainThirdParty', function ($query) use ($company_id) {
            $query->where('third_party_logistic_company_id', $company_id);
        });

    }

    public function scopeWithName($query, $key = 'name')
    {
        $query->addSelect([
            $key => User::select("name")->whereColumn('users.id', 'captains.user_id'),
        ]);
    }

    public function scopeWithCommissionBalance($query)
    {
        return $query->addSelect([
            'commission_balance' => CaptainCommission::select('balance')
                ->whereColumn('captain_commissions.captain_id', 'captains.id')
                ->latest('id')
                ->take(1),
        ]);
    }

    public function scopeWithCurrentLocationDistance($query, $location)
    {
        $location = explode(',', $location);
        return $query->addSelect([
            'distance' => DB::raw('(ST_Distance_Sphere(POINT(captain_location_logs.latitude, captain_location_logs.longitude), POINT(' . (float) $location[0] . ',' . (float) $location[1] . ')) * 0.001) as distance'),
        ])->leftJoin(
                DB::raw('(SELECT MAX(id) AS max_id, captain_id FROM captain_location_logs GROUP BY captain_id) as last_location_log'),
                'captains.id',
                '=',
                'last_location_log.captain_id'
            )->leftJoin('captain_location_logs', 'last_location_log.max_id', '=', 'captain_location_logs.id');
    }

    public function scopeWithVehicleType($query)
    {
        $query->addSelect([
            'vehicle_type_id' => DB::table('vehicles')
                ->select('vehicle_types.id')
                ->leftJoin('vehicle_types', 'vehicle_types.id', 'vehicles.type')
                ->whereColumn('vehicles.assigned_to', 'captains.id'),

        ]);
    }

    public function scopeWithCaptainArrivalTimeAtStore($query, $order)
    {
        $query->addSelect([
            'eta' => OrderEstimatedArrivalTime::select('estimated_arrival_time_at_store')
                ->where('order_estimated_arrival_times.order_id', $order->id)
                ->where('order_estimated_arrival_times.captain_id', $order->captain_id)
                ->take(1),
        ]);
    }

    public function scopeWithCaptainArrivalTimeAtDestination($query, $order)
    {
        $query->addSelect([
            'eta' => OrderEstimatedArrivalTime::select('estimated_arrival_time_at_customer')
                ->where('order_estimated_arrival_times.order_id', $order->id)
                ->where('order_estimated_arrival_times.captain_id', $order->captain_id)
                ->take(1),
        ]);
    }

    public function idle()
    {
        return $this->location()->where('last_updated_at', '<', now()->subMinutes(5));
    }

    public function OrderPayment()
    {
        return $this->hasMany('App\OrderPayment', 'captain_id', 'id');
    }

    public function orderPayableBalance()
    {
        return $this->hasOne(CaptainOrderPayment::class, 'captain_id')->latest();
    }

    public function captainOrderPayments()
    {
        return $this->hasMany(CaptainOrderPayment::class, 'captain_id');
    }

    public function account()
    {
        return $this->belongsTo('App\Account', 'id', 'captain_id');
    }

    public function ordersDelivered()
    {
        return $this->hasMany('App\Order', 'captain_id', 'id')->where('status_id', OrderStatus::DELIVERED);
    }

    public function asset()
    {
        return $this->hasMany('App\asset', 'captain_id', 'id');
    }

    public function ordersReturnedForyou()
    {
        return $this->hasMany('App\Order', 'captain_id', 'id')->where('status_id', OrderStatus::FORYOU_RETURN_ACCEPTED);
    }

    public function ordersReturned()
    {
        return $this->hasMany(Order::class, 'captain_id', 'id')->where('status_id', OrderStatus::CLIENT_RETURN_ACCEPTED);
    }

    public function packageRequests()
    {
        return $this->hasMany(PackageDeliveryRequest::class);
    }

    public function region()
    {
        return $this->hasOne('App\Region', 'id', 'region_id');
    }

    public function regions()
    {
        return $this->belongsToMany(Region::class);
    }

    public function preferredWorkingLocation()
    {
        return $this->belongsTo(Quadrant::class, 'preferred_working_location');
    }

    public function partner()
    {
        return $this->hasOne('App\Partner', 'id', 'sponsor_name');
    }

    public function transactions()
    {
        return $this->hasMany('App\Transaction', 'captain_id', 'id');
    }

    public function document()
    {
        return $this->hasOne('App\CaptainDocument', 'captain_id', 'id');
    }

    public function pickedOrders()
    {
        return $this->belongsToMany(Order::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'request_converted_by');
    }

    public function location()
    {
        $last_log_ids = DB::table('captain_location_logs')->select(DB::raw('max(id) as id'))->groupBy('captain_id')->get();
        $last_log_ids = $last_log_ids->pluck('id')->toArray();
        return $this->hasOne(CaptainLocationLog::class)->whereIn('id', $last_log_ids);
    }

    public function locations()
    {
        return $this->hasMany(CaptainLocationLog::class);
    }

    public function accessToken()
    {
        return $this->hasOne(AccessToken::class, 'captain_id');
    }

    public function vehicleFleets()
    {
        return $this->hasMany(VehicleFleet::class)->latest();
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class)->latest();
    }

    public function reminder()
    {
        return $this->hasOne(Reminder::class)
            ->whereRaw('reminders.id = (SELECT MAX(last_reminder.id) FROM reminders as last_reminder where last_reminder.captain_id = captains.id)');
    }

    public function captainVehicle()
    {
        return $this->belongsTo(VehicleCaptain::class, 'id', 'captain_id')->whereNull('to_date')->orWhere(function ($query) {
            $query->where('to_date', '>', now()->format('Y-m-d'));
            $query->where('status', 'Active');
        });
    }

    public function commissionRule()
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    public function pendingVehicleFleets()
    {
        return $this->vehicleFleets()->where('status_id', VehicleFleetStatus::PENDING);
    }

    public function acceptedVehicleFleets()
    {
        return $this->vehicleFleets()->where('status_id', VehicleFleetStatus::ACCEPTED);
    }

    public function receivableVehicleFleets()
    {
        return $this->vehicleFleets()->whereIn('status_id', [VehicleFleetStatus::ACCEPTED, VehicleFleetStatus::PENDING]);
    }

    public function receivedVehicleFleets()
    {
        return $this->vehicleFleets()->where('status_id', VehicleFleetStatus::RECEIVED);
    }

    public function current_status()
    {
        if ($this->currentShift && $this->location) {
            if ($this->currentOrder->isNotEmpty()) {
                return '- engaged with ' . ($this->current_order_count ?? $this->currentOrder->count()) . ' orders (' . (isset($this->location->zone) ? $this->location->zone->name : 'Unknown') . ')';
            }
            return (now()->subMinutes(2)->diffInMinutes($this->location->last_updated_at) < 3 ? '- Free' : '- Idle ' . $this->location->last_updated_at->diffForHumans()) . ' (' . (isset($this->location->zone) ? $this->location->zone->name : 'Unknown') . ')';
        }
        return $this->location ? $this->location->last_updated_at->diffForHumans() : '';
    }

    public function setDateOfJoiningDateAttribute($date)
    {
        return $this->attributes['date_of_joining'] = formatdate($date);
    }

    public function setIqamaExpiryDateAttribute($date)
    {
        return $this->attributes['iqama_expiry_date'] = $date;
    }

    public function setLicenceExpiryDateAttribute($date)
    {
        return $this->attributes['licence_expiry_date'] = $date;
    }

    public function setRcExpiryDateAttribute($date)
    {
        return $this->attributes['rc_expiry_date'] = $date;
    }

    public function setInsuranceExpiryDateAttribute($date)
    {
        return $this->attributes['insurance_expiry_date'] = $date;
    }

    public function getProfilePicPathAttribute()
    {
        if (!$this->document || !$this->document->profile_pic) {
            return $this->attributes['profile_pic_path'] = asset('images/users/avatar.png');
        }
        return $this->attributes['profile_pic_path'] = asset('images/captain_profiles/' . $this->document->profile_pic);
    }

    public function getOnlineStateAttribute($date)
    {
        $state = ['Offline'];

        if ($this->currentShift && $this->currentOrder->count() == 0) {
            $state = ['Free'];
        }

        if ($this->currentShift && $this->currentOrder->count() > 0) {
            $state = ['Busy'];
        }

        if ($this->currentShift && (!isset($this->location) || (isset($this->location->last_updated_at) && now()->diffInMinutes($this->location->last_updated_at) > 5))) {
            $state[] = 'Idle';
        }

        return $this->attributes['online_state'] = $state;
    }

    public function sponsored()
    {
        return $this->captain_employment_type_id == CaptainEmploymentType::SPONSORED;
    }

    public function earningCommission()
    {
        return in_array($this->captain_employment_type_id, [CaptainEmploymentType::FREELANCER_OWN_VEHICLE, CaptainEmploymentType::RENTED_FREELANCER, CaptainEmploymentType::SPONSORED, CaptainEmploymentType::THIRD_PARTY]);
    }

    public function rental()
    {
        return $this->captain_employment_type_id == CaptainEmploymentType::RENTED_FREELANCER;
    }

    public function payableVehicleRent()
    {
        $total_payable = $this->vehicleRents()->sum('amount');
        if ($total_payable < 1) {
            return 0;
        }

        $total_payed = $this->vehicleRentSettlement()->sum('amount');
        return $total_payable - $total_payed;
    }

    public function payableAmount()
    {

        $balance = $this->orderPayableBalance()->first();

        if (!$balance) {
            return 0;
        }

        return $balance->balance > 0 ? $balance->balance : 0;
    }

    public function receivableAmount()
    {
        $balance = $this->orderPayableBalance()->first();

        if (!$balance) {
            return 0;
        }

        return $balance->balance < 0 ? abs($balance->balance) : 0;
    }

    public function nearestCaptainDistance($location)
    {
        $captains = $this->query()
            ->select('captains.id', DB::raw('ST_Distance_Sphere(POINT(captain_location_logs.latitude, captain_location_logs.longitude), POINT(' . (float) $location[0] . ',' . (float) $location[1] . ')) as distance'))
            ->leftJoin(
                DB::raw('(SELECT MAX(id) AS max_id, captain_id FROM captain_location_logs GROUP BY captain_id) as last_location_log'),
                'captains.id',
                '=',
                'last_location_log.captain_id'
            )
            ->leftJoin('captain_location_logs', 'last_location_log.max_id', '=', 'captain_location_logs.id')
            ->onlineFree()
            ->orderBy('distance')
            ->get();

        return $captains->first() ? $captains->first()->distance : null;
    }

    public function paidSalary()
    {
        return $this->hasMany(CaptainSalaryPayment::class, 'captain_id');
    }

    public function firebaseVersion()
    {
        return $this->appVersion() < 40 ? 0 : 'v1';
    }

    public function appVersion()
    {
        return (int) preg_replace("/[^0-9]/", "", $this->current_using_app_version);
    }

    public function company()
    {
        return $this->hasOneThrough(ThirdPartyLogisticCompany::class, CaptainThirdPartyLogistic::class, 'captain_id', 'id', 'id', 'third_party_logistic_company_id');
    }

    public function captainThirdParty()
    {
        return $this->hasOne(CaptainThirdPartyLogistic::class, 'captain_id');

    }

    public static function generateCaptainId()
    {
        $captainId = 'MCAP-001';

        if (Captain::count() > 0) {
            $lastClient = Captain::orderBy('id', 'desc')->first();
            $lastClientCode = explode('-', $lastClient->code);
            $clientCount = $lastClientCode[1] + 1;
            $numlength = strlen((string) $clientCount);
            if ($numlength == 1) {
                $captainId = 'MCAP-00' . $clientCount;
            }

            if ($numlength == 2) {
                $captainId = 'MCAP-0' . $clientCount;
            }

            if ($numlength >= 3) {
                $captainId = 'MCAP-' . $clientCount;
            }
        }
        return $captainId;
    }

    protected function averageWorkTime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->working_days
            ? secondsToTime($this->total_seconds_worked / $this->working_days)
            : '00:00:00'
        );
    }

    protected function acceptanceRate(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_orders_received
            ? number_format(($this->total_orders_accepted / $this->total_orders_received) * 100, 2)
            : '0.00'
        );
    }

    protected function deliveryRate(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_orders_accepted
            ? number_format(($this->total_orders_delivered / $this->total_orders_accepted) * 100, 2)
            : '0.00'
        );
    }

    protected function diffInOrders(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_orders_accepted
            - ($this->total_orders_delivered + $this->total_orders_returned + $this->total_orders_cancelled)
        );
    }

    public function scopeExcludeQuadrants($query)
    {
        return $query->whereHas('regions', function ($regionQuery) {
            $regionQuery->whereNotIn('quadrant_id', Quadrant::HIDEABLE_ON_GRAPH);
        });
    }
}
