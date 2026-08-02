@if ($paginator->hasPages())
    <nav class="teacher-pagination" aria-label="Student list pagination">
        @if ($paginator->onFirstPage())
            <span class="disabled" aria-disabled="true">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element)) <span class="disabled" aria-hidden="true">{{ $element }}</span> @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
