<x-layouts.app :title="'My Notifications'">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                <p class="text-sm text-gray-500 mt-1">Your exam schedule alerts and updates</p>
            </div>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <form method="POST" action="{{ route('student.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">Mark all as read</button>
            </form>
            @endif
        </div>
    </x-slot>

    <div class="space-y-3 max-w-2xl">
        @forelse($notifications as $notification)
        @php $isUnread = is_null($notification->read_at); @endphp
        <div class="card p-4 flex items-start gap-4 {{ $isUnread ? 'border-l-4 border-indigo-500' : '' }}">
            {{-- Icon --}}
            <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $isUnread ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>

            {{-- Body --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 {{ $isUnread ? '' : 'text-gray-600' }}">
                    {{ $notification->data['message'] ?? 'Exam schedule update' }}
                </p>
                @if(isset($notification->data['course_code']))
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $notification->data['course_code'] }} — {{ isset($notification->data['exam_date']) ? \Carbon\Carbon::parse($notification->data['exam_date'])->format('M j, Y') : '' }}
                </p>
                @endif
                <p class="text-[11px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 shrink-0">
                @if(isset($notification->data['allocation_id']))
                <a href="{{ route('student.exam-pass.show', $notification->data['allocation_id']) }}"
                   class="text-xs font-medium text-indigo-600 hover:text-indigo-900">View Pass</a>
                @endif

                @if($isUnread)
                <form method="POST" action="{{ route('student.notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit" class="text-xs text-gray-400 hover:text-gray-600">Mark read</button>
                </form>
                @endif

                <form method="POST" action="{{ route('student.notifications.destroy', $notification->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600">Delete</button>
                </form>
            </div>
        </div>
        @empty
        <div class="card p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-gray-500 font-medium">No notifications yet</p>
            <p class="text-sm text-gray-400 mt-1">You'll be notified here when your exam schedule is released.</p>
        </div>
        @endforelse

        @if($notifications->hasPages())
        <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.app>
