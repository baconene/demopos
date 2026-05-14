<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private OrderRepository $orderRepository
    ) {}

    public function index(): JsonResponse
    {
        $this->checkPermission('view orders');

        $orders = Order::with(['items.product', 'user', 'queueNumber'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(OrderResource::collection($orders));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->checkPermission('create orders');

        $order = $this->orderService->createOrder($request->validated());

        return response()->json(new OrderResource($order), 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->checkPermission('view orders');

        $order = $this->orderRepository->getWithItems($order->id);

        return response()->json(new OrderResource($order));
    }

    public function update(Order $order): JsonResponse
    {
        $this->checkPermission('update orders');

        return response()->json(new OrderResource($order));
    }

    public function destroy(Order $order): Response
    {
        $this->checkPermission('delete orders');

        return response()->noContent();
    }

    public function updateStatus(Order $order, UpdateOrderStatusRequest $request): JsonResponse
    {
        $this->checkPermission('update orders');

        $order = $this->orderService->updateOrderStatus($order, $request->enum('status', OrderStatus::class));

        return response()->json(new OrderResource($order));
    }

    public function cancel(Order $order): JsonResponse
    {
        $this->checkPermission('update orders');

        $order = $this->orderService->cancelOrder($order, request()->input('reason'));

        return response()->json(new OrderResource($order));
    }

    public function activeOrders(): JsonResponse
    {
        $this->checkPermission('view orders');

        $orders = $this->orderRepository->getActiveOrders();

        return response()->json(OrderResource::collection($orders));
    }

    public function queueOrders(): JsonResponse
    {
        $this->checkPermission('view orders');

        $orders = $this->orderRepository->getQueueOrders();

        return response()->json(OrderResource::collection($orders));
    }

    private function checkPermission(string $permission): void
    {
        if (! auth()->user()?->hasAnyRole('admin') && ! auth()->user()?->hasPermissionTo($permission)) {
            abort(403, 'Unauthorized');
        }
    }
}
