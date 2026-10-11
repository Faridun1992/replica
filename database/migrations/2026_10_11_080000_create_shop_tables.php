<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('shop_customers', function(Blueprint $t) { $t->id(); $t->string('name'); $t->string('email')->unique(); $t->string('city'); $t->timestamps(); });
  Schema::create('shop_products', function(Blueprint $t) { $t->id(); $t->string('name'); $t->string('category')->index(); $t->unsignedInteger('price'); $t->unsignedInteger('stock'); $t->timestamps(); });
  Schema::create('shop_orders', function(Blueprint $t) { $t->id(); $t->foreignId('customer_id')->constrained('shop_customers'); $t->string('status')->default('new')->index(); $t->unsignedInteger('total'); $t->timestamps(); });
  Schema::create('shop_order_items', function(Blueprint $t) { $t->id(); $t->foreignId('order_id')->constrained('shop_orders')->cascadeOnDelete(); $t->foreignId('product_id')->constrained('shop_products'); $t->unsignedInteger('quantity'); $t->unsignedInteger('unit_price'); });
 }
 public function down(): void { foreach(['shop_order_items','shop_orders','shop_products','shop_customers'] as $table) Schema::dropIfExists($table); }
};
