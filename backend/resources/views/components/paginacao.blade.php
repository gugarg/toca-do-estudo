@if ($paginator->hasPages())
    <nav class="pagination-bar" role="navigation" aria-label="Paginação">
        <div class="pagination-info">
            Mostrando <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong> registros
        </div>

        <div class="pagination-controls">
            @if ($paginator->onFirstPage())
                <button type="button" class="pagination-btn" disabled aria-disabled="true">
                    <x-icon name="chevron-left" size="13" />
                    <span>Anterior</span>
                </button>
            @else
                <button type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        class="pagination-btn"
                        rel="prev">
                    <x-icon name="chevron-left" size="13" />
                    <span>Anterior</span>
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button type="button"
                                    wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"
                                    class="pagination-btn is-active"
                                    aria-current="page"
                                    disabled>
                                {{ $page }}
                            </button>
                        @else
                            <button type="button"
                                    wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    wire:loading.attr="disabled"
                                    class="pagination-btn">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        class="pagination-btn"
                        rel="next">
                    <span>Próxima</span>
                    <x-icon name="chevron-right" size="13" />
                </button>
            @else
                <button type="button" class="pagination-btn" disabled aria-disabled="true">
                    <span>Próxima</span>
                    <x-icon name="chevron-right" size="13" />
                </button>
            @endif
        </div>
    </nav>
@endif
