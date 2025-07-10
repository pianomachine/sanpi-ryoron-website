<?php

use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;

Route::get('login', function (AuthKitLoginRequest $request) {
    $intendedUrl = $request->query('intended');
    if ($intendedUrl) {
        session(['url.intended' => $intendedUrl]);
    }
    return $request->redirect();
})->middleware(['guest'])->name('login');

Route::get('authenticate', function (AuthKitAuthenticationRequest $request) {
    $intendedUrl = session('url.intended', route('home'));
    session()->forget('url.intended');
    return tap(redirect($intendedUrl), fn () => $request->authenticate());
})->middleware(['guest']);

Route::post('logout', function (AuthKitLogoutRequest $request) {
    $intendedUrl = $request->input('intended', route('home'));
    $request->logout();
    return redirect($intendedUrl);
})->middleware(['auth'])->name('logout');
