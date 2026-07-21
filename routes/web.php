<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InformationPageController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/mitra', [PartnerController::class, 'index'])->name('partners.index');
Route::get('/mitra/{partner:slug}', [PartnerController::class, 'show'])->name('partners.show');
Route::get('/kategori/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/tentang', [InformationPageController::class, 'about'])->name('about');
Route::get('/kontak', [InformationPageController::class, 'contact'])->name('contact');
Route::get('/kebijakan-privasi', [InformationPageController::class, 'privacy'])->name('privacy');
