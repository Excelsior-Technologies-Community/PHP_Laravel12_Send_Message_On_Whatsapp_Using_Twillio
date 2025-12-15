<?php

namespace App\Http\Controllers;

/**
 * Import necessary Laravel and Twilio dependencies
 * Illuminate\Http\Request - Handles incoming HTTP requests and validation
 * Twilio\Rest\Client - Twilio SDK client for sending WhatsApp messages
 */
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class WhatsAppController extends Controller
{
    /**
     * Display the WhatsApp messaging interface
     * 
     * This method renders the 'whatsapp' Blade view
     * Typically contains a form with phone number and message fields
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('whatsapp');
    }

    /**
     * Send WhatsApp message via Twilio API
     * 
     * Handles POST request from the WhatsApp form
     * Validates input, sends message using Twilio, and returns success/error response
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        /**
         * Validate incoming request data
         * 'phone' - Required Indian phone number (without +91 prefix)
         * 'message' - Required message content to send
         */
        $request->validate([
            'phone' => 'required',
            'message' => 'required',
        ]);

        try {
            /**
             * Get Twilio credentials from config/services.php
             * sid - Twilio Account SID (unique account identifier)
             * token - Twilio Auth Token (authentication key)
             * whatsapp_from - Your Twilio WhatsApp sender number/ID
             */
            $sid    = config('services.twilio.sid');
            $token  = config('services.twilio.token');
            $from   = 'whatsapp:' . config('services.twilio.whatsapp_from');

            /**
             * Format recipient phone number for India
             * Converts input phone (e.g., 9876543210) to whatsapp:+919876543210
             * IMPORTANT: +91 is added only once here - user inputs only digits
             */
            $to = 'whatsapp:+91' . $request->phone; // 👈 ONLY ONCE +91

            /**
             * Initialize Twilio client with credentials
             * This connects to Twilio's API for sending messages
             */
            $client = new Client($sid, $token);

            /**
             * Create and send WhatsApp message
             * 'from' - Your Twilio WhatsApp number (whatsapp:+14155238886 format)
             * 'body' - Message text content from form
             * 'to' - Recipient's WhatsApp number (whatsapp:+919876543210 format)
             */
            $client->messages->create($to, [
                'from' => $from,
                'body' => $request->message,
            ]);

            /**
             * Success response - Redirect back with success message
             * Flash message will be displayed in the whatsapp.blade.php view
             */
            return back()->with('success', 'WhatsApp message sent successfully!');
            
        } catch (\Exception $e) {
            /**
             * Error handling - Catches Twilio API errors or validation issues
             * Returns back to form with error message for display
             * Common errors: Invalid phone, insufficient credits, Twilio config issues
             */
            return back()->with('error', $e->getMessage());
        }
    }
}
