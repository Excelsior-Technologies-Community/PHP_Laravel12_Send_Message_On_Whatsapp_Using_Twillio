@extends('layouts.app')

@section('header')

<div class="flex items-center justify-between">

    <div>
        <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
            <a
                href="{{ route('messages.history') }}"
                class="hover:text-indigo-600">
                Messages
            </a>

            <span>/</span>

            <span>Details</span>
        </div>

        <h1 class="text-2xl font-bold text-gray-900">
            Message Details
        </h1>

    </div>

    <a
        href="{{ route('messages.history') }}"
        class="inline-flex items-center gap-2
        bg-white border border-gray-300
        hover:bg-gray-50
        text-gray-700 font-semibold
        px-4 py-2 rounded-lg
        text-sm shadow-sm transition">
        ← Back
    </a>

</div>

@endsection


@section('content')

<div class="max-w-6xl mx-auto px-4 py-4">

    {{-- ========================================================= --}}
    {{-- MAIN CARD --}}
    {{-- ========================================================= --}}

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        {{-- ===================================================== --}}
        {{-- HEADER --}}
        {{-- ===================================================== --}}

        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">

            <div class="flex items-center justify-between">

                <div class="flex items-center gap-3">

                    <div
                        class="w-11 h-11 rounded-xl
                        bg-gradient-to-br from-green-500 to-emerald-600
                        flex items-center justify-center
                        text-white text-xl shadow-sm">
                        💬
                    </div>

                    <div>

                        <div class="flex items-center gap-2">

                            <h2 class="font-bold text-gray-900">
                                WhatsApp Message
                            </h2>

                            <span
                                class="text-xs font-semibold
                                bg-gray-200 text-gray-600
                                px-2 py-0.5 rounded-full">
                                #{{ $message->id }}
                            </span>

                        </div>

                        <p class="text-xs text-gray-500">
                            +91 {{ $message->phone }}
                        </p>

                    </div>

                </div>


                {{-- Status --}}

                @if($message->status === 'sent')

                <span
                    class="inline-flex items-center gap-2
                        bg-green-100 text-green-700
                        px-3 py-1.5 rounded-full
                        text-xs font-bold">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    Sent
                </span>

                @else

                <span
                    class="inline-flex items-center gap-2
                        bg-red-100 text-red-700
                        px-3 py-1.5 rounded-full
                        text-xs font-bold">
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                    Failed
                </span>

                @endif

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- CONTENT --}}
        {{-- ===================================================== --}}

        <div class="p-5">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- ================================================= --}}
                {{-- LEFT: MESSAGE --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-2">

                    <div class="border border-gray-200 rounded-xl overflow-hidden">

                        <div
                            class="px-4 py-3
                            bg-gray-50
                            border-b border-gray-200
                            flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-bold text-gray-900">
                                    Message Content
                                </h3>

                                <p class="text-xs text-gray-500">
                                    WhatsApp message
                                </p>

                            </div>

                            <span class="text-lg">
                                💬
                            </span>

                        </div>


                        {{-- Message Bubble --}}

                        <div class="p-4">

                            <div
                                class="bg-green-50
                                border border-green-100
                                rounded-xl
                                p-4">

                                <p
                                    class="text-sm text-gray-800
                                    leading-6 whitespace-pre-wrap">
                                    {{ $message->message }}
                                </p>

                                <div
                                    class="flex justify-end
                                    items-center gap-2
                                    mt-3 pt-2
                                    border-t border-green-100">

                                    <span class="text-xs text-gray-500">
                                        {{ $message->sent_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                    </span>

                                    @if($message->status === 'sent')

                                    <span class="text-xs text-green-600">
                                        ✓✓
                                    </span>

                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- INFORMATION GRID --}}
                    {{-- ================================================= --}}

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-4">


                        {{-- Phone --}}

                        <div
                            class="border border-gray-200
                            rounded-xl p-3
                            hover:bg-blue-50
                            transition">

                            <p class="text-xs text-gray-500 mb-1">
                                Recipient
                            </p>

                            <p class="text-sm font-bold text-gray-900">
                                +91 {{ $message->phone }}
                            </p>

                        </div>


                        {{-- Status --}}

                        <div
                            class="border border-gray-200
                            rounded-xl p-3
                            hover:bg-green-50
                            transition">

                            <p class="text-xs text-gray-500 mb-1">
                                Status
                            </p>

                            @if($message->status === 'sent')

                            <p class="text-sm font-bold text-green-600">
                                ✓ Sent
                            </p>

                            @else

                            <p class="text-sm font-bold text-red-600">
                                ✕ Failed
                            </p>

                            @endif

                        </div>


                        {{-- Sent At --}}

                        <div
                            class="border border-gray-200
                            rounded-xl p-3
                            hover:bg-purple-50
                            transition">

                            <p class="text-xs text-gray-500 mb-1">
                                Sent At
                            </p>

                            <p class="text-sm font-bold text-gray-900">
                                {{ $message->sent_at?->format('M d, Y') ?? 'N/A' }}
                            </p>

                            <p class="text-xs text-gray-500">
                                {{ $message->sent_at?->format('h:i A') ?? '' }}
                            </p>

                        </div>


                        {{-- Sent By --}}

                        <div
                            class="border border-gray-200
                            rounded-xl p-3
                            hover:bg-orange-50
                            transition">

                            <p class="text-xs text-gray-500 mb-1">
                                Sent By
                            </p>

                            <p class="text-sm font-bold text-gray-900 truncate">
                                {{ $message->user?->name ?? 'N/A' }}
                            </p>

                        </div>


                        {{-- Attachment --}}

                        <div
                            class="border border-gray-200
                            rounded-xl p-3
                            hover:bg-cyan-50
                            transition">

                            <p class="text-xs text-gray-500 mb-1">
                                Attachment
                            </p>

                            @if($message->media_path)

                            <a
                                href="{{ Storage::url($message->media_path) }}"
                                target="_blank"
                                class="text-sm font-bold text-blue-600 hover:text-blue-800">
                                📎 View File ↗
                            </a>

                            @else

                            <p class="text-sm font-medium text-gray-400">
                                No attachment
                            </p>

                            @endif

                        </div>


                        {{-- Database ID --}}

                        <div
                            class="border border-gray-200
                            rounded-xl p-3
                            hover:bg-gray-50
                            transition">

                            <p class="text-xs text-gray-500 mb-1">
                                Database ID
                            </p>

                            <p class="text-sm font-bold text-gray-900">
                                #{{ $message->id }}
                            </p>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- RIGHT: SUMMARY --}}
                {{-- ================================================= --}}

                <div>

                    <div
                        class="border border-gray-200
                        rounded-xl overflow-hidden">

                        <div
                            class="px-4 py-3
                            bg-gray-50
                            border-b border-gray-200">

                            <h3 class="text-sm font-bold text-gray-900">
                                Message Summary
                            </h3>

                            <p class="text-xs text-gray-500 mt-0.5">
                                Quick information
                            </p>

                        </div>


                        <div class="p-4 space-y-4">


                            {{-- Status --}}

                            <div>

                                <p class="text-xs text-gray-500">
                                    Delivery Status
                                </p>

                                @if($message->status === 'sent')

                                <div class="mt-1 flex items-center gap-2">

                                    <span
                                        class="w-2.5 h-2.5
                                            bg-green-500 rounded-full"></span>

                                    <span class="text-sm font-bold text-green-600">
                                        Successfully Sent
                                    </span>

                                </div>

                                @else

                                <div class="mt-1 flex items-center gap-2">

                                    <span
                                        class="w-2.5 h-2.5
                                            bg-red-500 rounded-full"></span>

                                    <span class="text-sm font-bold text-red-600">
                                        Sending Failed
                                    </span>

                                </div>

                                @endif

                            </div>


                            <div class="border-t"></div>


                            {{-- Recipient --}}

                            <div>

                                <p class="text-xs text-gray-500">
                                    Recipient
                                </p>

                                <p class="text-sm font-bold text-gray-900 mt-1">
                                    +91 {{ $message->phone }}
                                </p>

                            </div>


                            {{-- Date --}}

                            <div>

                                <p class="text-xs text-gray-500">
                                    Date & Time
                                </p>

                                <p class="text-sm font-bold text-gray-900 mt-1">
                                    {{ $message->sent_at?->format('M d, Y') ?? 'N/A' }}
                                </p>

                                <p class="text-xs text-gray-500">
                                    {{ $message->sent_at?->format('h:i A') ?? '' }}
                                </p>

                            </div>


                            {{-- User --}}

                            <div>

                                <p class="text-xs text-gray-500">
                                    Sent By
                                </p>

                                <p class="text-sm font-bold text-gray-900 mt-1">
                                    {{ $message->user?->name ?? 'N/A' }}
                                </p>

                            </div>


                            {{-- Attachment --}}

                            <div>

                                <p class="text-xs text-gray-500">
                                    Attachment
                                </p>

                                @if($message->media_path)

                                <a
                                    href="{{ Storage::url($message->media_path) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1
                                        text-sm font-semibold
                                        text-indigo-600
                                        hover:text-indigo-800
                                        mt-1">
                                    📎 View Attachment
                                </a>

                                @else

                                <p class="text-sm text-gray-400 mt-1">
                                    No attachment
                                </p>

                                @endif

                            </div>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- ACTIONS --}}
                    {{-- ================================================= --}}

                    <div class="border border-gray-200 rounded-xl p-4 mt-4">

                        <p class="text-sm font-bold text-gray-900 mb-3">
                            Actions
                        </p>


                        <div class="space-y-2">


                            {{-- Retry --}}

                            @if($message->status === 'failed')

                            <form
                                action="{{ route('messages.retry', $message) }}"
                                method="POST">

                                @csrf

                                <button
                                    type="submit"
                                    onclick="return confirm('Retry sending this message?')"
                                    class="w-full inline-flex items-center
                                        justify-center gap-2
                                        bg-amber-500
                                        hover:bg-amber-600
                                        text-white
                                        font-semibold
                                        text-sm
                                        px-4 py-2.5
                                        rounded-lg
                                        transition">
                                    ↻ Retry Message
                                </button>

                            </form>

                            @endif


                            {{-- Delete --}}

                            <form
                                action="{{ route('messages.destroy', $message) }}"
                                method="POST">

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    onclick="return confirm('Delete this message permanently?')"
                                    class="w-full inline-flex items-center
                                    justify-center gap-2
                                    bg-red-50
                                    hover:bg-red-600
                                    text-red-600
                                    hover:text-white
                                    border border-red-200
                                    hover:border-red-600
                                    font-semibold
                                    text-sm
                                    px-4 py-2.5
                                    rounded-lg
                                    transition">
                                    🗑 Delete Message
                                </button>

                            </form>


                            {{-- Back --}}

                            <a
                                href="{{ route('messages.history') }}"
                                class="w-full inline-flex items-center
                                justify-center gap-2
                                bg-gray-100
                                hover:bg-gray-200
                                text-gray-700
                                font-semibold
                                text-sm
                                px-4 py-2.5
                                rounded-lg
                                transition">
                                ← Message History
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Footer --}}

    <div class="text-center mt-3">

        <p class="text-xs text-gray-400">
            Message #{{ $message->id }}
            •
            Created {{ $message->created_at?->format('M d, Y h:i A') }}
        </p>

    </div>

</div>

@endsection