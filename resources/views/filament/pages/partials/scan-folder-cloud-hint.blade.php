<div class="space-y-4 p-1">

    {{-- Chemin actuel --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Dossier scan-inbox actuel (serveur)</p>
        <p class="mt-1 break-all font-mono text-sm text-gray-700">{{ $scanInboxPath ?: '— non configuré —' }}</p>
    </div>

    {{-- Option 1 : upload web --}}
    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
        <div class="flex gap-3">
            <x-heroicon-o-arrow-up-tray class="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />
            <div>
                <p class="text-sm font-semibold text-sky-800">Option 1 — Upload web (recommandé)</p>
                <p class="mt-1 text-sm text-sky-700">
                    Scannez vos documents localement, puis glissez-les directement dans la zone
                    <strong>«&nbsp;Déposer ou glisser des fichiers&nbsp;»</strong> de cette page.
                    Aucune configuration nécessaire.
                </p>
            </div>
        </div>
    </div>

    {{-- Option 2 : client desktop --}}
    <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
        <div class="flex gap-3">
            <x-heroicon-o-computer-desktop class="mt-0.5 h-5 w-5 shrink-0 text-violet-600" />
            <div>
                <p class="text-sm font-semibold text-violet-800">Option 2 — Client desktop (automatique)</p>
                <p class="mt-1 text-sm text-violet-700">
                    Installez le <strong>PLUSSCI Sync Client</strong> sur votre poste.
                    Configurez-y un dossier local surveillé (ex.&nbsp;<code class="rounded bg-violet-100 px-1 text-xs">C:\Scans\inbox</code>).
                    Chaque nouveau fichier déposé par votre scanner sera automatiquement envoyé vers la GED.
                </p>
                <p class="mt-2 text-xs text-violet-500">
                    Contactez votre administrateur système pour obtenir le fichier d'installation et votre jeton de connexion.
                </p>
            </div>
        </div>
    </div>

    {{-- Option 3 : admin réseau --}}
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
        <div class="flex gap-3">
            <x-heroicon-o-server class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div>
                <p class="text-sm font-semibold text-amber-800">Option 3 — Scanner réseau (SFTP / partage)</p>
                <p class="mt-1 text-sm text-amber-700">
                    Pour les scanners multifonctions capables d'envoyer vers un serveur FTP/SFTP,
                    votre administrateur peut configurer un dossier de dépôt sur le serveur.
                    Le chemin sera ensuite défini dans le compte Super Admin.
                </p>
            </div>
        </div>
    </div>

</div>
