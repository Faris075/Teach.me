@extends('layouts.app')
@section('title', 'Create Assignment')

@section('sidebar')
    @include('teacher._sidebar')
@endsection

@section('content')
<div class="max-w-xl">
    <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-6">
        ← {{ $classroom->name }}
    </a>

    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-6">Create Assignment</h1>

    <form method="POST" action="{{ route('teacher.assignments.store', $classroom) }}"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label for="topic_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Topic</label>
            <select id="topic_id" name="topic_id"
                    class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @foreach($topics as $t)
                    <option value="{{ $t->id }}" {{ (old('topic_id', $topic?->id) == $t->id) ? 'selected' : '' }}>{{ $t->title }}</option>
                @endforeach
            </select>
            @error('topic_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" autofocus
                   class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description <span class="text-slate-400">(optional)</span></label>
            <textarea id="description" name="description" rows="4"
                      class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Due Date <span class="text-slate-400">(optional)</span></label>
                <input type="datetime-local" id="due_date" name="due_date" value="{{ old('due_date') }}"
                       class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="max_score" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Max Score</label>
                <input type="number" id="max_score" name="max_score" value="{{ old('max_score', 100) }}" min="0" step="0.5"
                       class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label for="grading_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Grading Type</label>
            <select id="grading_type" name="grading_type"
                    class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="percentage" {{ old('grading_type') === 'percentage' ? 'selected' : '' }}>Percentage (numeric)</option>
                <option value="igcse_letter" {{ old('grading_type') === 'igcse_letter' ? 'selected' : '' }}>IGCSE Letter Grade (A*–G)</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" id="allow_late_submissions" name="allow_late_submissions" value="1"
                   {{ old('allow_late_submissions', '1') === '1' ? 'checked' : '' }}
                   class="rounded border-gray-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500">
            <label for="allow_late_submissions" class="text-sm font-medium text-slate-700 dark:text-slate-300">Allow late submissions</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
                Create Assignment
            </button>
            <a href="{{ route('teacher.classrooms.show', $classroom) }}"
               class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
