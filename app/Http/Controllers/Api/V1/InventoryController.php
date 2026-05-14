<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Resources\InventoryResource;
use App\Enums\InventoryTransactionType;
use App\Models\Ingredient;
use App\Repositories\InventoryRepository;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private InventoryRepository $inventoryRepository
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        $ingredients = $this->inventoryRepository->getAll();

        return response()->json(InventoryResource::collection($ingredients));
    }

    public function lowStock(): JsonResponse
    {
        $ingredients = $this->inventoryRepository->getLowStock();

        return response()->json(InventoryResource::collection($ingredients));
    }

    public function adjust(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $ingredient = Ingredient::findOrFail($data['ingredient_id']);

        $type = InventoryTransactionType::from($data['type']);

        $transaction = $this->inventoryService->recordTransaction(
            $ingredient,
            (float) $data['quantity'],
            $type,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
        );

        return response()->json(['transaction' => $transaction], 201);
    }

    public function transactions(Ingredient $ingredient): JsonResponse
    {
        $transactions = $this->inventoryRepository->getTransactions($ingredient->id);

        return response()->json($transactions);
    }
}
