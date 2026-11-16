{{-- The line diff from Knowledge\Queries\RevisionDiff: old value lines in red, new value lines in green. --}}
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <pre class="overflow-x-auto rounded-lg border border-gray-200 p-3 text-sm dark:border-white/10" aria-label="{{ __('admin.fields.diff') }}">@foreach ($getState() ?? [] as $row)<span @class([
        'block',
        'bg-danger-50 text-danger-700 dark:bg-danger-950 dark:text-danger-300' => $row['op'] === '-',
        'bg-success-50 text-success-700 dark:bg-success-950 dark:text-success-300' => $row['op'] === '+',
    ])>{{ $row['op'] }} {{ $row['line'] }}</span>@endforeach</pre>
</x-dynamic-component>
