<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send WhatsApp Message</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        @include('layouts.navigation')

        <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold mb-6">Send WhatsApp Message</h2>

                <form method="POST" action="{{ route('whatsapp.post') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phones">
                            Recipient Phone Numbers
                        </label>
                        <p class="text-gray-600 text-xs mb-2">Enter one number per line (Indian format, 10 digits, without +91)</p>
                        <textarea
                            name="phones[]"
                            id="phones"
                            class="w-full border rounded px-3 py-2 @error('phones') border-red-500 @enderror"
                            rows="5"
                            placeholder="8511270630&#10;9876543210"
                        >{{ old('phones') ? implode("\n", (array) old('phones')) : '' }}</textarea>
                        @error('phones')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            Your Name (Optional - for template personalization)
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="w-full border rounded px-3 py-2"
                            placeholder="Enter your name"
                            value="{{ old('name') }}"
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            Or Single Phone Number
                        </label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="w-full border rounded px-3 py-2 @error('phone') border-red-500 @enderror"
                            placeholder="8511270630"
                            maxlength="10"
                            value="{{ old('phone') }}"
                        >
                        @error('phone')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="template_id">
                            Select Template (Optional)
                        </label>
                        <select
                            name="template_id"
                            id="template_id"
                            class="w-full border rounded px-3 py-2 @error('template_id') border-red-500 @enderror"
                        >
                            <option value="">-- Select a Template --</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('template_id')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                            Message
                        </label>
                        <textarea
                            name="message"
                            id="message"
                            class="w-full border rounded px-3 py-2 @error('message') border-red-500 @enderror"
                            rows="4"
                            placeholder="Enter your WhatsApp message here..."
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="media">
                            Attach Media (Optional)
                        </label>
                        <input
                            type="file"
                            name="media"
                            id="media"
                            class="w-full border rounded px-3 py-2 @error('media') border-red-500 @enderror"
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                        >
                        <p class="text-gray-600 text-xs mt-1">Max 5MB. Supported: JPG, PNG, PDF, DOC, DOCX</p>
                        @error('media')
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                        Send WhatsApp Message
                    </button>
                </form>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('messages.history') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    View Message History
                </a>
                <a href="{{ route('templates.index') }}" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    Manage Templates
                </a>
                <a href="{{ route('dashboard') }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    Dashboard
                </a>
            </div>
        </main>
    </div>
</body>
</html>