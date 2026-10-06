<a class="page-item pagination-prev {{ $myclass->onFirstPage() ? 'disabled' : '' }}" href="javascript:void(0);" data-url="{{ $myclass->previousPageUrl() }}">
    <i class="mdi mdi-chevron-left align-middle"></i>
</a>
<ul class="pagination listjs-pagination mb-0">
    @foreach ($myclass->links()->elements[0] as $page => $url)
        <li class="page-item {{ $myclass->currentPage() == $page ? 'active' : '' }}">
            <a class="page-link" href="javascript:void(0);" data-url="{{ $url }}">{{ $page }}</a>
        </li>
    @endforeach
</ul>
<a class="page-item pagination-next {{ $myclass->hasMorePages() ? '' : 'disabled' }}" href="javascript:void(0);" data-url="{{ $myclass->nextPageUrl() }}">
    <i class="mdi mdi-chevron-right align-middle"></i>
</a>