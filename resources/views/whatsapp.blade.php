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
