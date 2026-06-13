@once
    <style>
        .pluss-login-hero {
            position: relative;
            overflow: hidden;
            margin-bottom: 1.1rem;
            border-radius: 24px;
            padding: 1.2rem 1.2rem 1.1rem;
            color: #f8fbff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 34%),
                linear-gradient(145deg, #16345f 0%, #20539a 52%, #2b91d9 100%);
            box-shadow: 0 22px 44px rgba(10, 34, 66, 0.34);
        }

        .pluss-login-hero::before {
            content: '';
            position: absolute;
            inset: auto -10% -42% auto;
            width: 180px;
            height: 180px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            filter: blur(8px);
        }

        .pluss-login-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: inherit;
            pointer-events: none;
        }

        .pluss-login-hero-inner {
            position: relative;
            z-index: 1;
        }

        .pluss-login-hero-top {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .pluss-login-hero-mark {
            width: 3.5rem;
            height: 3.5rem;
            flex-shrink: 0;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
        }

        .pluss-login-hero-mark img {
            width: 2.35rem;
            height: 2.35rem;
            object-fit: contain;
            filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.18));
        }

        .pluss-login-hero-copy {
            min-width: 0;
        }

        .pluss-login-hero-kicker {
            margin: 0 0 0.2rem;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.72);
        }

        .pluss-login-hero-title {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            line-height: 1.22;
            text-wrap: balance;
        }

        .pluss-login-hero-subtitle {
            margin: 0.8rem 0 0;
            font-size: 0.8rem;
            line-height: 1.55;
            color: rgba(248, 251, 255, 0.84);
        }

        .pluss-login-hero-meta {
            margin-top: 0.95rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .pluss-login-hero-chip {
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .pluss-login-hero-trust {
            margin-top: 0.95rem;
            padding-top: 0.85rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.6rem;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
        }

        .pluss-login-hero-trust-item {
            min-width: 0;
        }

        .pluss-login-hero-trust-label {
            display: block;
            margin-bottom: 0.16rem;
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.62);
        }

        .pluss-login-hero-trust-value {
            display: block;
            font-size: 0.78rem;
            line-height: 1.35;
            color: rgba(255, 255, 255, 0.94);
        }

        @media (max-width: 640px) {
            .pluss-login-hero {
                padding: 1rem;
                border-radius: 20px;
            }

            .pluss-login-hero-top {
                align-items: flex-start;
            }

            .pluss-login-hero-trust {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endonce

@php
    $clockId = 'pluss-login-clock-' . uniqid();
    $dateId = 'pluss-login-date-' . uniqid();
@endphp

<div class="pluss-login-hero">
    <div class="pluss-login-hero-inner">
        <div class="pluss-login-hero-top">
            <div class="pluss-login-hero-mark" aria-hidden="true">
                <img src="{{ asset('images/Logo One_Health.png') }}" alt="" />
            </div>

            <div class="pluss-login-hero-copy">
                <p class="pluss-login-hero-kicker">Espace securise PLUSS-CI</p>
                <p class="pluss-login-hero-title">Systeme de gestion documentaire et collaborative</p>
            </div>
        </div>

        <p class="pluss-login-hero-subtitle">Accedez a votre espace de travail, vos courriers, vos dossiers GED et vos taches dans une interface unifiee.</p>

        <div class="pluss-login-hero-meta" role="status" aria-live="polite">
            <span id="{{ $dateId }}" class="pluss-login-hero-chip">{{ now()->translatedFormat('l d F Y') }}</span>
            <span id="{{ $clockId }}" class="pluss-login-hero-chip">{{ now()->format('H:i:s') }}</span>
            <span class="pluss-login-hero-chip">Connexion admin</span>
        </div>

        <div class="pluss-login-hero-trust" aria-label="Informations plateforme">
            <div class="pluss-login-hero-trust-item">
                <span class="pluss-login-hero-trust-label">Acces</span>
                <span class="pluss-login-hero-trust-value">Profil et backoffice centralises</span>
            </div>
            <div class="pluss-login-hero-trust-item">
                <span class="pluss-login-hero-trust-label">Securite</span>
                <span class="pluss-login-hero-trust-value">Sessions controlees et rotation des mots de passe</span>
            </div>
            <div class="pluss-login-hero-trust-item">
                <span class="pluss-login-hero-trust-label">Usage</span>
                <span class="pluss-login-hero-trust-value">Courriers, GED, agendas et collaboration</span>
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
