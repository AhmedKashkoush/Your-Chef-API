<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => ['apikey', 'locale'], 'prefix' => 'v1'], function () {
    Route::group(['prefix' => 'users', 'namespace' => '\App\Http\Controllers\Users'], function () {
        Route::post('register', 'UserController@register');
        Route::post('login', 'UserController@login');
        Route::post('send-code', 'UserController@sendOtp');
        Route::post('verify', 'UserController@verifyOtp');
        Route::post('send-code-mail', 'UserController@sendMailOtp');
        Route::post('verify-email', 'UserController@verifyMailOtp');
        Route::post('reset-password', 'UserController@resetPassword');
        Route::group(['prefix' => 'profile-photo'], function () {
            Route::post('upload', 'UserController@uploadPhoto');
            Route::post('delete', 'UserController@deletePhoto');
            Route::post('all', 'UserController@allPhotos');
        });
        Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'user'], function () {
            Route::middleware('user.status')->group(function () {
                Route::get('/', 'UserController@user');
                Route::get('update-user-status', 'UserController@updateUserStatus');
                Route::post('edit-user', 'UserController@editUser');
                Route::get('logout', 'UserController@logout');
                Route::get('delete-user', 'UserController@deleteUser');
            });
        });
    });
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('test-token', function () {
            return 'Has Access';
        });
        Route::group(['prefix' => 'categories'], function () {
            Route::group(['namespace' => '\App\Http\Controllers\Categories'], function () {
                Route::post('/', 'CategoryController@all');
                Route::post('add', 'CategoryController@addCategory');
            });
        });
        Route::group(['prefix' => 'foods'], function () {
            Route::group(['namespace' => '\App\Http\Controllers\Foods'], function () {
                Route::post('/', 'FoodController@all');
                Route::post('category', 'FoodController@withCategory');
                Route::post('restaurant', 'FoodController@fromRestaurant');
                Route::post('add', 'FoodController@addFood');
            });
        });
        Route::group(['prefix' => 'restaurants'], function () {
            Route::group(['namespace' => '\App\Http\Controllers\Restaurants'], function () {
                Route::post('/', 'RestaurantController@all');
                Route::post('category', 'RestaurantController@withCategory');
                Route::post('add', 'RestaurantController@addRestaurant');
            });
        });
    });
});
