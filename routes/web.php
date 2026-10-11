<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReplicaShopController;

Route::get('/', [ReplicaShopController::class, 'index']);
Route::get('/replica/snapshot', [ReplicaShopController::class, 'snapshot']);
Route::post('/replica/orders', [ReplicaShopController::class, 'store']);
Auth::routes();
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
