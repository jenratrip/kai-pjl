<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">
            7
        </span>

        <h2 class="font-bold text-[#1d2d50]">
            Petugas yang berdinas
        </h2>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        {{-- Petugas penyerah --}}
        <div class="rounded-lg bg-slate-50 p-4">
            <p class="text-xs text-slate-500">
                Penyerah / petugas yang mengakhiri dinas
            </p>

            <p class="mt-1 font-bold text-slate-800">
                {{ auth()->user()->name }}
            </p>

            <p class="mt-1 text-sm text-slate-500">
                NIP: {{ auth()->user()->nip ?: '-' }}
            </p>
        </div>

        {{-- Admin penerima --}}
        <div class="rounded-lg bg-slate-50 p-4">
            <label
                for="penerima_user_id"
                class="block text-xs font-semibold text-slate-500"
            >
                Admin penerima serah terima
            </label>

            <select
                id="penerima_user_id"
                name="penerima_user_id"
                form="serahTerimaSubmitForm"
                required
                class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >
                <option value="">Pilih admin penerima</option>

                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}">
                        {{ $admin->name }}{{ $admin->nip ? ' — '.$admin->nip : '' }}
                    </option>
                @endforeach
            </select>

            @if ($admins->isEmpty())
                <p class="mt-2 text-xs text-red-600">
                    Belum ada admin aktif yang dapat menerima serah terima.
                    Hubungi administrator sistem.
                </p>
            @endif
        </div>
    </div>
</section>
