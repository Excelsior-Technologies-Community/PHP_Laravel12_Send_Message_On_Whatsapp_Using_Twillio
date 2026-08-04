<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp');
Route::post('/whatsapp', [WhatsAppController::class, 'store'])->name('whatsapp.post');

Route::get('/messages', [WhatsAppController::class, 'history'])->name('messages.history');

Route::get('/templates', [WhatsAppController::class, 'templatesIndex'])->name('templates.index');
Route::get('/templates/create', [WhatsAppController::class, 'templatesCreate'])->name('templates.create');
Route::post('/templates', [WhatsAppController::class, 'templatesStore'])->name('templates.store');
Route::get('/templates/{template}/edit', [WhatsAppController::class, 'templatesEdit'])->name('templates.edit');
Route::put('/templates/{template}', [WhatsAppController::class, 'templatesUpdate'])->name('templates.update');
Route::delete('/templates/{template}', [WhatsAppController::class, 'templatesDestroy'])->name('templates.destroy');

Route::post('/webhook/whatsapp', [WhatsAppController::class, 'webhook'])->name('webhook.whatsapp');

Route::get('/dashboard', [WhatsAppController::class, 'dashboard'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';