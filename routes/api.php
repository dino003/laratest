<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\CartsController;
use App\Http\Controllers\UsersController;

Route::group(['prefix' => 'v1'], function () {
    Route::get('documentation.yml', function () {
        return response()->view(
            'documentation',
            [],
            200,
            ['Content-Type' => 'text/yaml; charset=UTF-8']
        );
    });

    Route::group(['prefix' => 'cart'], function () {
        Route::post('discount', [CartsController::class, 'calculateDiscount']);
    });

    Route::group(['prefix' => 'user'], function () {
        Route::get('{email}', [UsersController::class, 'information']);
    });
});
