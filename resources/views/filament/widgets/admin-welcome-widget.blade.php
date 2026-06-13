@php
    $clockId = 'pluss-clock-' . uniqid();
    $dateId = 'pluss-date-' . uniqid();
@endphp

<div class="pluss-home-hero" role="status" aria-live="polite">
    <div class="pluss-home-hero__glow"></div>
    <div class="pluss-home-hero__content">
        <p class="pluss-home-hero__eyebrow">Espace administration PLUSS-CI</p>
        <h2 class="pluss-home-hero__title">
            Bonjour {{ $userName }}, vous etes bien connecte sur le SYSTEME DE GESTION DOCUMENTAIRE ET COLLABORATIVE DE LA PLUSS-CI.
        </h2>
        <p class="pluss-home-hero__subtitle">A vous de jouer. Cliquez sur <strong>Tableau de bord</strong> pour afficher vos widgets.</p>

        <div class="pluss-home-hero__meta">
            <div>
                <span class="pluss-home-hero__meta-label">Date</span>
                <strong id="{{ $dateId }}">{{ $now->translatedFormat('l d F Y') }}</strong>
            </div>
            <div>
                <span class="pluss-home-hero__meta-label">Heure</span>
                <strong id="{{ $clockId }}">{{ $now->format('H:i:s') }}</strong>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const dateEl = document.getElementById('{{ $dateId }}');
        const timeEl = document.getElementById('{{ $clockId }}');

        if (!dateEl || !timeEl) {
            return;
        }

        const formatterDate = new Intl.DateTimeFormat('fr-FR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: '2-digit',
        });

        const formatterTime = new Intl.DateTimeFormat('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        });

        const update = () => {
            const now = new Date();
            dateEl.textContent = formatterDate.format(now);
            timeEl.textContent = formatterTime.format(now);
        };

        update();
        setInterval(update, 1000);
    })();
</script>
