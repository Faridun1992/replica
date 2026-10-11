<?php
namespace Database\Factories;
use App\Models\ShopCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;
class ShopCustomerFactory extends Factory {
 protected $model = ShopCustomer::class;
 public function definition(): array { return ['name'=>fake()->name(), 'email'=>fake()->unique()->safeEmail(), 'city'=>fake()->randomElement(['Душанбе','Худжанд','Бохтар','Куляб','Пенджикент'])]; }
}
