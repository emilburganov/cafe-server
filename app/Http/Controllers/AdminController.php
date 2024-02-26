<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ShiftResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function getUsers(): JsonResponse
    {
        $users = User::all();

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    public function storeUsers(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string',
            'surname' => 'string',
            'patronymic' => 'string',
            'login' => 'required|string|unique:users,login',
            'password' => 'required|string',
            'photo_file' => 'image|mimes:jpeg,png',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $user = User::query()->create([
            'name' => $request->name,
            'surname' => $request->surname,
            'patronymic' => $request->patronymic,
            'login' => $request->login,
            'password' => $request->password,
            'role_id' => $request->role_id,
        ]);

        $image = $request->file('photo_file');

        if ($image) {
            $imageName = Str::uuid() . '.' . $image->extension();
            $image->move(public_path('/photos'), $imageName);
            $user->update([
                'photo_file' => $imageName,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'status' => 'created',
            ]
        ], 201);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function storeShift(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'start' => 'required|date_format:Y-m-d H:i',
            'end' => 'required|date_format:Y-m-d H:i',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $shift = WorkShift::query()->create([
            'start' => $request->start,
            'end' => $request->end,
        ]);

        return response()->json([
            'id' => $shift->id,
            'start' => $shift->start,
            'end' => $shift->end,
        ], 201);
    }

    /**
     * @param WorkShift $workShift
     * @return JsonResponse
     */
    public function openShift(WorkShift $workShift): JsonResponse
    {
        if (WorkShift::query()->where('active', true)->exists()) {
            return $this->baseError('Forbidden. There are open shifts!', 403);
        }

        $workShift->update([
            'active' => true,
        ]);

        return response()->json([
            'data' => [
                new ShiftResource($workShift),
            ]
        ]);
    }

    /**
     * @param WorkShift $workShift
     * @return JsonResponse
     */
    public function closeShift(WorkShift $workShift): JsonResponse
    {
        if (boolval($workShift->active) === false) {
            return $this->baseError('Forbidden. The shift is already closed!', 403);
        }

        $workShift->update([
            'active' => false,
        ]);

        return response()->json([
            'data' => [
                new ShiftResource($workShift),
            ]
        ]);
    }

    /**
     * @param Request $request
     * @param WorkShift $workShift
     * @return JsonResponse
     */
    public function addUserToShift(Request $request, WorkShift $workShift): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $user = User::query()->find($request->user_id);

        if ($workShift->users()->where('user_id', $user->id)->exists()) {
            return $this->baseError('Forbidden. The worker is already on shift!', 403);
        }

        $workShift->users()->attach($user);

        return response()->json([
            'data' => [
                'id_user' => $user->id,
                'status' => 'added',
            ]
        ]);
    }

    /**
     * @param WorkShift $workShift
     * @return JsonResponse
     */
    public function getShiftOrders(WorkShift $workShift): JsonResponse
    {
        $orders = $workShift->orders;

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
}
