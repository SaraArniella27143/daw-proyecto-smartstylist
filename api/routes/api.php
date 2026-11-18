<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\StyleController;
use App\Http\Controllers\Api\GarmentController;
use App\Http\Controllers\Api\OutfitController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\OutfitLogController;

Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->whereNumber('category');
    Route::get('/styles', [StyleController::class, 'index']);
    Route::get('/styles/{style}', [StyleController::class, 'show'])->whereNumber('style');
    Route::get('/colors', [ColorController::class, 'index']);
    Route::get('/colors/{color}', [ColorController::class, 'show'])->whereNumber('color');

    Route::get('/garments', [GarmentController::class, 'index']);
    Route::post('/garments', [GarmentController::class, 'store']);
    Route::get('/garments/{garment}/image', [GarmentController::class, 'image'])->whereNumber('garment')->name('garments.image');
    Route::get('/garments/{garment}', [GarmentController::class, 'show'])->whereNumber('garment');
    Route::post('/garments/{garment}', [GarmentController::class, 'update'])->whereNumber('garment');
    Route::put('/garments/{garment}', [GarmentController::class, 'update'])->whereNumber('garment');
    Route::patch('/garments/{garment}', [GarmentController::class, 'update'])->whereNumber('garment');
    Route::delete('/garments/{garment}', [GarmentController::class, 'destroy'])->whereNumber('garment');

    Route::get('/outfits', [OutfitController::class, 'index']);
    Route::post('/outfits', [OutfitController::class, 'store']);
    Route::get('/outfits/{outfit}', [OutfitController::class, 'show'])->whereNumber('outfit');
    Route::put('/outfits/{outfit}', [OutfitController::class, 'update'])->whereNumber('outfit');
    Route::patch('/outfits/{outfit}', [OutfitController::class, 'update'])->whereNumber('outfit');
    Route::delete('/outfits/{outfit}', [OutfitController::class, 'destroy'])->whereNumber('outfit');
    Route::put('/outfits/{outfit}/garments', [OutfitController::class, 'replaceGarments'])->whereNumber('outfit');

    Route::get('/outfits/suggestions', [OutfitController::class, 'suggestions']);

    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show'])->whereNumber('schedule');
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->whereNumber('schedule');
    Route::patch('/schedules/{schedule}', [ScheduleController::class, 'update'])->whereNumber('schedule');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->whereNumber('schedule');

    Route::post('/outfit-logs', [OutfitLogController::class, 'store']);
    Route::get('/outfit-logs/statistics', [OutfitLogController::class, 'statistics']);


    Route::middleware('role:admin')->group(function (): void {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->whereNumber('user');

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->whereNumber('category');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('category');

        Route::post('/styles', [StyleController::class, 'store']);
        Route::put('/styles/{style}', [StyleController::class, 'update'])->whereNumber('style');
        Route::patch('/styles/{style}', [StyleController::class, 'update'])->whereNumber('style');
        Route::delete('/styles/{style}', [StyleController::class, 'destroy'])->whereNumber('style');

        Route::post('/colors', [ColorController::class, 'store']);
        Route::put('/colors/{color}', [ColorController::class, 'update'])->whereNumber('color');
        Route::patch('/colors/{color}', [ColorController::class, 'update'])->whereNumber('color');
        Route::delete('/colors/{color}', [ColorController::class, 'destroy'])->whereNumber('color');

    });
});