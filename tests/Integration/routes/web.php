<?php

use Illuminate\Support\Facades\Route;
use Tests\Integration\App\Http\Controllers\UserController;

Route::get('/user/{user}', (new UserController())->show(...))
    ->name('user.show')
    ->where('user', '\d+');

Route::post('user', (new UserController())->store(...))
    ->name('user.store');

Route::get('request/{num}', function ($num) {
    return "Request {$num}";
});

Route::get('server-error', function () {
    abort(500, 'Server error');
});

Route::get('redirect-1', function () {
    return redirect('redirect-2');
});

Route::get('redirect-2', function () {
    return redirect('redirect-3');
});

Route::get('redirect-3', function () {
    return 'Final Response';
});

Route::pattern('irrelevant-parameter', '\w+');

Route::pattern('global', '[a-z]+');

Route::get('parameters/{global}/{local}/{optional?}', function () {
    return 'Test route parameters';
});

Route::post('upload-photo', (new UserController())->uploadPhoto(...));
