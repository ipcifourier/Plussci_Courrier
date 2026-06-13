<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PLUSS-CI | Plateforme Une Seule Sante</title>
    <style>
        :root {
            --pluss-blue-deep: #173d78;
            --pluss-blue: #2d9cdb;
            --pluss-green: #58b947;
            --pluss-orange: #f08a2b;
            --pluss-ink: #10223f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Trebuchet MS", Tahoma, sans-serif;
            color: var(--pluss-ink);
            background:
                radial-gradient(1100px 500px at 110% -15%, rgba(88, 185, 71, 0.25), transparent 58%),
                radial-gradient(900px 420px at -10% -10%, rgba(45, 156, 219, 0.25), transparent 62%),
                linear-gradient(180deg, #fafdff 0%, #ecf5ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .page {
            width: min(980px, 100%);
            border-radius: 30px;
            padding: clamp(1.2rem, 3vw, 2.2rem);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(247, 252, 255, 0.92));
            border: 1px solid rgba(23, 61, 120, 0.14);
            box-shadow: 0 30px 60px rgba(23, 61, 120, 0.16);
            animation: pageEnter 0.65s ease-out both;
        }

        .center-wrap {
            display: grid;
            gap: 1rem;
            justify-items: center;
            text-align: center;
        }

        .brand {
            display: grid;
            gap: 0.55rem;
            justify-items: center;
            align-items: center;
            font-weight: 800;
            color: var(--pluss-blue-deep);
            letter-spacing: 0.03em;
        }

        .brand img {
            width: clamp(74px, 11vw, 108px);
            height: clamp(74px, 11vw, 108px);
            object-fit: contain;
            filter: drop-shadow(0 14px 24px rgba(23, 61, 120, 0.2));
            animation: logoFloat 4.8s ease-in-out infinite;
        }

        .brand span {
            font-size: clamp(0.8rem, 1.6vw, 1rem);
        }

        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.9rem;
            animation: fadeSlideUp 0.8s ease-out 0.12s both;
        }

        .hero h1 {
            margin: 0;
            color: var(--pluss-blue-deep);
            font-size: clamp(1.12rem, 2.5vw, 1.9rem);
            line-height: 1.3;
            max-width: 34ch;
        }

        .hero p {
            margin: 0;
            font-size: clamp(0.93rem, 1.6vw, 1.04rem);
            line-height: 1.55;
            max-width: 72ch;
        }

        .hero-badges {
            margin-top: 0.3rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.45rem;
        }

        .hero-badge {
            border: 1px solid rgba(23, 61, 120, 0.2);
            background: rgba(255, 255, 255, 0.85);
            border-radius: 999px;
            padding: 0.37rem 0.75rem;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--pluss-blue-deep);
        }

        .actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.6rem;
            margin-top: 0.5rem;
            animation: fadeSlideUp 0.8s ease-out 0.2s both;
        }

        .btn {
            text-decoration: none;
            border-radius: 999px;
            padding: 0.68rem 1.1rem;
            font-weight: 700;
            font-size: 0.92rem;
            transition: transform .2s ease, box-shadow .2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn-soft {
            color: var(--pluss-blue-deep);
            border: 1px solid rgba(23, 61, 120, 0.24);
            background: rgba(255, 255, 255, 0.8);
        }

        .btn-main {
            color: #fff;
            background: linear-gradient(90deg, var(--pluss-blue-deep), var(--pluss-blue));
            box-shadow: 0 12px 28px rgba(23, 61, 120, 0.28);
            position: relative;
            overflow: hidden;
        }

        .btn-main::after {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            box-shadow: 0 0 0 0 rgba(88, 185, 71, 0.4);
            transition: box-shadow 0.22s ease;
            pointer-events: none;
        }

        .btn-main:hover::after {
            box-shadow: 0 0 0 5px rgba(88, 185, 71, 0.22);
        }

        .footer {
            margin-top: 0.8rem;
            font-size: 0.86rem;
            color: #345278;
            text-align: center;
            animation: fadeInSoft 0.9s ease-out 0.28s both;
        }

        .footer strong { color: var(--pluss-orange); }

        @keyframes pageEnter {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.995);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInSoft {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .page,
            .brand img,
            .hero,
            .actions,
            .footer {
                animation: none !important;
            }
        }

        @media (max-width: 960px) {
            .page {
                border-radius: 20px;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="center-wrap">
            <div class="brand">
                <img src="{{ asset('images/Logo One_Health.png') }}" alt="Logo Plateforme Une Seule Sante" />
                <span>PLATEFORME UNE SEULE SANTE - COTE D'IVOIRE</span>
            </div>

            <div class="hero">
                <h1>SYSTEME DE GESTION DOCUMENTAIRE ET COLLABORATIVE DE LA PLUSS-CI</h1>
                <p>
                    Un espace unifie pour centraliser les courriers, documents, reunions et diligences,
                    avec une tracabilite claire, une collaboration fluide et une gouvernance fiable.
                </p>

                <div class="hero-badges">
                    <span class="hero-badge">Charte ONE HEALTH</span>
                    <span class="hero-badge">Traitement en temps reel</span>
                    <span class="hero-badge">Gestion collaborative</span>
                </div>
            </div>

            <div class="actions">
                <a class="btn btn-main" href="{{ url('/admin/login') }}">Se connecter</a>
                @auth
                    <a class="btn btn-soft" href="{{ url('/admin') }}">Acceder a l'administration</a>
                @endauth
            </div>

            <footer class="footer">
                <strong>PLUSS-CI</strong> - Efficacite administrative, collaboration et transparence documentaire.
            </footer>
        </section>
    </main>
</body>
</html>
