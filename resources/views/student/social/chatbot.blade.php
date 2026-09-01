@extends('layouts.app')
@section('title', 'AI Course Assistant')

@section('sidebar')
    @include('student._sidebar')
@endsection

@section('content')
<div class="max-w-4xl">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">AI Course Assistant</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Get personalized recommendations powered by your current course activity.</p>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5 mb-6">
        <form method="POST" action="{{ route('student.chatbot.recommend') }}" class="space-y-3">
            @csrf
            <textarea name="prompt" rows="4" required class="w-full rounded-xl border-gray-300 dark:border-slate-600 dark:bg-slate-900 text-sm" placeholder="Example: Recommend 3 courses to improve my writing and exam strategy.">{{ old('prompt', $prompt ?? '') }}</textarea>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">Get Recommendations</button>
        </form>
    </div>

    @if(!empty($recommendations))
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-slate-800 dark:text-white">Suggested Courses</h2>
                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">Provider: {{ $provider ?? 'local-fallback' }}</span>
            </div>
            <div class="space-y-3">
                @foreach($recommendations as $item)
                    <div class="rounded-xl border border-gray-100 dark:border-slate-700 p-3">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $item['title'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $item['reason'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
