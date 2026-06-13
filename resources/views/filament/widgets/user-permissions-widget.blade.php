<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Mes rôles et permissions</x-slot>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            {{-- Rôles --}}
            <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Rôles attribués</p>
                @if(empty($roles))
                    <span class="text-sm text-gray-400 italic">Aucun rôle</span>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach($roles as $role)
                            <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">
                                {{ $role }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Permissions --}}
            <div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Permissions effectives
                    <span class="ml-1 text-xs font-normal text-gray-400">({{ count($permissions) }})</span>
                </p>
                @if(empty($permissions))
                    <span class="text-sm text-gray-400 italic">Aucune permission</span>
                @else
                    <div class="flex flex-wrap gap-1 max-h-48 overflow-y-auto">
                        @foreach($permissions as $permission)
                            <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $permission }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
