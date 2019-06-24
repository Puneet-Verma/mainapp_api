<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

date_default_timezone_set('Asia/Kolkata');
Route::post('api/v1.0/profile','ProfileController@getProfile');
Route::post('api/v1.0/register','OtpController@generateOtp');
Route::post('api/v1.0/verifyOtp', 'OtpController@verifyOtp');
Route::post('api/v1.0/logout', 'OtpController@logout');
Route::post('api/v1.1/getreading/hourly',"ConsumptionController@getHourlyReading");
Route::post('api/v1.1/getreading/monthly',"ConsumptionController@getMonthlyReading");


//Route::post('verifyOtp', 'AuthController@verifyOtp');    // otp +(91)981139142
//Route::post('register', 'AuthController@authenticate'); //  (91)9811391432
