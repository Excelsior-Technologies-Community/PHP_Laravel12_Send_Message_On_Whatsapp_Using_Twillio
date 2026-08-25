@extends('layouts.app')

@section('header')

<div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">

    <div>

        <h1 class="text-2xl font-bold text-gray-800">
            Message History
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Search, filter, export and manage WhatsApp messages.
        </p>

    </div>

    <div class="flex flex-wrap gap-2">

        <a
            href="{{ route('whatsapp') }}"
            class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg">
            + Send Message
        </a>

        <a
            href="{{ route('messages.export.csv', request()->query()) }}"
            class="bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg">
            ↓ Export CSV
        </a>

    </div>

</div>

@endsection


@section('content')

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">


    {{-- Flash Messages --}}

    @if(session('success'))

    <div class="mb-4 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>

    @endif


    @if(session('warning'))

    <div class="mb-4 bg-yellow-100 border border-yellow-300 text-yellow-700 px-4 py-3 rounded-lg">
        {{ session('warning') }}
    </div>

    @endif


    @if(session('error'))

    <div class="mb-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>

    @endif


    {{-- Search & Filters --}}

    <div class="bg-white shadow rounded-xl p-5 mb-6">

        <div class="flex justify-between items-center mb-4">

            <div>

                <h2 class="text-lg font-bold text-gray-800">
                    Search & Filter
                </h2>

                <p class="text-sm text-gray-500">
                    Find messages using phone, status or date.
                </p>

            </div>

        </div>


        <form
            method="GET"
            action="{{ route('messages.history') }}">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">


                {{-- Search --}}

                <div class="lg:col-span-2">

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Phone or message..."
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                </div>


                {{-- Status --}}

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>

                    <select
                        name="status"
                        class="w-full border-gray-300 rounded-lg shadow-sm">

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="sent"
                            {{ request('status') === 'sent' ? 'selected' : '' }}>
                            Sent
                        </option>

                        <option
                            value="failed"
                            {{ request('status') === 'failed' ? 'selected' : '' }}>
                            Failed
                        </option>

                    </select>

                </div>


                {{-- From Date --}}

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from_date"
                        value="{{ request('from_date') }}"
                        class="w-full border-gray-300 rounded-lg shadow-sm">

                </div>


                {{-- To Date --}}

                <div>

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ request('to_date') }}"
                        class="w-full border-gray-300 rounded-lg shadow-sm">

                </div>

            </div>


            <div class="flex flex-wrap gap-3 mt-4">

                <button
                    type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-5 rounded-lg">
                    🔍 Search
                </button>

                <a
                    href="{{ route('messages.history') }}"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-5 rounded-lg">
                    Reset
                </a>

            </div>

        </form>

    </div>


    {{-- Main History Card --}}

    <div class="bg-white shadow rounded-xl overflow-hidden">


        {{-- Header --}}

        <div class="px-5 py-4 border-b bg-gray-50">

            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">

                <div>

                    <h2 class="text-lg font-bold text-gray-800">
                        WhatsApp Messages
                    </h2>

                    <p class="text-sm text-gray-500">
                        {{ $messages->total() }} message(s) found
                    </p>

                </div>


                {{-- Per Page --}}

                <form
                    method="GET"
                    action="{{ route('messages.history') }}"
                    class="flex items-center gap-2">

                    @foreach(request()->except('per_page', 'page') as $key => $value)

                    @if(is_array($value))

                    @foreach($value as $item)

                    <input
                        type="hidden"
                        name="{{ $key }}[]"
                        value="{{ $item }}">

                    @endforeach

                    @else

                    <input
                        type="hidden"
                        name="{{ $key }}"
                        value="{{ $value }}">

                    @endif

                    @endforeach


                    <label class="text-sm text-gray-600">
                        Per Page
                    </label>

                    <select
                        name="per_page"
                        onchange="this.form.submit()"
                        class="border-gray-300 rounded-lg text-sm">

                        @foreach([5,10, 20, 50, 100] as $size)

                        <option
                            value="{{ $size }}"
                            {{ request('per_page', 5) == $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>

                        @endforeach

                    </select>

                </form>

            </div>

        </div>


        {{-- Bulk Delete Form --}}

        <form
            id="bulkDeleteForm"
            action="{{ route('messages.bulkDestroy') }}"
            method="POST">

            @csrf

            @method('DELETE')


            {{-- Bulk Toolbar --}}

            <div class="px-5 py-3 bg-gray-50 border-b flex flex-wrap items-center gap-3">

                <label class="flex items-center gap-2 text-sm text-gray-700">

                    <input
                        type="checkbox"
                        id="selectAll"
                        class="rounded border-gray-300">

                    Select All

                </label>


                <button
                    type="submit"
                    id="bulkDeleteButton"
                    disabled
                    onclick="return confirm('Delete selected messages?')"
                    class="bg-red-500 hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-2 px-4 rounded-lg">
                    🗑 Delete Selected
                </button>

            </div>


            {{-- Table --}}

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left">

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
                                Date
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Media
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="bg-white divide-y divide-gray-200">

                        @forelse($messages as $message)

                        <tr class="hover:bg-gray-50">


                            {{-- Checkbox --}}

                            <td class="px-4 py-4">

                                <input
                                    type="checkbox"
                                    name="message_ids[]"
                                    value="{{ $message->id }}"
                                    class="message-checkbox rounded border-gray-300">

                            </td>

                            {{-- Phone --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm">

                                <span class="font-semibold">
                                    +91 {{ $message->phone }}
                                </span>

                            </td>


                            {{-- Message --}}

                            <td class="px-6 py-4 text-sm max-w-xs">

                                <div
                                    class="truncate"
                                    title="{{ $message->message }}">
                                    {{ $message->message }}
                                </div>

                            </td>


                            {{-- Status --}}

                            <td class="px-6 py-4 whitespace-nowrap">

                                @if($message->status === 'sent')

                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    ✓ Sent
                                </span>

                                @else

                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    ✕ Failed
                                </span>

                                @endif

                            </td>

                            {{-- Date --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm">

                                {{ $message->sent_at?->format('M d, Y H:i') }}

                            </td>



                            {{-- Media --}}

                            <td class="px-6 py-4 whitespace-nowrap text-sm">

                                @if($message->media_path)

                                <a
                                    href="{{ Storage::url($message->media_path) }}"
                                    target="_blank"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                    View
                                </a>

                                @else

                                <span class="text-gray-400">
                                    —
                                </span>

                                @endif

                            </td>


                            {{-- Actions --}}

                            <td class="px-6 py-4 whitespace-nowrap">

                                <div class="flex items-center gap-2">


                                    {{-- View --}}

                                    <a
                                        href="{{ route('messages.show', $message) }}"
                                        class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold px-3 py-2 rounded">
                                        View
                                    </a>


                                    {{-- Retry --}}

                                    @if($message->status === 'failed')

                                    <form
                                        action="{{ route('messages.retry', $message) }}"
                                        method="POST">

                                        @csrf

                                        <button
                                            type="submit"
                                            onclick="return confirm('Retry this message?')"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold px-3 py-2 rounded">
                                            ↻ Retry
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
                                            class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-3 py-2 rounded">
                                            🗑
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-6 py-10 text-center text-gray-500">

                                <div class="text-4xl mb-2">
                                    📭
                                </div>

                                <p class="font-medium">
                                    No messages found.
                                </p>

                                <p class="text-sm mt-1">
                                    Try changing your search or filters.
                                </p>

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </form>


        {{-- Pagination --}}

        @if($messages->hasPages())

        <div class="px-5 py-4 border-t">

            {{ $messages->links() }}

        </div>

        @endif

    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {

        const selectAll =
            document.getElementById('selectAll');

        const checkboxes =
            document.querySelectorAll('.message-checkbox');

        const deleteButton =
            document.getElementById('bulkDeleteButton');


        function updateDeleteButton() {

            const checked =
                document.querySelectorAll(
                    '.message-checkbox:checked'
                ).length;

            deleteButton.disabled =
                checked === 0;
        }


        selectAll.addEventListener('change', function() {

            checkboxes.forEach(function(checkbox) {

                checkbox.checked =
                    selectAll.checked;

            });

            updateDeleteButton();

        });


        checkboxes.forEach(function(checkbox) {

            checkbox.addEventListener(
                'change',
                updateDeleteButton
            );

        });

    });
</script>

@endsection