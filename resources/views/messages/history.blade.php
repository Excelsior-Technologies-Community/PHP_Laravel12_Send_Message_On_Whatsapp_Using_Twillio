@extends('layouts.app')

@section('header')

    <div class="flex justify-between items-center">

        <div>

            <h1 class="text-2xl font-bold">
                Message History
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Track sent and failed WhatsApp messages.
            </p>

        </div>

        <a
            href="{{ route('whatsapp') }}"
            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
        >
            Send New Message
        </a>

    </div>

@endsection


@section('content')

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">


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


    {{-- Feature information --}}

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">

        <h3 class="font-semibold text-blue-800">
            WhatsApp Message Protection
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">

            <div>

                <p class="text-sm font-semibold text-blue-700">
                    Duplicate Detection
                </p>

                <p class="text-xs text-blue-600">
                    Successfully sent numbers are protected
                    from duplicate sending for 24 hours.
                </p>

            </div>

            <div>

                <p class="text-sm font-semibold text-blue-700">
                    Failed Messages
                </p>

                <p class="text-xs text-blue-600">
                    Failed messages remain available
                    for retry.
                </p>

            </div>

            <div>

                <p class="text-sm font-semibold text-blue-700">
                    Message Tracking
                </p>

                <p class="text-xs text-blue-600">
                    Every sent and failed attempt is recorded
                    in history.
                </p>

            </div>

        </div>

    </div>


    <div class="bg-white shadow rounded-lg overflow-hidden">


        {{-- Header --}}

        <div class="px-4 py-4 border-b bg-gray-50">

            <div class="flex justify-between items-center">

                <div>

                    <h2 class="text-lg font-semibold text-gray-800">
                        WhatsApp Message History
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        View sent and failed WhatsApp messages.
                    </p>

                </div>

                <div class="text-sm text-gray-500">

                    {{ $messages->total() }}
                    message(s)

                </div>

            </div>

        </div>


        {{-- Table --}}

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">

                    <tr>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Date
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Phone
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Message
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Status
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Media
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="bg-white divide-y divide-gray-200">

                    @forelse($messages as $message)

                        <tr class="hover:bg-gray-50">

                            {{-- Date --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">

                                {{ $message->sent_at?->format('M d, Y H:i') }}

                            </td>


                            {{-- Phone --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">

                                <span class="font-medium">
                                    +91 {{ $message->phone }}
                                </span>

                            </td>


                            {{-- Message --}}

                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">

                                <div
                                    class="truncate"
                                    title="{{ $message->message }}"
                                >
                                    {{ $message->message }}
                                </div>

                            </td>


                            {{-- Status --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm">

                                @if($message->status === 'sent')

                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                                    >
                                        ✓ Sent
                                    </span>

                                @else

                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
                                    >
                                        ✕ Failed
                                    </span>

                                @endif

                            </td>


                            {{-- Media --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm">

                                @if($message->media_path)

                                    <a
                                        href="{{ Storage::url($message->media_path) }}"
                                        target="_blank"
                                        class="text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        View
                                    </a>

                                @else

                                    <span class="text-gray-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Action --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm">

                                @if($message->status === 'failed')

                                    <form
                                        action="{{ route('messages.retry', $message) }}"
                                        method="POST"
                                        onsubmit="return confirm('Retry sending this WhatsApp message?')"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1 px-3 rounded"
                                        >
                                            ↻ Retry
                                        </button>

                                    </form>

                                @else

                                    <span class="text-gray-400">
                                        —
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-6 py-8 text-center text-sm text-gray-500"
                            >
                                No messages found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}

        <div class="px-4 py-4 border-t">

            {{ $messages->links() }}

        </div>

    </div>

</div>

@endsection