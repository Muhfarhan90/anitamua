<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    @if($showSummary ?? true)
        <p class="text-sm text-gray-500">
            Menampilkan
            <span class="font-semibold text-gray-700">{{ $paginator->firstItem() ?? 0 }}</span>
            @if($paginator->firstItem() !== $paginator->lastItem())
                –<span class="font-semibold text-gray-700">{{ $paginator->lastItem() ?? 0 }}</span>
            @endif
            dari <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span> data
        </p>
    @endif

    @if($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="ml-auto">
            <ul class="m-0 inline-flex list-none -space-x-px p-0 text-sm">
                @if($paginator->onFirstPage())
                    <li>
                        <span aria-disabled="true" aria-label="Halaman sebelumnya" class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-s-lg border border-gray-300 bg-gray-50 text-gray-300">
                            <i class="fas fa-chevron-left text-xs" aria-hidden="true"></i>
                        </span>
                    </li>
                @else
                    <li>
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="flex h-9 w-9 items-center justify-center rounded-s-lg border border-gray-300 bg-white text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-brand-light">
                            <i class="fas fa-chevron-left text-xs" aria-hidden="true"></i>
                        </a>
                    </li>
                @endif

                @foreach($elements as $element)
                    @if(is_string($element))
                        <li>
                            <span aria-hidden="true" class="flex h-9 items-center border border-gray-300 bg-white px-3 text-gray-400">{{ $element }}</span>
                        </li>
                    @elseif(is_array($element))
                        @foreach($element as $page => $url)
                            <li>
                                @if($page === $paginator->currentPage())
                                    <span aria-current="page" class="relative z-10 flex h-9 w-9 items-center justify-center border border-brand bg-brand font-semibold text-white">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" aria-label="Halaman {{ $page }}" class="flex h-9 w-9 items-center justify-center border border-gray-300 bg-white font-medium text-gray-600 transition hover:bg-brand-50 hover:text-brand focus:z-10 focus:outline-none focus:ring-2 focus:ring-brand-light">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                @if($paginator->hasMorePages())
                    <li>
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="flex h-9 w-9 items-center justify-center rounded-e-lg border border-gray-300 bg-white text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-brand-light">
                            <i class="fas fa-chevron-right text-xs" aria-hidden="true"></i>
                        </a>
                    </li>
                @else
                    <li>
                        <span aria-disabled="true" aria-label="Halaman berikutnya" class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-e-lg border border-gray-300 bg-gray-50 text-gray-300">
                            <i class="fas fa-chevron-right text-xs" aria-hidden="true"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    @endif
</div>
