<?php

namespace Database\Seeders;

use App\Models\ShopCustomer;
use App\Models\ShopProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            if (ShopProduct::query()->useWritePdo()->exists()) {
                return;
            }
            ShopCustomer::factory()->count(60)->create();
            $catalog = ['Кофе Эфиопия' => ['Кофе', 6500], 'Кофе Бразилия' => ['Кофе', 4900], 'Матча' => ['Чай', 8200], 'Чай с жасмином' => ['Чай', 3500], 'Чайник Hario' => ['Аксессуары', 24000], 'Керамическая чашка' => ['Посуда', 4500], 'Термос' => ['Посуда', 18000], 'Кофемолка' => ['Аксессуары', 32000], 'Фильтры V60' => ['Аксессуары', 2500], 'Печенье миндальное' => ['Сладости', 2800], 'Шоколад 70%' => ['Сладости', 3900], 'Кофе Колумбия' => ['Кофе', 7200]];
            foreach ($catalog as $name => [$category,$price]) {
                ShopProduct::create(['name' => $name, 'category' => $category, 'price' => $price, 'stock' => fake()->numberBetween(100, 300), 'created_at' => now(), 'updated_at' => now()]);
            }
            $customers = ShopCustomer::query()->useWritePdo()->pluck('id');
            $products = ShopProduct::query()->useWritePdo()->get();
            for ($i = 0; $i < 180; $i++) {
                $date = now()->subDays(fake()->numberBetween(0, 30));
                $order = ShopCustomer::query()->useWritePdo()->findOrFail($customers->random())->orders()->create(['status' => fake()->randomElement(['new', 'paid', 'shipped', 'completed']), 'total' => 0, 'created_at' => $date, 'updated_at' => $date]);
                $total = 0;
                foreach ($products->random(fake()->numberBetween(1, 4)) as $product) {
                    $qty = fake()->numberBetween(1, 3);
                    $total += $qty * $product->price;
                    $order->items()->create(['product_id' => $product->id, 'quantity' => $qty, 'unit_price' => $product->price]);
                }
                $order->update(['total' => $total]);
            }
        });
    }
}
