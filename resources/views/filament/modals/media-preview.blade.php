@php
    /** @var \Illuminate\Support\Collection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $mediaItems */
    $mediaItems = $mediaItems ?? collect();
    $emptyMessage = $emptyMessage ?? 'Aucun fichier disponible.';

    $previewableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'txt'];

    $firstPreviewable = $mediaItems->first(function ($media) use ($previewableExtensions) {
        $extension = strtolower(pathinfo((string) $media->file_name, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) ($media->mime_type ?? ''));

        return in_array($extension, $previewableExtensions, true)
            || str_starts_with($mimeType, 'image/');
    });
@endphp

<div class="space-y-4">
    @if ($mediaItems->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
            {{ $emptyMessage }}
        </div>
    @else
        @if ($firstPreviewable)
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <iframe
                    src="{{ $firstPreviewable->getUrl() }}"
                    class="h-[70vh] w-full"
                    title="Apercu fichier"
                ></iframe>
            </div>
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Aucun apercu integre pour ce type de fichier. Utilisez le bouton Telecharger ci-dessous.
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h4 class="mb-3 text-sm font-semibold text-gray-900">Fichiers disponibles</h4>
            <div class="space-y-2">
                @foreach ($mediaItems as $media)
                    <div class="flex items-center justify-between gap-3 rounded-md border border-gray-100 px-3 py-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $media->file_name }}</p>
                            <p class="text-xs text-gray-500">{{ strtoupper(pathinfo((string) $media->file_name, PATHINFO_EXTENSION)) }} · {{ $media->human_readable_size ?? '' }}</p>
                        </div>
                        <a
                            href="{{ $media->getUrl() }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                        >
                            Telecharger
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
