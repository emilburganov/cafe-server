<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ShiftResource;
use App\Models\Order;
use App\Models\WorkShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WaiterController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function storeOrders(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'work_shift_id' => 'required|integer|exists:work_shifts,id',
            'table_id' => 'required|integer|exists:restaurant_tables,id',
            'number_of_person' => 'integer',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $workShift = WorkShift::query()->find($request->work_shift_id);

        if (boolval($workShift->active) === false) {
            return $this->baseError('Forbidden. The shift must be active!', 403);
        }

        if (!$workShift->users()->where('user_id', Auth::id())->exists()) {
            return $this->baseError('Forbidden. You don\'t work this shift!', 403);
        }

        $order = Order::query()->create([
            'work_shift_id' => $request->work_shift_id,
            'restaurant_table_id' => $request->table_id,
            'user_id' => Auth::id(),
            'status' => 'Принят',
        ]);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function showOrders(Order $order): JsonResponse
    {
        if ($order->user_id !== Auth::id()) {
            return $this->baseError('Forbidden. You did not accept this order!', 403);
        }
        return response()->json([
            'data' =>
                new OrderResource($order),
            'positions' => [
                [
                    'id' => 1,
                    'count' => 5,
                    'position' => 'Aut sit ut et reprehenderit sed cumque.'
                ],
                [
                    'id' => 2,
                    'count' => 1,
                    'position' => 'Ut similique dolorum eos culpa officiis.'
                ]
            ],
        ]);
    }

    /**
     * @param WorkShift $workShift
     * @return JsonResponse
     */
    public function getShiftOrders(WorkShift $workShift): JsonResponse
    {
        $orders = $workShift->orders;

        if (!$workShift->users()->where('user_id', Auth::id())->exists()) {
            return $this->baseError('Forbidden. You did not accept this order!', 403);
        }

        return response()->json([
            'data' => [
                'id' => $workShift->id,
                'start' => $workShift->start,
                'end' => $workShift->end,
                'active' => $workShift->active,
                'orders' => OrderResource::collection($orders),
            ]
        ]);
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     */
    public function changeOrderStatus(Request $request, Order $order): JsonResponse
    {

        if (Auth::user()->role_id !== 2 && Auth::user()->role_id !== 3) {
            return $this->baseError('Forbidden for you', 403);
        }

        $v = Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        if (
            !($order->status === 'Принят' && $request->status === 'Отменен') &&
            !($order->status === 'Готов' && $request->status === 'Оплачен') &&
            Auth::user()->role_id === 2
        ) {
            return $this->baseError('Forbidden! Can\'t change existing order status', 403);
        }

        if (
            !($order->status === 'Принят' && $request->status === 'Готовится') &&
            !($order->status === 'Готовится' && $request->status === 'Готов') &&
            Auth::user()->role_id === 3
        ) {
            return $this->baseError('Forbidden! Can\'t change existing order status', 403);
        }

        if (Auth::user()->role_id === 2 && $order->user_id !== Auth::id()) {
            return $this->baseError('Forbidden. You did not accept this order!', 403);
        }

        if (boolval($order->shift->active) === false) {
            return $this->baseError('You cannot change the order status of a closed shift!', 403);
        }

        $order->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
            ]
        ]);
    }
}
