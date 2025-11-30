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

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ActionApiController;
use App\Http\Controllers\Api\UserApiController;


// group prefix api


Route::group(['prefix' => 'v1'], function () {

    Route::group(['prefix' => 'actions'], function () {

        Route::get('/', [ActionApiController::class, 'index'])->name('actions.index');
    });


    Route::group(['prefix' => 'users'], function () {

        Route::get('/', [UserApiController::class, 'index'])->name('users.index');
        Route::post('/', [UserApiController::class, 'store'])->name('users.store');

    });




    // Route::get('/users', 'UserController@index');
    // Route::get('/users/{id}', 'UserController@show');
});






// Route::post('/api/organizations', 'Api\OrganizationController@store');
// Route::post('/api/actions', [ActionApiController::class, 'index']) ->name('actions.index');
// Route::post('/api/organizations', [OrganizationController::class, 'index']) ->name('organizations.index');
