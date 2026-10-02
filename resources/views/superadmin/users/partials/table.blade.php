@forelse ($users as $index => $user)

    <tr class="transition hover:bg-gray-50">

        {{-- No --}}
        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
            {{ $users->firstItem() + $index }}
        </td>


        {{-- NIP --}}
        <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-[#1b2b4b]">
            {{ $user->nip }}
        </td>


        {{-- Nama --}}
        <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-[#1b2b4b]">
            {{ $user->name }}
        </td>


        {{-- Email --}}
        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">
            {{ $user->email ?? '-' }}
        </td>


        {{-- Role --}}
        <td class="whitespace-nowrap px-5 py-4">

            @if ($user->role?->name === 'superadmin')

                <span class="inline-flex rounded-md bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">
                    Superadmin
                </span>

            @elseif ($user->role?->name === 'admin')

                <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                    Admin
                </span>

            @elseif ($user->role?->name === 'pjl')

                <span class="inline-flex rounded-md bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-600">
                    PJL
                </span>

            @else

                <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                    -
                </span>

            @endif

        </td>


        {{-- Status --}}
        <td class="whitespace-nowrap px-5 py-4">

            @if ($user->is_active)

                <span class="inline-flex rounded-md bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-600">
                    Aktif
                </span>

            @else

                <span class="inline-flex rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600">
                    Nonaktif
                </span>

            @endif

        </td>


        {{-- Aksi --}}
        <td class="whitespace-nowrap px-5 py-4">

            <div class="flex justify-center gap-2">

                {{-- Detail --}}
                <a
                    href="{{ route('superadmin.users.show', $user) }}"
                    class="rounded-lg bg-gray-50 p-2 text-gray-600 transition hover:bg-gray-100 hover:text-[#0b1f3a]"
                    title="Detail"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"
                        />

                    </svg>

                </a>


                {{-- Edit --}}
                <a
                    href="{{ route('superadmin.users.edit', $user) }}"
                    class="rounded-lg bg-gray-50 p-2 text-gray-600 transition hover:bg-gray-100 hover:text-[#0b1f3a]"
                    title="Edit"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 20h9"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.5 3.5a2.12 2.12 0 013 3L8 18l-4 1 1-4L16.5 3.5z"
                        />

                    </svg>

                </a>


                {{-- Toggle Status --}}
                @if (auth()->id() !== $user->id)

                    <button
                        type="button"
                        onclick="toggleUserStatus({{ $user->id }}, @js($user->name))"
                        class="rounded-lg p-2 transition
                            {{ $user->is_active
                                ? 'bg-red-50 text-red-500 hover:bg-red-100'
                                : 'bg-green-50 text-green-600 hover:bg-green-100'
                            }}"
                        title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                    >

                        @if ($user->is_active)

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 12h12"
                                />

                            </svg>

                        @else

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 12l4 4L19 6"
                                />

                            </svg>

                        @endif

                    </button>

                @endif

            </div>

        </td>

    </tr>

@empty

    <tr>

        <td
            colspan="7"
            class="px-5 py-12 text-center"
        >

            <div class="flex flex-col items-center justify-center">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="mb-3 h-10 w-10 text-gray-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M20 13V7a2 2 0 00-2-2h-4l-2-2H6a2 2 0 00-2 2v8"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 15a2 2 0 002 2h11a2 2 0 002-2l1-5H5l-1 5z"
                    />

                </svg>


                <p class="text-sm font-medium text-gray-500">
                    Tidak ada data pengguna
                </p>


                <p class="mt-1 text-xs text-gray-400">
                    Coba ubah kata pencarian atau filter.
                </p>

            </div>

        </td>

    </tr>

@endforelse