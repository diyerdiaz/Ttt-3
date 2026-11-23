@props(['status', 'dotOnly' => false])
@php
    $tone = match ($status->value) {
        'new' => ['dot' => 'bg-sky-500', 'pill' => 'bg-sky-50 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300'],
        'contacted' => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-900 dark:bg-amber-950/50 dark:text-amber-300'],
        'qualified' => ['dot' => 'bg-violet-500', 'pill' => 'bg-violet-50 text-violet-800 dark:bg-violet-950/50 dark:text-violet-300'],
        'proposal' => ['dot' => 'bg-orange-500', 'pill' => 'bg-orange-50 text-orange-900 dark:bg-orange-950/50 dark:text-orange-300'],
        'won' => ['dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300'],
        default => ['dot' => 'bg-zinc-400', 'pill' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'],
    };
@endphp
@if ($dotOnly)
    <span aria-hidden="true" data-stage="{{ $status->value }}" class="inline-block size-2 shrink-0 rounded-full {{ $tone['dot'] }}"></span>
@else
    <span data-stage="{{ $status->value }}" class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium {{ $tone['pill'] }}">
        <span aria-hidden="true" class="size-1.5 rounded-full {{ $tone['dot'] }}"></span>{{ $status->label() }}
    </span>
@endif
