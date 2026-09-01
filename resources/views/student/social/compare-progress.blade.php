@extends('layouts.app')
@section('title', 'Compare Progress')

@section('sidebar')
    @include('student._sidebar')
@endsection

@section('content')
<div class="max-w-5xl">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">Compare Progress with Friends</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Track where you stand in each enrolled course.</p>

    <div class="space-y-4">
        @forelse($rows as $row)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-white">{{ $row['classroom']->name }}</h2>
                    <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">You: {{ number_format($row['my_progress'], 1) }}%</span>
                </div>

                @if(collect($row['friends'])->isEmpty())
                    <p class="text-sm text-slate-500 dark:text-slate-400">No friends enrolled in this course yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-500 dark:text-slate-400 border-b border-gray-100 dark:border-slate-700">
                                    <th class="py-2 pr-3">Friend</th>
                                    <th class="py-2 pr-3">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($row['friends'] as $friend)
                                    <tr class="border-b border-gray-50 dark:border-slate-700/60 last:border-0">
                                        <td class="py-2 pr-3 text-slate-700 dark:text-slate-300">{{ $friend['name'] }}</td>
                                        <td class="py-2 pr-3 font-medium text-slate-800 dark:text-slate-200">{{ number_format($friend['progress'], 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-dashed border-gray-200 dark:border-slate-700 p-8 text-center text-slate-500 dark:text-slate-400">
                Join a course to start progress comparisons.
            </div>
        @endforelse
    </div>
</div>
@endsection
