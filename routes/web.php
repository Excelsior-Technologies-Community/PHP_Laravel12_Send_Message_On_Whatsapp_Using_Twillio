<?php

/**
 * Import necessary Laravel facades and controllers
 * Illuminate\Support\Facades\Route - Provides access to Laravel's routing system
 * App\Http\Controllers\WhatsAppController - Imports the WhatsAppController class for handling WhatsApp functionality
 */
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

/**
 * Default root route - Serves the welcome view when users visit the homepage
 * This is typically the landing page of your Laravel application
 */
Route::get('/', function () {
    return view('welcome');
});

/**
 * WhatsApp Webhook Routes
 * 
 * GET /whatsapp - Displays the WhatsApp integration page or form
 * This route calls the 'index' method in WhatsAppController
 * Usually used for showing a dashboard, form, or webhook status page
 */
Route::get('/whatsapp', [WhatsAppController::class, 'index']);

/**
 * POST /whatsapp - Handles incoming WhatsApp webhook data
 * This route processes POST requests from WhatsApp servers (webhooks)
 * 'store' method in controller will handle the incoming message data
 * Named route 'whatsapp.post' allows easy URL generation using route('whatsapp.post')
 */
Route::post('/whatsapp', [WhatsAppController::class, 'store'])->name('whatsapp.post');
