@extends('layouts.app')

@section('header')
    <h1 class="text-2xl font-bold">Edit Template</h1>
@endsection

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('templates.update', $template) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Template Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" class="w-full border rounded px-3 py-2 @error('name') border-red-500 @enderror" placeholder="e.g., Welcome Message">
                @error('name')
                    <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="content">Message Content</label>
                <textarea name="content" id="content" rows="5" class="w-full border rounded px-3 py-2 @error('content') border-red-500 @enderror" placeholder="Enter template content...">{{ old('content', $template->content) }}</textarea>
                <p class="text-gray-600 text-xs mt-1">Use <code>{name}</code> for variable placeholders</p>
                @error('content')
                    <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="variables">Variables (comma separated)</label>
                <input type="text" name="variables" id="variables" value="{{ old('variables', implode(', ', $template->variables ?? [])) }}" class="w-full border rounded px-3 py-2" placeholder="e.g., name, date, amount">
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} class="mr-2">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Template</button>
                <a href="{{ route('templates.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Cancel</a>
            </div>
        </form>
    </div>
@endsection