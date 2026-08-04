@extends('layouts.app')

@section('header')
    <h1 class="text-2xl font-bold">Dashboard</h1>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-gray-500 text-sm font-medium">Total Sent</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ $totalSent }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-gray-500 text-sm font-medium">Total Failed</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ $totalFailed }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-gray-500 text-sm font-medium">Today Sent</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ $todaySent }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-gray-500 text-sm font-medium">Today Failed</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ $todayFailed }}</div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-bold mb-4">Messages (Last 30 Days)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($messagesByDay as $day)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ $day->sent_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">{{ $day->failed_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No messages in the last 30 days.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('messages.history') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">View Full History</a>
    </div>
@endsection