@once
    <style>
        .pluss-dossier-tree {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(16, 185, 129, 0.14);
            background:
                radial-gradient(circle at top left, rgba(16, 185, 129, 0.15), transparent 32%),
                linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(240, 253, 250, 0.94));
            border-radius: 1.5rem;
            padding: 1.35rem;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        }

        .pluss-dossier-tree__header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .pluss-dossier-tree__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .pluss-dossier-tree__subtitle {
            margin: 0.35rem 0 0;
            color: #475569;
            font-size: 0.92rem;
            max-width: 58rem;
        }

        .pluss-dossier-tree__stats {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .pluss-dossier-tree__pill {
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(148, 163, 184, 0.18);
            color: #0f766e;
            padding: 0.45rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .pluss-dossier-tree__year {
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.86);
            margin-top: 0.9rem;
        }

        .pluss-dossier-tree__year > summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.95rem 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .pluss-dossier-tree__year > summary::-webkit-details-marker,
        .pluss-dossier-tree__summary::-webkit-details-marker {
            display: none;
        }

        .pluss-dossier-tree__year-count {
            color: #0f766e;
            font-size: 0.8rem;
            font-weight: 700;
            background: rgba(16, 185, 129, 0.12);
            padding: 0.3rem 0.55rem;
            border-radius: 999px;
        }

        .pluss-dossier-tree__year-toolbar {
            padding: 0 1rem 0.2rem;
        }

        .pluss-dossier-tree__year-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: #0f766e;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .pluss-dossier-tree__list,
        .pluss-dossier-tree__children {
            list-style: none;
            margin: 0;
            padding: 0 0 0.8rem;
        }

        .pluss-dossier-tree__node {
            margin-left: var(--pluss-tree-indent, 0rem);
            padding: 0 1rem;
        }

        .pluss-dossier-tree__details,
        .pluss-dossier-tree__leaf {
            margin-top: 0.7rem;
            border-left: 2px solid rgba(16, 185, 129, 0.15);
            padding-left: 0.9rem;
        }

        .pluss-dossier-tree__summary,
        .pluss-dossier-tree__leaf {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
        }

        .pluss-dossier-tree__summary {
            cursor: pointer;
            padding: 0.15rem 0;
        }

        .pluss-dossier-tree__title-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .pluss-dossier-tree__title-text {
            font-weight: 700;
            color: #0f172a;
        }

        .pluss-dossier-tree__code {
            font-size: 0.76rem;
            color: #475569;
            background: rgba(226, 232, 240, 0.6);
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
        }

        .pluss-dossier-tree__meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            font-size: 0.76rem;
            color: #475569;
        }

        .pluss-dossier-tree__meta span {
            background: rgba(248, 250, 252, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 999px;
            padding: 0.25rem 0.45rem;
        }

        .pluss-dossier-tree__actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 0.45rem;
            font-size: 0.78rem;
        }

        .pluss-dossier-tree__actions a {
            color: #0f766e;
            font-weight: 700;
            text-decoration: none;
        }

        .pluss-dossier-tree__actions span {
            color: #475569;
        }

        .pluss-dossier-tree__empty {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.82);
            color: #475569;
        }

        @media (max-width: 768px) {
            .pluss-dossier-tree__header,
            .pluss-dossier-tree__summary,
            .pluss-dossier-tree__leaf,
            .pluss-dossier-tree__year > summary {
                display: block;
            }

            .pluss-dossier-tree__stats,
            .pluss-dossier-tree__meta,
            .pluss-dossier-tree__actions {
                margin-top: 0.65rem;
                justify-content: flex-start;
            }
        }
    </style>
@endonce

@once
    <script>
        (() => {
            const storageKey = 'pluss:dossiers-tree:open-state';

            const readState = () => {
                try {
                    return JSON.parse(window.localStorage.getItem(storageKey) ?? '{}');
                } catch {
                    return {};
                }
            };

            const writeState = (state) => {
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify(state));
                } catch {
                    return;
                }
            };

            const syncTreeState = () => {
                const state = readState();

                document.querySelectorAll('[data-pluss-tree-key]').forEach((detailsEl) => {
                    const key = detailsEl.getAttribute('data-pluss-tree-key');

                    if (!key) {
                        return;
                    }

                    if (Object.prototype.hasOwnProperty.call(state, key)) {
                        detailsEl.open = Boolean(state[key]);
                    }

                    if (detailsEl.dataset.plussTreeBound === '1') {
                        return;
                    }

                    detailsEl.dataset.plussTreeBound = '1';

                    detailsEl.addEventListener('toggle', () => {
                        const nextState = readState();
                        nextState[key] = detailsEl.open;
                        writeState(nextState);
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', syncTreeState, { once: true });
            } else {
                syncTreeState();
            }

            document.addEventListener('livewire:navigated', syncTreeState);
        })();
    </script>
@endonce

<section class="pluss-dossier-tree">
    <div class="pluss-dossier-tree__header">
        <div>
            <h3 class="pluss-dossier-tree__title">Arborescence GED par annee</h3>
            <p class="pluss-dossier-tree__subtitle">
                Cette vue permet de parcourir rapidement les dossiers par campagne annuelle, rubrique et sous-dossier sans perdre la table detaillee.
            </p>
        </div>

        <div class="pluss-dossier-tree__stats">
            <span class="pluss-dossier-tree__pill">{{ $totalDossiers }} dossiers</span>
            <span class="pluss-dossier-tree__pill">{{ $classifiedDossiers }} classes par annee</span>
        </div>
    </div>

    @forelse ($yearGroups as $group)
        <details class="pluss-dossier-tree__year" data-pluss-tree-key="year-{{ $group['year'] }}" @if($group['is_open']) open @endif>
            <summary>
                <span>Annee {{ $group['label'] }}</span>
                <span class="pluss-dossier-tree__year-count">{{ $group['count'] }} racine(s)</span>
            </summary>

            <div class="pluss-dossier-tree__year-toolbar">
                <a class="pluss-dossier-tree__year-link" href="{{ \App\Filament\Resources\Dossiers\DossierResource::getUrl('index') . '?' . http_build_query(['tableFilters[annee_activite][value]' => (string) $group['year']]) }}">
                    Voir la liste de l'annee {{ $group['label'] }}
                </a>
            </div>

            <ul class="pluss-dossier-tree__list">
                @foreach ($group['nodes'] as $node)
                    @include('filament.widgets.partials.dossier-tree-node', ['node' => $node, 'depth' => 0])
                @endforeach
            </ul>
        </details>
    @empty
        <div class="pluss-dossier-tree__empty">Aucun classement annuel n'est encore disponible. Utilisez l'action de generation d'arborescence annuelle pour initialiser vos dossiers.</div>
    @endforelse

    @if (filled($unclassifiedNodes))
        <details class="pluss-dossier-tree__year" data-pluss-tree-key="year-unclassified">
            <summary>
                <span>Dossiers hors classement annuel</span>
                <span class="pluss-dossier-tree__year-count">{{ count($unclassifiedNodes) }} racine(s)</span>
            </summary>

            <ul class="pluss-dossier-tree__list">
                @foreach ($unclassifiedNodes as $node)
                    @include('filament.widgets.partials.dossier-tree-node', ['node' => $node, 'depth' => 0])
                @endforeach
            </ul>
        </details>
    @endif
</section>