<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\WorkShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CookController extends Controller
{
    /**
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     */
    public function changeOrderStatus(Request $request, Order $order): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        if (
            !($order->status === 'Принят' && $request->status === 'Готовится') &&
            !($order->status === 'Готовится' && $request->status === 'Готов')
        ) {
            return $this->baseError('Forbidden! Can\'t change existing order status', 403);
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

    /**
     * @return JsonResponse
     */
    public function getActualOrders(): JsonResponse
    {
        $workShift = WorkShift::query()->firstWhere('active', true);

        if ($workShift) {
            $orders = $workShift->orders()->whereIn('status', ['Принят', 'Готовится'])->get();

            return response()->json([
                'data' => OrderResource::collection($orders)
            ]);
        }

        return response()->json([
            'data' => []
        ]);
    }
}
