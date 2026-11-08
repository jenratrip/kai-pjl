<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">
            7
        </span>

        <h2 class="font-bold text-[#1d2d50]">
            Petugas yang berdinas
        </h2>
    </div>

    @php
        $namaPenerimaLama = old(
            'nama_penerima',
            data_get($serahTerima, 'ringkasan.nama_penerima', '')
        );

        $nipPenerimaLama = old(
            'nip_penerima',
            data_get($serahTerima, 'ringkasan.nip_penerima', '')
        );

        $penerimaIdLama = old(
            'penerima_user_id',
            $serahTerima?->penerima_user_id ?? ''
        );
    @endphp

    @if (!empty($modeEdit) && $serahTerima)
        <input type="hidden"
               name="serah_terima_id"
               value="{{ $serahTerima->id }}"
               form="serahTerimaSubmitForm">
    @endif

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
                NIPP / NIPKWT: {{ auth()->user()->nip ?: '-' }}
            </p>
        </div>

        {{-- Petugas penerima --}}
        <div class="rounded-lg bg-slate-50 p-4">
            <label for="penerima_search"
                   class="block text-xs font-semibold text-slate-600">
                Cari PJL penerima berdasarkan nama atau NIPP / NIPKWT
            </label>

            <input id="penerima_search"
                   type="text"
                   value="{{ $namaPenerimaLama }}"
                   maxlength="100"
                   autocomplete="off"
                   placeholder="Ketik nama atau NIPP / NIPKWT..."
                   aria-describedby="penerima_search_help"
                   aria-controls="penerima_search_results"
                   class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/20">

            <p id="penerima_search_help"
               class="mt-1 text-xs text-slate-500">
                Ketik minimal 2 karakter, lalu pilih petugas dari hasil pencarian.
            </p>

            <div id="penerima_search_status"
                 class="mt-2 text-xs text-slate-500"
                 role="status"
                 aria-live="polite"></div>

            <div id="penerima_search_results"
                 class="mt-2 hidden max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            </div>

            {{-- ID akun PJL yang benar-benar dipilih --}}
            <input type="hidden"
                   id="penerima_user_id"
                   name="penerima_user_id"
                   value="{{ $penerimaIdLama }}"
                   form="serahTerimaSubmitForm">

            <div id="penerima_selected"
                 class="{{ $penerimaIdLama ? '' : 'hidden' }} mt-3 rounded-lg border border-green-200 bg-green-50 p-3">
                <p class="text-xs font-semibold text-green-800">
                    PJL penerima terpilih
                </p>

                <p id="penerima_selected_name"
                   class="mt-1 font-bold text-slate-800">
                    {{ $namaPenerimaLama }}
                </p>

                <p class="mt-1 text-sm text-slate-600">
                    NIPP / NIPKWT: <span id="penerima_selected_nip">{{ $nipPenerimaLama ?: '-' }}</span>
                </p>

                <button type="button"
                        id="penerima_change_button"
                        class="mt-2 text-xs font-semibold text-[#1d2d50] underline">
                    Ganti penerima
                </button>
            </div>

            {{-- Snapshot nama dan NIP untuk kompatibilitas ringkasan --}}
            <input type="hidden"
                   id="nama_penerima"
                   name="nama_penerima"
                   value="{{ $namaPenerimaLama }}"
                   form="serahTerimaSubmitForm">

            <input type="hidden"
                   id="nip_penerima"
                   name="nip_penerima"
                   value="{{ $nipPenerimaLama }}"
                   form="serahTerimaSubmitForm">
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('penerima_search');
    const resultsBox = document.getElementById('penerima_search_results');
    const statusBox = document.getElementById('penerima_search_status');
    const selectedBox = document.getElementById('penerima_selected');
    const selectedName = document.getElementById('penerima_selected_name');
    const selectedNip = document.getElementById('penerima_selected_nip');
    const selectedId = document.getElementById('penerima_user_id');
    const nameInput = document.getElementById('nama_penerima');
    const nipInput = document.getElementById('nip_penerima');
    const changeButton = document.getElementById('penerima_change_button');
    const submitForm = document.getElementById('serahTerimaSubmitForm');

    if (!searchInput || !resultsBox || !selectedId) {
        return;
    }

    const endpoint = @json(route('user.serah-terima-dinas.cari-penerima'));

    let debounceTimer = null;
    let activeController = null;
    let requestNumber = 0;

    function clearResults() {
        resultsBox.replaceChildren();
        resultsBox.classList.add('hidden');
    }

    function showStatus(message) {
        statusBox.textContent = message;
    }

    function resetSelection() {
        selectedId.value = '';
        nameInput.value = '';
        nipInput.value = '';

        selectedBox.classList.add('hidden');
        searchInput.readOnly = false;
        searchInput.value = '';

        clearResults();
        showStatus('Silakan cari dan pilih akun PJL penerima.');
        searchInput.focus();
    }

    function choosePetugas(petugas) {
        selectedId.value = String(petugas.id);
        nameInput.value = petugas.name || '';
        nipInput.value = petugas.nip || '';

        selectedName.textContent = petugas.name || '-';
        selectedNip.textContent = petugas.nip || '-';

        searchInput.value = petugas.name || '';
        searchInput.readOnly = true;

        selectedBox.classList.remove('hidden');
        clearResults();
        showStatus('Akun PJL penerima berhasil dipilih.');
    }

    function renderResults(petugasList) {
        clearResults();

        if (!petugasList.length) {
            showStatus('Tidak ditemukan PJL aktif yang cocok.');
            return;
        }

        petugasList.forEach(function (petugas) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className =
                'block w-full border-b border-slate-100 px-3 py-3 text-left last:border-b-0 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none';

            const name = document.createElement('span');
            name.className = 'block text-sm font-semibold text-slate-800';
            name.textContent = petugas.name || '-';

            const nip = document.createElement('span');
            nip.className = 'mt-1 block text-xs text-slate-500';
            nip.textContent = 'NIP: ' + (petugas.nip || '-');

            button.append(name, nip);

            button.addEventListener('click', function () {
                choosePetugas(petugas);
            });

            resultsBox.appendChild(button);
        });

        resultsBox.classList.remove('hidden');
        showStatus('Pilih salah satu petugas dari hasil pencarian.');
    }

    async function searchPetugas(keyword, currentRequest) {
        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', keyword);

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: activeController.signal
            });

            if (!response.ok) {
                throw new Error('Pencarian gagal.');
            }

            const result = await response.json();

            if (currentRequest !== requestNumber || selectedId.value) {
                return;
            }

            renderResults(result.data || []);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            clearResults();
            showStatus('Pencarian gagal. Silakan coba lagi.');
        }
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);

        // Ketika petugas mengganti teks, pilihan sebelumnya tidak lagi valid.
        selectedId.value = '';
        nameInput.value = '';
        nipInput.value = '';
        selectedBox.classList.add('hidden');
        searchInput.readOnly = false;

        const keyword = searchInput.value.trim();
        const currentRequest = ++requestNumber;

        if (activeController) {
            activeController.abort();
        }

        clearResults();

        if (keyword.length < 2) {
            showStatus('Ketik minimal 2 karakter untuk mencari.');
            return;
        }

        showStatus('Mencari petugas...');

        debounceTimer = setTimeout(function () {
            searchPetugas(keyword, currentRequest);
        }, 300);
    });

    changeButton.addEventListener('click', resetSelection);

    if (submitForm) {
        submitForm.addEventListener('submit', function (event) {
            if (!selectedId.value) {
                event.preventDefault();
                showStatus('Pilih PJL penerima dari hasil pencarian sebelum menyimpan.');
                searchInput.focus();
                searchInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    }

    // Jangan otomatis menganggap nama lama sebagai akun terpilih.
    // Jika ID penerima sudah tersimpan, tampilkan identitas snapshot yang ada.
    if (selectedId.value) {
        searchInput.readOnly = true;
        selectedBox.classList.remove('hidden');
        showStatus('Penerima tersimpan. Gunakan "Ganti penerima" jika perlu.');
    } else {
        showStatus('Belum ada akun PJL penerima yang dipilih.');
    }
});
</script>
