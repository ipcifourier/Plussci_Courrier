@once
    <style>
        .scan-folder-preview .text-slate-900 { color: #0f172a !important; }
        .scan-folder-preview .text-slate-700 { color: #334155 !important; }
        .scan-folder-preview .text-slate-600 { color: #475569 !important; }
        .scan-folder-preview .text-slate-500 { color: #64748b !important; }
        .scan-folder-preview .text-sky-700 { color: #0369a1 !important; }
    </style>
@endonce

<div class="scan-folder-preview space-y-4 p-4">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Dossier surveillé</p>
        <p class="mt-2 break-all text-sm font-medium text-slate-900">{{ $scanFolder }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $count }} fichier(s) prêts pour un traitement scanner par lot.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nom</th>
                    <th class="px-4 py-3 font-semibold">Format</th>
                    <th class="px-4 py-3 font-semibold">Taille</th>
                    <th class="px-4 py-3 font-semibold">Type détecté</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($files as $file)
                    <tr>
                        <td class="px-4 py-3 text-slate-900">{{ $file['name'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $file['extension'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $file['size'] }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $file['detected_type'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                            Le dossier scanner est vide.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>