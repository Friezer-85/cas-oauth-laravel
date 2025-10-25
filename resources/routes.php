<?php
use Illuminate\Support\Facades\Route;
use Friezer\CasOauth\Controllers\CasController;
use Friezer\CasOauth\Controllers\OauthController;

Route::prefix('cas')
  ->name('cas-oauth.cas.')
  ->middleware('web')
  ->group(function () {
    Route::get('/login', [CasController::class, 'login'])->name('login');
    Route::get('/serviceValidate', [CasController::class, 'serviceValidate'])->name('serviceValidate');
    Route::post('/samlValidate', [CasController::class, 'samlValidate'])
      ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
      ->name('samlValidate');
  });

Route::prefix('oauth')
  ->name('cas-oauth.oauth.')
  ->middleware('web')
  ->group(function () {
    Route::get('/login', [OauthController::class, 'login'])->name('login');
    Route::get('/callback', [OauthController::class, 'callback'])->name('callback');
  });