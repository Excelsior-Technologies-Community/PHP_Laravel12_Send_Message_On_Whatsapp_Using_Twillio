<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| WhatsApp
|--------------------------------------------------------------------------
*/

Route::get('/whatsapp', [
    WhatsAppController::class,
    'index'
])->name('whatsapp');

Route::post('/whatsapp', [
    WhatsAppController::class,
    'store'
])->name('whatsapp.post');

/*
|--------------------------------------------------------------------------
| Message History
|--------------------------------------------------------------------------
*/

Route::get('/messages', [
    WhatsAppController::class,
    'history'
])->name('messages.history');

/*
|--------------------------------------------------------------------------
| Retry Failed Message
|--------------------------------------------------------------------------
*/

Route::post('/messages/{message}/retry', [
    WhatsAppController::class,
    'retry'
])->name('messages.retry');

/*
|--------------------------------------------------------------------------
| Templates
|--------------------------------------------------------------------------
*/

Route::get('/templates', [
    WhatsAppController::class,
    'templatesIndex'
])->name('templates.index');

Route::get('/templates/create', [
    WhatsAppController::class,
    'templatesCreate'
])->name('templates.create');

Route::post('/templates', [
    WhatsAppController::class,
    'templatesStore'
])->name('templates.store');

Route::get('/templates/{template}/edit', [
    WhatsAppController::class,
    'templatesEdit'
])->name('templates.edit');

Route::put('/templates/{template}', [
    WhatsAppController::class,
    'templatesUpdate'
])->name('templates.update');

Route::delete('/templates/{template}', [
    WhatsAppController::class,
    'templatesDestroy'
])->name('templates.destroy');

/*
|--------------------------------------------------------------------------
| WhatsApp Webhook
|--------------------------------------------------------------------------
*/

Route::post('/webhook/whatsapp', [
    WhatsAppController::class,
    'webhook'
])->name('webhook.whatsapp');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [
    WhatsAppController::class,
    'dashboard'
])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [
        ProfileController::class,
        'edit'
    ])->name('profile.edit');

    Route::put('/profile', [
        ProfileController::class,
        'update'
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy'
    ])->name('profile.destroy');
});

require __DIR__ . '/auth.php';