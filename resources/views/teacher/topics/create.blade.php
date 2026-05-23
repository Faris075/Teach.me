@extends('layouts.app')
@section('title', 'Create Topic')

@section('sidebar')
    @include('teacher._sidebar')
@endsection

@section('content')
<div class="max-w-lg">
    <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-6">
        ← {{ $classroom->name }}
    </a>

    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-6">Add Topic</h1>

    <form method="POST" action="{{ route('teacher.classrooms.topics.store', $classroom) }}"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Topic Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" autofocus
                   class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g. Chapter 1: Stoichiometry">
            @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
                Add Topic
            </button>
            <a href="{{ route('teacher.classrooms.show', $classroom) }}"
               class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
