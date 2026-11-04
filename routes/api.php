<?php

use App\Http\Controllers\Web\Admin\UnitMaterialImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/units/{unit}/material/images/{image}', [UnitMaterialImageController::class, 'showForLearner'])
    ->middleware(['auth:sanctum', 'role:learner'])
    ->where('image', '[A-Za-z0-9.-]+')
    ->name('api.units.material-images.show');
