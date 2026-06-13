<div class="space-y-3">
    @forelse ($workflow->steps as $step)
        <div class="flex items-start gap-4 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            {{-- Step order badge --}}
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                {{ $step->step_order }}
            </div>

            {{-- Step details --}}
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-900 dark:text-white">{{ $step->label }}</p>
                <p class="text-sm text-gray-500">
                    {{ $step->actionLabel() }}
                    @if ($step->approver)
                        &mdash; {{ $step->approver->name }}
                    @endif
                </p>
                <p class="text-xs text-gray-500">
                    SLA: {{ $step->sla_hours ?? 24 }}h
                    &middot; Source: {{ $step->slaSourceLabel() }}
                    @if ($step->deadlineAt())
                        &middot; Échéance: {{ $step->deadlineAt()->format('d/m/Y H:i') }}
                    @endif
                    @if ($step->escalated_at)
                        &middot; Escaladé le {{ $step->escalated_at->format('d/m/Y H:i') }}
                    @endif
                </p>
                @if ($step->comment)
                    <p class="mt-1 text-sm italic text-gray-500">"{{ $step->comment }}"</p>
                @endif
                @if ($step->decided_at)
                    <p class="mt-0.5 text-xs text-gray-400">{{ $step->decided_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>

            {{-- Status badge --}}
            <span @class([
                'inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                'bg-yellow-100 text-yellow-800' => $step->status === 'pending',
                'bg-green-100 text-green-800'   => $step->status === 'approved',
                'bg-red-100 text-red-800'        => $step->status === 'rejected',
                'bg-gray-100 text-gray-600'     => $step->status === 'skipped',
            ])>
                {{ $step->statusLabel() }}
            </span>
        </div>
    @empty
        <p class="text-sm text-gray-500">Aucune étape trouvée.</p>
    @endforelse

    @if ($workflow->final_comment)
        <div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Commentaire final&nbsp;:</p>
            <p class="mt-1 text-sm italic text-gray-600 dark:text-gray-400">"{{ $workflow->final_comment }}"</p>
        </div>
    @endif
</div>
