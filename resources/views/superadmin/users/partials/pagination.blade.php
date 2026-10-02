@if ($users->hasPages())

    <div class="border-t border-gray-200 px-5 py-4">

        <div class="flex items-center justify-between">

            {{-- Info --}}
            <div class="text-sm text-gray-500">

                Menampilkan

                <span class="font-semibold text-gray-700">
                    {{ $users->firstItem() ?? 0 }}
                </span>

                sampai

                <span class="font-semibold text-gray-700">
                    {{ $users->lastItem() ?? 0 }}
                </span>

                dari

                <span class="font-semibold text-gray-700">
                    {{ $users->total() }}
                </span>

                user

            </div>


            {{-- Navigation --}}
            <div class="flex items-center gap-1">

                {{-- Previous --}}
                @if ($users->onFirstPage())

                    <span
                        class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300"
                    >
                        ‹
                    </span>

                @else

                    <a
                        href="{{ $users->previousPageUrl() }}"
                        class="ajax-pagination-link rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-50"
                    >
                        ‹
                    </a>

                @endif


                {{-- Page Numbers --}}
                @foreach (
                    $users->getUrlRange(
                        max(1, $users->currentPage() - 2),
                        min($users->lastPage(), $users->currentPage() + 2)
                    )
                    as $page => $url
                )

                    @if ($page == $users->currentPage())

                        <span
                            class="rounded-lg bg-[#0b1f3a] px-3 py-2 text-sm font-semibold text-white"
                        >
                            {{ $page }}
                        </span>

                    @else

                        <a
                            href="{{ $url }}"
                            class="ajax-pagination-link rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-50"
                        >
                            {{ $page }}
                        </a>

                    @endif

                @endforeach


                {{-- Next --}}
                @if ($users->hasMorePages())

                    <a
                        href="{{ $users->nextPageUrl() }}"
                        class="ajax-pagination-link rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 transition hover:bg-gray-50"
                    >
                        ›
                    </a>

                @else

                    <span
                        class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300"
                    >
                        ›
                    </span>

                @endif

            </div>

        </div>

    </div>

@else

    @if ($users->total() > 0)

        <div class="border-t border-gray-200 px-5 py-4">

            <div class="text-sm text-gray-500">

                Menampilkan

                <span class="font-semibold text-gray-700">
                    {{ $users->total() }}
                </span>

                user

            </div>

        </div>

    @endif

@endif