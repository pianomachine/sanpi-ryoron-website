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
        \Log::info('Authenticate route accessed', [
            'query_params' => $request->query(),
            'session_data' => session()->all(),
            'workos_config' => [
                'client_id' => config('services.workos.client_id') ? 'SET' : 'NOT SET',
                'redirect_url' => config('services.workos.redirect_url'),
            ]
        ]);
        
        $intendedUrl = session('url.intended', route('home'));
        session()->forget('url.intended');
        
        \Log::info('About to authenticate', ['intended_url' => $intendedUrl]);
        
        return tap(redirect($intendedUrl), fn () => $request->authenticate());
        
    } catch (\Exception $e) {
        \Log::error('Authentication error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'query_params' => $request->query(),
        ]);
        
        // エラー時は適切なエラーページにリダイレクト
        return redirect('/welcome')->with('error', 'ログインに失敗しました。もう一度お試しください。');
    }
})->middleware(['guest']);

Route::post('logout', function (AuthKitLogoutRequest $request) {
    $intendedUrl = $request->input('intended', route('home'));
    $request->logout();
    return redirect($intendedUrl);
})->middleware(['auth'])->name('logout');
