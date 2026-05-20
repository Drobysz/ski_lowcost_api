<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AdminResource::collection(Admin::query()->latest('id')->paginate());
    }

    public function store(StoreAdminRequest $request): AdminResource
    {
        return new AdminResource(Admin::create($request->validated()));
    }

    public function show(Admin $admin): AdminResource
    {
        return new AdminResource($admin);
    }

    public function update(UpdateAdminRequest $request, Admin $admin): AdminResource
    {
        $admin->update($request->validated());

        return new AdminResource($admin->refresh());
    }

    public function destroy(Admin $admin): JsonResponse
    {
        $admin->delete();

        return response()->json(null, 204);
    }
}
