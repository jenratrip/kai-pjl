<div class="overflow-hidden rounded-xl bg-white shadow-sm">

    <div class="border-b border-gray-200 px-5 py-4">
        <h3 class="font-semibold text-gray-800">
            Riwayat Aktivitas
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            Total hasil: {{ $activities->total() }} aktivitas
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Waktu</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Pengguna</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Aktivitas</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Modul</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Deskripsi</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Detail</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($activities as $activity)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                            {{ $activity->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-sm font-medium text-gray-800">
                                {{ $activity->user?->name ?? 'Pengguna tidak tersedia' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $activity->user?->nip ?? '-' }}
                            </p>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                {{ ucfirst($activity->action) }}
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                            {{ $activity->module ?? '-' }}
                        </td>

                        <td class="min-w-64 px-5 py-4 text-sm text-gray-600">
                            {{ $activity->description ?? '-' }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <a href="{{ route('superadmin.activities.show', $activity) }}"
                               class="font-medium text-blue-700 hover:underline">
                                Lihat detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">
                            Belum ada aktivitas yang cocok dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-200 px-5 py-4">
        {{ $activities->links() }}
    </div>

</div>