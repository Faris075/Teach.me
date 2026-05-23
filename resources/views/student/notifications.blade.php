@extends('layouts.app')
@section('title', 'Notifications')

@section('sidebar')
    @include('student._sidebar')
@endsection

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-6">Notifications</h1>

    @if($notifications->isEmpty())
        <p class="text-slate-400 dark:text-slate-500 text-sm">No notifications yet.</p>
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
                @php $data = $notification->data; @endphp
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-medium text-slate-800 dark:text-white text-sm">
                                Grade published: <span class="text-indigo-600 dark:text-indigo-400">{{ $data['assignment_title'] ?? '' }}</span>
                            </p>
                            @if(!empty($data['grade_literal']))
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Grade: <strong>{{ $data['grade_literal'] }}</strong></p>
                            @elseif(isset($data['grade_numeric']))
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Score: <strong>{{ $data['grade_numeric'] }}</strong></p>
                            @endif
                            @if(!empty($data['feedback']))
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 italic">{{ $data['feedback'] }}</p>
                            @endif
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if(!empty($data['url']))
                            <a href="{{ $data['url'] }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap font-medium">View →</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
