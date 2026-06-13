@php
    $events = $this->getEvents();
@endphp

<x-filament-panels::page>
    <div
        x-data="agendaCalendar(@js(json_decode($events, true)))"
        x-init="initCalendar()"
        class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4"
    >
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <button @click="calendar.prev()" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                ← Précédent
            </button>
            <button @click="calendar.today()" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-800">
                Aujourd'hui
            </button>
            <button @click="calendar.next()" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                Suivant →
            </button>
            <div class="ml-auto flex gap-2">
                <button @click="calendar.changeView('dayGridMonth')" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 hover:bg-amber-100 dark:bg-gray-700">Mois</button>
                <button @click="calendar.changeView('timeGridWeek')" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 hover:bg-amber-100 dark:bg-gray-700">Semaine</button>
                <button @click="calendar.changeView('listWeek')"   class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 hover:bg-amber-100 dark:bg-gray-700">Liste</button>
            </div>
        </div>

        {{-- Légende --}}
        <div class="flex flex-wrap gap-4 mb-4 text-xs text-gray-600 dark:text-gray-400">
            <span><span class="inline-block w-3 h-3 rounded-full bg-amber-500 mr-1"></span>Rendez-vous</span>
            <span><span class="inline-block w-3 h-3 rounded-full bg-violet-600 mr-1"></span>Réunions</span>
            <span><span class="inline-block w-3 h-3 rounded-full bg-cyan-600 mr-1"></span>Visites</span>
            <span><span class="inline-block w-3 h-3 rounded-full bg-rose-600 mr-1"></span>Diligences</span>
        </div>

        <div id="agenda-calendar" class="min-h-[600px]"></div>

        {{-- Modal de création d'événement --}}
        <div
            x-show="showTypeModal"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @keydown.escape.window="closeModal()"
        >
            <div
                x-show="showTypeModal"
                x-transition
                @click.outside="closeModal()"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4"
            >
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">
                    Créer un événement
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    Le <span class="font-medium text-amber-600" x-text="clickedDateDisplay"></span>
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <button
                        @click="goCreate('rdv')"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-amber-400 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 text-amber-700 dark:text-amber-300 font-medium transition"
                    >
                        <span class="text-2xl">📅</span>
                        <span class="text-sm">Rendez-vous</span>
                    </button>
                    <button
                        @click="goCreate('visite')"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-cyan-400 bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-900/20 dark:hover:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300 font-medium transition"
                    >
                        <span class="text-2xl">🤝</span>
                        <span class="text-sm">Visite</span>
                    </button>
                    <button
                        @click="goCreate('reunion')"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-violet-400 bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/20 dark:hover:bg-violet-900/40 text-violet-700 dark:text-violet-300 font-medium transition"
                    >
                        <span class="text-2xl">👥</span>
                        <span class="text-sm">Réunion</span>
                    </button>
                    <button
                        @click="goCreate('diligence')"
                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-rose-400 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 text-rose-700 dark:text-rose-300 font-medium transition"
                    >
                        <span class="text-2xl">✅</span>
                        <span class="text-sm">Diligence</span>
                    </button>
                </div>

                <button
                    @click="closeModal()"
                    class="mt-4 w-full text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-center transition"
                >
                    Annuler
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <style>
        td.fc-date-selected > .fc-daygrid-day-frame {
            background-color: #fef3c7 !important;
            outline: 2px solid #f59e0b;
            outline-offset: -2px;
            border-radius: 4px;
        }
        .dark td.fc-date-selected > .fc-daygrid-day-frame {
            background-color: #78350f33 !important;
            outline-color: #f59e0b;
        }
    </style>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('agendaCalendar', (events) => ({
            calendar: null,
            showTypeModal: false,
            clickedDate: '',
            clickedDateDisplay: '',
            selectedDayEl: null,

            initCalendar() {
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                this.calendar = new FullCalendar.Calendar(document.getElementById('agenda-calendar'), {
                    initialView: 'dayGridMonth',
                    locale: 'fr',
                    firstDay: 1,
                    height: 'auto',
                    headerToolbar: false,
                    events: events,
                    eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },

                    dateClick: (info) => {
                        const clicked = new Date(info.date);
                        clicked.setHours(0, 0, 0, 0);
                        if (clicked < today) return; // Bloquer les dates passées

                        // Mettre en surbrillance la cellule cliquée
                        if (this.selectedDayEl) {
                            this.selectedDayEl.classList.remove('fc-date-selected');
                        }
                        this.selectedDayEl = info.dayEl;
                        info.dayEl.classList.add('fc-date-selected');

                        // Formater la date pour l'URL (ISO local YYYY-MM-DDTHH:MM)
                        this.clickedDate = info.dateStr + 'T09:00';

                        // Formater pour affichage
                        this.clickedDateDisplay = info.date.toLocaleDateString('fr-FR', {
                            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                        });

                        this.showTypeModal = true;
                    },

                    eventClick: ({ event }) => {
                        const p = event.extendedProps;
                        const type = p.type === 'rdv' ? 'Rendez-vous' : (p.type === 'reunion' ? 'Réunion' : 'Visite');
                        const details = [
                            type,
                            event.startStr ? 'Début : ' + event.startStr.replace('T', ' ').substring(0,16) : '',
                            p.location ? 'Lieu : ' + p.location : '',
                            p.assignee ? 'Assigné à : ' + p.assignee : '',
                            p.status ? 'Statut : ' + p.status : '',
                        ].filter(Boolean).join('\n');
                        alert(event.title + '\n\n' + details);
                    },
                });
                this.calendar.render();
            },

            closeModal() {
                if (this.selectedDayEl) {
                    this.selectedDayEl.classList.remove('fc-date-selected');
                    this.selectedDayEl = null;
                }
                this.showTypeModal = false;
            },

            goCreate(type) {
                this.showTypeModal = false;
                const base = "{{ url('/admin') }}";
                const d    = encodeURIComponent(this.clickedDate);
                const urls = {
                    rdv:       `${base}/appointments/create?starts_at=${d}&type=rendez_vous`,
                    visite:    `${base}/visits/create?happened_at=${d}`,
                    reunion:   `${base}/meetings/create?starts_at=${d}`,
                    diligence: `${base}/appointments/create?starts_at=${d}&type=diligence`,
                };
                window.location.href = urls[type] ?? urls.rdv;
            },
        }));
    });
    </script>
    @endpush
</x-filament-panels::page>
