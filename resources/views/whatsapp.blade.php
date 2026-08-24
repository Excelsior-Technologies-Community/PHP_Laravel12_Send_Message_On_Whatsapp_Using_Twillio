<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Send WhatsApp Message</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="min-h-screen">

    @include('layouts.navigation')

    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        {{-- Success --}}
        @if(session('success'))

            <div
                class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded"
                role="alert"
            >
                {{ session('success') }}
            </div>

        @endif

        {{-- Warning --}}
        @if(session('warning'))

            <div
                class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded"
                role="alert"
            >
                {{ session('warning') }}
            </div>

        @endif

        {{-- Error --}}
        @if(session('error'))

            <div
                class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"
                role="alert"
            >
                {{ session('error') }}
            </div>

        @endif


        <div class="bg-white shadow rounded-lg p-6 mb-6">

            <div class="mb-6">

                <h2 class="text-2xl font-bold text-gray-800">
                    Send WhatsApp Message
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Send WhatsApp messages with smart validation,
                    duplicate detection and retry support.
                </p>

            </div>


            {{-- Duplicate information --}}

            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">

                <h3 class="font-semibold text-blue-800">
                    Smart Number Protection
                </h3>

                <ul class="text-sm text-blue-700 mt-2 space-y-1">

                    <li>
                        ✓ Invalid Indian numbers are automatically skipped.
                    </li>

                    <li>
                        ✓ Duplicate numbers in the same list are removed.
                    </li>

                    <li>
                        ✓ Numbers successfully sent within the last
                        24 hours are skipped.
                    </li>

                    <li>
                        ✓ Failed messages can still be retried.
                    </li>

                </ul>

            </div>


            <form
                method="POST"
                action="{{ route('whatsapp.post') }}"
                enctype="multipart/form-data"
            >

                @csrf


                {{-- Bulk Numbers --}}

                <div class="mb-4">

                    <label
                        class="block text-gray-700 text-sm font-bold mb-2"
                        for="phones"
                    >
                        Recipient Phone Numbers
                    </label>

                    <p class="text-gray-600 text-xs mb-2">
                        Enter one number per line.
                        Indian format is supported.
                    </p>

                    <textarea
                        name="phones[]"
                        id="phones"
                        class="w-full border rounded px-3 py-2 @error('phones') border-red-500 @enderror"
                        rows="6"
                        placeholder="6354448612&#10;9876543210&#10;6354448612"
                    >{{ old('phones') ? implode("\n", (array) old('phones')) : '' }}</textarea>

                    @error('phones')

                        <div class="text-red-500 text-xs mt-1">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Name --}}

                <div class="mb-4">

                    <label
                        class="block text-gray-700 text-sm font-bold mb-2"
                        for="name"
                    >
                        Your Name
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


                {{-- Single Number --}}

                <div class="mb-4">

                    <label
                        class="block text-gray-700 text-sm font-bold mb-2"
                        for="phone"
                    >
                        Or Single Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        id="phone"
                        class="w-full border rounded px-3 py-2"
                        placeholder="6354448612"
                        maxlength="10"
                        value="{{ old('phone') }}"
                    >

                    @error('phone')

                        <div class="text-red-500 text-xs mt-1">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Template --}}

                <div class="mb-4">

                    <label
                        class="block text-gray-700 text-sm font-bold mb-2"
                        for="template_id"
                    >
                        Select Template
                    </label>

                    <select
                        name="template_id"
                        id="template_id"
                        class="w-full border rounded px-3 py-2"
                    >

                        <option value="">
                            -- Select a Template --
                        </option>

                        @foreach($templates as $template)

                            <option
                                value="{{ $template->id }}"
                                {{ old('template_id') == $template->id ? 'selected' : '' }}
                            >
                                {{ $template->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Message --}}

                <div class="mb-4">

                    <label
                        class="block text-gray-700 text-sm font-bold mb-2"
                        for="message"
                    >
                        Message
                    </label>

                    <textarea
                        name="message"
                        id="message"
                        class="w-full border rounded px-3 py-2"
                        rows="4"
                        placeholder="Enter your WhatsApp message here..."
                    >{{ old('message') }}</textarea>

                    @error('message')

                        <div class="text-red-500 text-xs mt-1">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Media --}}

                <div class="mb-6">

                    <label
                        class="block text-gray-700 text-sm font-bold mb-2"
                        for="media"
                    >
                        Attach Media
                    </label>

                    <input
                        type="file"
                        name="media"
                        id="media"
                        class="w-full border rounded px-3 py-2"
                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                    >

                    <p class="text-gray-600 text-xs mt-1">
                        Max 5MB.
                        Supported: JPG, PNG, PDF, DOC, DOCX
                    </p>

                </div>


                {{-- Send --}}

                <button
                    type="submit"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded"
                >
                    Send WhatsApp Message
                </button>

            </form>

        </div>


        {{-- Navigation --}}

        <div class="flex flex-wrap gap-4">

            <a
                href="{{ route('messages.history') }}"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                View Message History
            </a>

            <a
                href="{{ route('templates.index') }}"
                class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded"
            >
                Manage Templates
            </a>

            <a
                href="{{ route('dashboard') }}"
                class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
            >
                Dashboard
            </a>

        </div>

    </main>

</div>

</body>

</html>