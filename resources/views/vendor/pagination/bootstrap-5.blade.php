@if ($paginator->hasPages())
    <nav class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <div>
            <small class="text-muted">
                Menampilkan <span class="fw-semibold text-dark">{{ $paginator->firstItem() }}</span> &ndash; <span class="fw-semibold text-dark">{{ $paginator->lastItem() }}</span> dari <span class="fw-semibold text-dark">{{ $paginator->total() }}</span> data
            </small>
        </div>

        <div>
            <ul class="pagination pagination-sm m-0">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link" aria-label="Sebelumnya"><i class="ri-arrow-left-s-line"></i></span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"><i class="ri-arrow-left-s-line"></i></a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"><i class="ri-arrow-right-s-line"></i></a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link" aria-label="Berikutnya"><i class="ri-arrow-right-s-line"></i></span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endif
