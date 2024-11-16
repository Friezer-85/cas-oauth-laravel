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
  'as' => 'cas-oauth.',
  'middleware' => 'web',
  'excluded_middleware' => [\App\Http\Middleware\VerifyCsrfToken::class],
], function () {
  Route::group([
    'prefix' => '/cas/',
    'as' => 'cas.'
  ], function () {
    Route::get('/login', '\Friezer-85\CasOauth\Controllers\CasController@login')
      ->name('login');

    Route::get('/serviceValidate', '\Friezer-85\CasOauth\Controllers\CasController@serviceValidate')
      ->name('serviceValidate');

    Route::post('/samlValidate', '\Friezer-85\CasOauth\Controllers\CasController@samlValidate')
      ->name('samlValidate');
  });

  Route::group([
      'prefix' => '/oauth/',
      'as' => 'oauth.'
  ], function () {
    Route::get('/login', '\Friezer-85\CasOauth\Controllers\OauthController@login')
      ->name('login');

    Route::get('/callback', '\Friezer-85\CasOauth\Controllers\OauthController@callback')
      ->name('callback');
  });
});
