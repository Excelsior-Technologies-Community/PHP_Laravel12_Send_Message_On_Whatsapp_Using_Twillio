# PHP_Laravel12_Send_Message_On_Whatsapp_Using_Twillio



---

## Step 1: Install Laravel 12

This step is optional.  
If you have not created a Laravel application yet, run the command below:

```
composer create-project laravel/laravel example-app
```

Explanation:  
This command creates a fresh Laravel 12 project with default configuration and folder structure.

---

## Step 2: Set Up a Twilio Account

1. Create an account on **Twilio**.
2. Add a WhatsApp-enabled phone number.
3. Copy the following credentials from Twilio Dashboard:
   - Account SID
   - Auth Token
   - WhatsApp Number
  
<img width="1891" height="880" alt="image" src="https://github.com/user-attachments/assets/a49355fb-467d-45fa-94ec-6385cb891051" />


Add them to your `.env` file:

```
TWILIO_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_WHATSAPP_NUMBER=your_twilio_whatsapp_number
```

Explanation:  
These credentials authenticate your Laravel application with Twilio’s WhatsApp service.

---

## Step 3: Install Twilio SDK

Install the Twilio SDK package using Composer:

```
composer require twilio/sdk
```

Explanation:  
This package allows Laravel to communicate with Twilio’s REST API.

---

## Step 4: Create Routes

File: `routes/web.php`

```php
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
 */
Route::get('/whatsapp', [WhatsAppController::class, 'index']);

/**
 * POST /whatsapp - Handles incoming WhatsApp webhook data
 * Named route 'whatsapp.post' allows easy URL generation
 */
Route::post('/whatsapp', [WhatsAppController::class, 'store'])->name('whatsapp.post');
```

Explanation:  
These routes display the WhatsApp form and handle message submission.

---

## Step 5: Create Controller

File: `app/Http/Controllers/WhatsAppController.php`

```php
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
     */
    public function index()
    {
        return view('whatsapp');
    }

    /**
     * Send WhatsApp message via Twilio API
     */
    public function store(Request $request)
    {
        /**
         * Validate incoming request data
         */
        $request->validate([
            'phone' => 'required',
            'message' => 'required',
        ]);

        try {
            /**
             * Get Twilio credentials from config/services.php
             */
            $sid   = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from  = 'whatsapp:' . config('services.twilio.whatsapp_from');

            /**
             * Format recipient phone number for India
             */
            $to = 'whatsapp:+91' . $request->phone; // ONLY ONCE +91

            /**
             * Initialize Twilio client
             */
            $client = new Client($sid, $token);

            /**
             * Send WhatsApp message
             */
            $client->messages->create($to, [
                'from' => $from,
                'body' => $request->message,
            ]);

            return back()->with('success', 'WhatsApp message sent successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

Explanation:  
Controller validates input, formats WhatsApp numbers, and sends messages using Twilio API.

---

## Step 6: Create Blade File

File: `resources/views/whatsapp.blade.php`

```html

```
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Page title displayed in browser tab -->
    <title>Send WhatsApp Message</title>
    
    <!-- Bootstrap 5 CSS CDN for responsive styling and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Main container with top margin for better spacing -->
    <div class="container mt-5">
        <!-- Bootstrap card component for clean form presentation -->
        <div class="card">
            <!-- Card header with title -->
            <div class="card-header">
                <h4>Send WhatsApp Message using Twilio</h4>
            </div>
            
            <!-- Card body contains the form and flash messages -->
            <div class="card-body">
                <!-- Success message display (from controller flash session) -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <!-- Optional: Close button for alerts -->
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Error message display (from controller flash session) -->
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Main WhatsApp form - POST to named route 'whatsapp.post' -->
                <form method="POST" action="{{ route('whatsapp.post') }}">
                    <!-- Laravel CSRF protection token (security requirement) -->
                    @csrf

                    <!-- Phone Number Input Field -->
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <!-- Indian mobile number format (10 digits only, no +91) -->
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control @error('phone') is-invalid @enderror" 
                            placeholder="8511270630"
                            value="{{ old('phone') }}"
                            maxlength="10"
                        >
                        <!-- Laravel validation error display -->
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Message Textarea Field -->
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea 
                            name="message" 
                            class="form-control @error('message') is-invalid @enderror" 
                            rows="4"
                            placeholder="Enter your WhatsApp message here..."
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit button styled with Bootstrap success color -->
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fab fa-whatsapp"></i> Send WhatsApp Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (includes Popper for tooltips/dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


---

## Step 7: Configure Twilio in services.php

File: `config/services.php`

```php
'twilio' => [
    'sid' => env('TWILIO_SID'),
    'token' => env('TWILIO_AUTH_TOKEN'),
    'whatsapp_from' => env('TWILIO_WHATSAPP_NUMBER'),
],
```

Explanation:  
Central configuration file for Twilio credentials used throughout the application.

---

## Step 8: Run Laravel Application

```
php artisan serve
```

Open browser:

```
http://localhost:8000/whatsapp
```
<img width="1613" height="645" alt="image" src="https://github.com/user-attachments/assets/70226c3d-a220-49a7-a92e-009667ed08ec" />
<img width="1025" height="882" alt="image" src="https://github.com/user-attachments/assets/7877fc66-96d0-4052-9bb5-9d4346a87e1a" />



