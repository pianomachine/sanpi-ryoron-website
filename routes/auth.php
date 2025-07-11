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
    try {
        $intendedUrl = session('url.intended', route('home'));
        session()->forget('url.intended');
        return tap(redirect($intendedUrl), fn () => $request->authenticate());
    } catch (\Exception $e) {
        \Log::error('Authentication error', [
            'error' => $e->getMessage(),
            'query_params' => $request->query(),
        ]);
        
        return redirect('/welcome')->with('error', 'ログインに失敗しました。もう一度お試しください。');
    }
})->middleware(['guest']);

Route::post('logout', function (AuthKitLogoutRequest $request) {
    $intendedUrl = $request->input('intended', route('home'));
    $request->logout();
    return redirect($intendedUrl);
})->middleware(['auth'])->name('logout');
