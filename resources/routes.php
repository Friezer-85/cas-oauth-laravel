<?php

use Illuminate\Support\Facades\Route;

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

Route::group([
  'prefix' => '/cas/',
  'as' => 'cas.'
], function () {
  Route::get('/login', '\Micorksen\CasOauth\Controllers\CasController@login')
    ->name('login');

  Route::get('/serviceValidate', '\Micorksen\CasOauth\Controllers\CasController@serviceValidate')
    ->name('serviceValidate');
});

Route::group([
  'prefix' => '/oauth/',
  'as' => 'oauth.'
], function () {
  Route::get('/login', '\Micorksen\CasOauth\Controllers\OauthController@login')
    ->name('login');

  Route::get('/callback', '\Micorksen\CasOauth\Controllers\OauthController@callback')
    ->name('callback');
});