<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PruebasController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ApiAuthMiddleware;
use Illuminate\Support\Facades\Route;



Route::post('/api/login', [UserController::class, 'login']);
Route::post('/api/register', [UserController::class, 'register']);
//Route::post('/api/update', [UserController::class, 'update']);
Route::put('api/user/update', [UserController::class, 'update'])->middleware(ApiAuthMiddleware::class);
Route::post('api/user/upload', [UserController::class, 'upload'])->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/avatar/{filename}', [UserController::class, 'getImage'])->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/detail/{id}', [UserController::class, 'detail']);

Route::resource('api/category', CategoryController::class);
Route::resource('api/post', PostController::class);
Route::post('api/post/upload', [PostController::class, 'upload']);
Route::get('/api/post/image/{filename}', [PostController::class, 'getImage']);
Route::get('/api/post/category/{id}', [PostController::class, 'getPostsByCategory']);
Route::get('/api/post/user/{id}', [PostController::class, 'getPostsByUser']);