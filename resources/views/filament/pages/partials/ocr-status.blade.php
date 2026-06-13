<div class="space-y-4 p-4">
    {{-- Tesseract status --}}
    <div class="rounded-lg border p-4 {{ $tesseractPath ? 'border-success-200 bg-success-50 dark:bg-success-950 dark:border-success-800' : 'border-warning-200 bg-warning-50 dark:bg-warning-950 dark:border-warning-800' }}">
        <div class="flex items-center gap-3">
            <x-filament::icon
                :icon="$tesseractPath ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle'"
                class="{{ $tesseractPath ? 'text-success-600' : 'text-warning-600' }} h-5 w-5"
            />
            <div>
                <p class="font-semibold text-sm">
                    Tesseract OCR :
                    @if($tesseractPath)
                        <span class="text-success-700 dark:text-success-400">Installé ({{ $tesseractPath }})</span>
                    @else
                        <span class="text-warning-700 dark:text-warning-400">Non installé</span>
                    @endif
                </p>
                @if(! $tesseractPath)
                    <p class="text-xs text-gray-500 mt-1">
                        Pour l'OCR des images et PDFs scannés, installez Tesseract :
                        <a href="https://github.com/UB-Mannheim/tesseract/wiki" target="_blank" class="underline">
                            Télécharger (Windows)
                        </a>
                        ou <code>sudo apt-get install tesseract-ocr tesseract-ocr-fra</code> (Linux).
                        Puis définissez <code>TESSERACT_PATH</code> dans le <code>.env</code>.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- OCR job stats --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $pendingCount }}</p>
            <p class="text-xs text-gray-500 mt-1">En attente</p>
        </div>
        <div class="rounded-lg bg-primary-50 dark:bg-primary-900 p-3 text-center">
            <p class="text-2xl font-bold text-primary-700 dark:text-primary-300">{{ $processingCount }}</p>
            <p class="text-xs text-gray-500 mt-1">En cours</p>
        </div>
        <div class="rounded-lg bg-success-50 dark:bg-success-900 p-3 text-center">
            <p class="text-2xl font-bold text-success-700 dark:text-success-300">{{ $completedCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Terminé</p>
        </div>
        <div class="rounded-lg bg-danger-50 dark:bg-danger-900 p-3 text-center">
            <p class="text-2xl font-bold text-danger-700 dark:text-danger-300">{{ $failedCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Échoué</p>
        </div>
    </div>

    @if($pendingCount > 0)
        <p class="text-xs text-gray-500">
            Les jobs OCR s'exécutent via la queue Laravel. Assurez-vous qu'un worker est actif :
            <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan queue:work</code>
        </p>
    @endif
</div>
