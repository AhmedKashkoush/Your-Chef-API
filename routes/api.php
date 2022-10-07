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

Route::group(['middleware' => 'apikey','prefix' => 'v1'],function(){
    Route::group(['prefix' => 'users','namespace' => '\App\Http\Controllers\Users'],function(){
        Route::post('register','UserController@register');
        Route::post('login','UserController@login');
        Route::post('send-code','UserController@sendOtp');
        Route::post('verify','UserController@verifyOtp');
        Route::post('send-code-mail','UserController@sendMailOtp');
        Route::post('verify-email','UserController@verifyMailOtp');
        Route::group(['prefix' => 'profile-photo'],function () {
            Route::post('upload','UserController@uploadPhoto');
            Route::post('delete','UserController@deletePhoto');
            Route::post('all','UserController@allPhotos');
        });
    });
    Route::group(['middleware' => 'auth:sanctum'],function(){
        Route::post('test-token',function(){
            return 'Has Access';
        });
    });
});
