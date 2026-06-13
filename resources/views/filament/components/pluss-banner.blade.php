@once
    <style>
        .pluss-banner-wrap {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 0.32rem 1rem;
        }

        .pluss-banner {
            position: relative;
            overflow: hidden;
            width: min(1100px, 100%);
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 0.72rem;
            border-radius: 999px;
            padding: 0.56rem 1rem 0.56rem 0.72rem;
            font-weight: 700;
            letter-spacing: 0.018em;
            font-size: clamp(0.62rem, 1.15vw, 0.92rem);
            line-height: 1.25;
            color: #12345f;
            background:
                radial-gradient(160% 130% at 0% 50%, rgba(45, 156, 219, 0.12) 0%, rgba(45, 156, 219, 0) 46%),
                linear-gradient(95deg, #fbfdff 0%, #f1f8ff 52%, #ebf5ff 100%);
            border: 1px solid rgba(45, 109, 182, 0.2);
            box-shadow: 0 8px 18px rgba(23, 61, 120, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .pluss-banner::after {
            content: '';
            position: absolute;
            inset: -52% auto auto 44%;
            width: 16rem;
            height: 16rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(88, 185, 71, 0.12) 0%, rgba(88, 185, 71, 0) 72%);
            pointer-events: none;
        }

        .pluss-banner-logo {
            position: relative;
            z-index: 1;
            width: 2.05rem;
            height: 2.05rem;
            border-radius: 999px;
            padding: 0.2rem;
            background: linear-gradient(150deg, #ffffff 0%, #eef7ff 100%);
            border: 1px solid rgba(18, 52, 95, 0.18);
            box-shadow: 0 3px 8px rgba(18, 52, 95, 0.12);
            animation: plussBannerLogoFloat 6.4s ease-in-out infinite;
        }

        .pluss-banner-logo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: saturate(1.05) contrast(1.03);
        }

        .pluss-banner-text {
            position: relative;
            z-index: 1;
            text-align: center;
            text-transform: uppercase;
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        @keyframes plussBannerLogoFloat {
            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-1.5px) scale(1.006);
            }
        }

        @media (max-width: 768px) {
            .pluss-banner {
                border-radius: 14px;
                grid-template-columns: auto 1fr;
                gap: 0.56rem;
                padding: 0.48rem 0.68rem 0.48rem 0.55rem;
                letter-spacing: 0.02em;
            }

            .pluss-banner-logo {
                width: 1.74rem;
                height: 1.74rem;
            }

            .pluss-banner-text {
                text-align: left;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .pluss-banner-logo {
                animation: none;
            }
        }
    </style>
@endonce

<div class="pluss-banner-wrap">
    <div class="pluss-banner">
        <div class="pluss-banner-logo" aria-hidden="true">
            <img src="{{ asset('images/Logo One_Health.png') }}" alt="" />
        </div>

        <div class="pluss-banner-text">
            SYSTEME DOCUMENTAIRE ET COLLABORATIVE DE LA PLUSS-CI
        </div>
    </div>
</div>
