<?php

namespace App\Http\Controllers;

use App\Models\ShopCustomer;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReplicaShopController extends Controller
{
    private function orders(bool $writer): Collection
    {
        $query = ShopOrder::query()->with(['customer' => function ($query) use ($writer) {
            if ($writer) {
                $query->useWritePdo();
            }
        }])->latest('id')->limit(12);

        if ($writer) {
            $query->useWritePdo();
        }

        return $query->get()->map(fn (ShopOrder $order) => [
            'id' => $order->id,
            'customer_id' => $order->customer_id,
            'customer' => $order->customer->name,
            'status' => $order->status,
            'total' => $order->total,
            'created_at' => $order->created_at->toIso8601String(),
            'updated_at' => $order->updated_at->toIso8601String(),
        ]);
    }

    public function index(): View
    {
        return view('replica-shop', [
            'products' => ShopProduct::query()->orderBy('category')->get(),
            'customers' => ShopCustomer::query()->orderBy('name')->get(),
        ]);
    }

    public function snapshot(): JsonResponse
    {
        // Separate read-only request prevents sticky routing from hiding replication.
        return response()->json([
            'reader' => $this->orders(false),
            'writer' => $this->orders(true),
            'reader_count' => ShopOrder::query()->count(),
            'writer_count' => ShopOrder::query()->useWritePdo()->count(),
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $order = DB::transaction(function () use ($data) {
            $customer = ShopCustomer::query()->useWritePdo()->find($data['customer_id']);
            abort_unless($customer, 422, 'Покупатель не найден');

            $product = ShopProduct::query()->useWritePdo()->lockForUpdate()->find($data['product_id']);
            abort_unless($product && $product->stock >= $data['quantity'], 422, 'Недостаточно товара на складе');

            $order = $customer->orders()->create([
                'status' => 'new',
                'total' => $product->price * $data['quantity'],
            ]);
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'unit_price' => $product->price,
            ]);
            $product->decrement('stock', $data['quantity']);

            return $order;
        });

        return response()->json(['id' => $order->id], 201);
    }
}
