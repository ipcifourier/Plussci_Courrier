@php
    $depth = $depth ?? 0;
    $hasChildren = filled($node['children'] ?? []);
    $indent = min($depth, 5) * 1.1;
@endphp

<li class="pluss-dossier-tree__node" style="--pluss-tree-indent: {{ $indent }}rem;">
    @if ($hasChildren)
        <details class="pluss-dossier-tree__details" data-pluss-tree-key="node-{{ $node['id'] }}" @if($node['default_open']) open @endif>
            <summary class="pluss-dossier-tree__summary">
                <span class="pluss-dossier-tree__title-wrap">
                    <span class="pluss-dossier-tree__title-text">{{ $node['label'] }}</span>
                    <span class="pluss-dossier-tree__code">{{ $node['code'] }}</span>
                </span>
                <span class="pluss-dossier-tree__meta">
                    <span>{{ $node['type_label'] }}</span>
                    <span>{{ $node['documents_count'] }} doc. direct(s)</span>
                    <span>{{ $node['aggregated_documents_count'] }} doc. cumule(s)</span>
                    <span>{{ $node['aggregated_children_count'] }} sous-dossier(s) cumule(s)</span>
                </span>
            </summary>

            <div class="pluss-dossier-tree__actions">
                <a href="{{ $node['url'] }}">Ouvrir</a>
                <a href="{{ $node['edit_url'] }}">Modifier</a>
                <a href="{{ $node['create_child_url'] }}">Creer un sous-dossier ici</a>
                <a href="{{ $node['create_document_url'] }}">Creer un document ici</a>
                <a href="{{ $node['acquisition_url'] }}">Acquerir ici</a>
                <a href="{{ $node['filter_url'] }}">Filtrer la liste</a>
                @if (filled($node['owner']))
                    <span>Responsable: {{ $node['owner'] }}</span>
                @endif
            </div>

            <ul class="pluss-dossier-tree__children">
                @foreach ($node['children'] as $child)
                    @include('filament.widgets.partials.dossier-tree-node', ['node' => $child, 'depth' => $depth + 1])
                @endforeach
            </ul>
        </details>
    @else
        <div class="pluss-dossier-tree__leaf">
            <div>
                <div class="pluss-dossier-tree__title-wrap">
                    <span class="pluss-dossier-tree__title-text">{{ $node['label'] }}</span>
                    <span class="pluss-dossier-tree__code">{{ $node['code'] }}</span>
                </div>
                <div class="pluss-dossier-tree__meta">
                    <span>{{ $node['type_label'] }}</span>
                    <span>{{ $node['documents_count'] }} doc. direct(s)</span>
                    <span>{{ $node['aggregated_documents_count'] }} doc. cumule(s)</span>
                </div>
            </div>
            <div class="pluss-dossier-tree__actions">
                <a href="{{ $node['url'] }}">Ouvrir</a>
                <a href="{{ $node['edit_url'] }}">Modifier</a>
                <a href="{{ $node['create_child_url'] }}">Creer un sous-dossier ici</a>
                <a href="{{ $node['create_document_url'] }}">Creer un document ici</a>
                <a href="{{ $node['acquisition_url'] }}">Acquerir ici</a>
                <a href="{{ $node['filter_url'] }}">Filtrer la liste</a>
            </div>
        </div>
    @endif
</li>