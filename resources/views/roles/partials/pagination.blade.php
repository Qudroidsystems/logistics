<div class="row mt-3 align-items-center">
    <div class="col-sm">
        <div class="text-muted text-center text-sm-start">
            Showing <span class="fw-semibold">{{ $users->count() }}</span> of
            <span class="fw-semibold">{{ $users->total() }}</span> Results
        </div>
    </div>
    <div class="col-sm-auto mt-3 mt-sm-0">
        <div class="pagination-wrap hstack gap-2 justify-content-center">
            <!-- Previous Button -->
            <a class="page-item pagination-prev {{ $users->onFirstPage() ? 'disabled' : '' }}"
               href="javascript:void(0);"
               data-page="{{ $users->currentPage() - 1 }}">
                <i class="mdi mdi-chevron-left align-middle"></i>
            </a>

            <!-- Page Numbers -->
            <ul class="pagination listjs-pagination mb-0">
                @php
                    $current = $users->currentPage();
                    $last = $users->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $current + 2);

                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="1">1</a></li>';
                        if ($start > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($i = $start; $i <= $end; $i++) {
                        $active = $i == $current ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="javascript:void(0);" data-page="' . $i . '">' . $i . '</a></li>';
                    }

                    if ($end < $last) {
                        if ($end < $last - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="' . $last . '">' . $last . '</a></li>';
                    }
                @endphp
            </ul>

            <!-- Next Button -->
            <a class="page-item pagination-next {{ $users->hasMorePages() ? '' : 'disabled' }}"
               href="javascript:void(0);"
               data-page="{{ $users->currentPage() + 1 }}">
                <i class="mdi mdi-chevron-right align-middle"></i>
            </a>
        </div>
    </div>
</div>
