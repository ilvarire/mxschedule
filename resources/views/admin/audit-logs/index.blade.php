<x-layouts.app :title="'Audit Logs'">
    <x-slot name="header"><h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1></x-slot>
    <div class="card"><div class="table-container"><table class="data-table">
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Target</th><th>Changes</th></tr></thead>
        <tbody>@forelse($logs as $log)<tr>
            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $log->user->name ?? 'System' }}</td>
            <td>{{ $log->action }}</td>
            <td>{{ class_basename($log->auditable_type ?? '') }} #{{ $log->auditable_id }}</td>
            <td class="text-xs">{{ json_encode($log->new_values) }}</td>
        </tr>@empty<tr><td colspan="5">No audit records yet.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.app>
