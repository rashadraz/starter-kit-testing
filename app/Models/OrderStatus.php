<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderStatus extends Model
{
    const NEW_ORDER               = 1;
    const NOT_ASSIGNED            = 2;
    const ACCEPT                  = 3;
    const START_RIDE              = 4;
    const REACHED_SHOP            = 5;
    const PICKED                  = 6;
    const PICKED_UP               = 7;
    const SHIPPED                 = 8;
    const REACHED_DESTINATION     = 9;
    const DELIVERED               = 10;
    const REQUEST_FOR_CANCEL      = 11;
    const CANCEL_REQUEST_ACCEPTED = 12;
    const RETURN_TO_FORYOU        = 13;
    const FORYOU_RETURN_ACCEPTED  = 14;
    const RETURN_TO_CLIENT        = 15;
    const CLIENT_RETURN_ACCEPTED  = 16;
    const INCOMPLETE              = 17;
    const PENDING                 = 18;
    const CANCEL                  = 19;
    const REFUSE                  = 20;
    const TICKET_RAISED           = 21;
    const REROUTED                = 22;
    const ORDER_PACKAGE           = 24;
    const ASSIGN_ATTEMPTS         = 23;
    const CLIENT_RETURN_DECLINE   = 25;
    const RELOCATED               = 26;
    const WAITING_FOR_ACCEPTING   = 27;
    const WAITING_TIME_OUT        = 28;
    const CAPTAIN_ORDER_REJECTED  = 29;

    const LOGISTICS_STATUSES = [3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 15, 16, 18, 19, 21, 22];

    const OPEN_STATUSES = [
        OrderStatus::NEW_ORDER,
        OrderStatus::ORDER_PACKAGE,
        OrderStatus::ASSIGN_ATTEMPTS,
        OrderStatus::NOT_ASSIGNED,
        OrderStatus::ACCEPT,
        OrderStatus::START_RIDE,
        OrderStatus::REACHED_SHOP,
        OrderStatus::PICKED,
        OrderStatus::PICKED_UP,
        OrderStatus::SHIPPED,
        OrderStatus::REACHED_DESTINATION,
        OrderStatus::REROUTED,
        OrderStatus::TICKET_RAISED,
    ];

    const NOT_ASSIGNED_ORDER = [
        OrderStatus::NEW_ORDER,
        OrderStatus::NOT_ASSIGNED,
        OrderStatus::ORDER_PACKAGE,
        OrderStatus::ASSIGN_ATTEMPTS,
    ];

    const ON_GOING_ORDER = [
        OrderStatus::ACCEPT,
        OrderStatus::START_RIDE,
        OrderStatus::REACHED_SHOP,
        OrderStatus::PICKED,
        OrderStatus::PICKED_UP,
        OrderStatus::SHIPPED,
        OrderStatus::REACHED_DESTINATION,
        OrderStatus::REROUTED,
    ];

    const FINISHED = [
        OrderStatus::DELIVERED,
        OrderStatus::CANCEL,
        OrderStatus::FORYOU_RETURN_ACCEPTED,
        OrderStatus::CLIENT_RETURN_ACCEPTED,
        OrderStatus::CANCEL_REQUEST_ACCEPTED,
    ];

    public $appends = ['status_class'];

    // getter status class
    public function getStatusClassAttribute()
    {
        return static::getBadgeClass($this->id);
    }

    public static function getBadgeClass($status_id)
    {
        switch ($status_id) {
            case static::NEW_ORDER:
                return 'badge badge-warning';
                break;
            case static::ACCEPT:
                return 'badge badge-primary';
                break;
            case static::START_RIDE:
                return 'badge badge-secondary';
                break;
            case static::REACHED_SHOP:
                return 'badge badge-info';
                break;
            case static::SHIPPED:
                return 'badge badge-dark';
                break;
            case static::DELIVERED:
                return 'badge badge-success';
                break;
            case static::CANCEL:
                return 'badge badge-success brown';
                break;
            case static::RETURN_TO_FORYOU:
                return 'badge badge-success orange';
                break;
            case static::RETURN_TO_CLIENT:
                return 'badge badge-success gold';
                break;
            case static::CLIENT_RETURN_ACCEPTED:
                return 'badge badge-return-client';
                break;
            case static::FORYOU_RETURN_ACCEPTED:
                return 'badge badge-return-foryou';
                break;
            case static::NOT_ASSIGNED:
                return 'badge badge-danger';
                break;
            case static::PENDING:
                return 'badge badge-foryou-pending';
                break;
            case static::REQUEST_FOR_CANCEL:
                return 'badge badge-foryou-request-for-cancel';
                break;
            case static::CLIENT_RETURN_ACCEPTED:
                return 'badge badge-cancel-request-accepted';
                break;
            case static::PICKED:
                return 'badge badge-order-picked';
                break;

            default:
                return 'badge ' . Str::slug(static::find($status_id)->name);
                # code...
                break;
        }
    }
    public function scopeLogisticStatuses($query)
    {
        return $query->whereIn('id', static::LOGISTICS_STATUSES);
    }
}
