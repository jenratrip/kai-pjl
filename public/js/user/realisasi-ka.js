
        /* =====================================================
           DATA DARI CONTROLLER LARAVEL
        ===================================================== */

        let dataKA = window.realisasiKaConfig.data;

        dataKA = dataKA.map((ka, index) => ({
            no: ka.no ?? index + 1,
            nomor: ka.nomor ?? '',
            nama: ka.nama ?? '',
            jadwal: ka.jadwal ?? '-',
            aktual: ka.aktual ?? '',
            status: ['JALAN', 'BATAL', 'BELUM'].includes(ka.status)
                ? ka.status
                : 'BELUM',
            semboyan21: ka.semboyan21 ?? '',
            genta: ka.genta ?? '',
            pintu: ka.pintu ?? '',
            semboyan35: ka.semboyan35 ?? '',
            keterangan: ka.keterangan ?? '',
            jadwal_ka_id: ka.jadwal_ka_id ?? null,

            // ID realisasi dari database untuk mempertahankan identitas data.
            realisasi_ka_id: ka.realisasi_ka_id ?? null,

            is_manual: Boolean(ka.is_manual)
        }));

        let manualCheck = {
            semboyan21: '',
            genta: '',
            pintu: '',
            semboyan35: ''
        };

        /* ESCAPE HTML */
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /* WAKTU WIB */
        function getWIBTime() {
            const parts = new Intl.DateTimeFormat('en-GB', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).formatToParts(new Date());

            const result = {};

            parts.forEach(part => {
                if (['hour', 'minute', 'second'].includes(part.type)) {
                    result[part.type] = Number(part.value);
                }
            });

            return result;
        }

        function getCurrentMinutes() {
            const now = getWIBTime();
            return now.hour * 60 + now.minute;
        }

        function timeToMinutes(time) {
            if (!time || time === '-') {
                return null;
            }

            const parts = String(time).split(':');

            if (parts.length < 2) {
                return null;
            }

            const hours = Number(parts[0]);
            const minutes = Number(parts[1]);

            if (
                !Number.isInteger(hours) ||
                !Number.isInteger(minutes) ||
                hours < 0 || hours > 23 ||
                minutes < 0 || minutes > 59
            ) {
                return null;
            }

            return hours * 60 + minutes;
        }

        function formatMinutes(totalMinutes) {
            totalMinutes = (totalMinutes + 1440) % 1440;

            return String(Math.floor(totalMinutes / 60)).padStart(2, '0')
                + ':'
                + String(totalMinutes % 60).padStart(2, '0');
        }

        function updateWindowRange() {
            const element = document.getElementById('windowRange');

            if (!element) {
                return;
            }

            const now = getWIBTime();
            const currentMinutes = now.hour * 60 + now.minute;

            const currentTime =
                String(now.hour).padStart(2, '0') + ':' +
                String(now.minute).padStart(2, '0') + ':' +
                String(now.second).padStart(2, '0');

            element.textContent =
                `${currentTime} WIB · Rentang ` +
                `${formatMinutes(currentMinutes - 60)} – ` +
                `${formatMinutes(currentMinutes + 60)}`;
        }

        /*
         * KA terjadwal tampil jika jadwalnya berjarak maksimal
         * 60 menit dari waktu sekarang.
         *
         * KA manual selalu tampil.
         *
         * Filter ini hanya memengaruhi tampilan tabel.
         * Semua data dalam dataKA tetap dikirim saat disimpan.
         */
        function isWithinOneHour(ka) {
            if (ka.is_manual || !ka.jadwal || ka.jadwal === '-') {
                return true;
            }

            const jadwalMinutes = timeToMinutes(ka.jadwal);

            if (jadwalMinutes === null) {
                return false;
            }

            let difference = Math.abs(jadwalMinutes - getCurrentMinutes());

            // Menangani pergantian hari, misalnya 23:45 dan 00:15.
            difference = Math.min(difference, 1440 - difference);

            return difference <= 60;
        }

        function getVisibleKA() {
            return dataKA
                .map((ka, index) => ({ ka, index }))
                .filter(item => isWithinOneHour(item.ka));
        }

        /* TOMBOL SEMBOYAN */
        function createCheckGroup(value, index, field) {
            const yesClass = value === 'yes' ? 'active-yes' : 'inactive';
            const noClass = value === 'no' ? 'active-no' : 'inactive';

            return `
                <div class="check-group">
                    <button type="button"
                        class="check ${yesClass}"
                        onclick="setCheck(this, ${index}, '${field}', 'yes')"
                        aria-label="Ya"
                        title="Ya">✓</button>

                    <button type="button"
                        class="check ${noClass}"
                        onclick="setCheck(this, ${index}, '${field}', 'no')"
                        aria-label="Tidak"
                        title="Tidak">×</button>
                </div>
            `;
        }

        /* STATUS KA */
        function getStatusClass(status) {
            if (status === 'JALAN') return 'status-jalan';
            if (status === 'BATAL') return 'status-batal';
            return 'status-belum';
        }

        function createStatus(status, index) {
            return `
                <select class="status-select ${getStatusClass(status)}"
                    onchange="updateStatus(this, ${index})">
                    <option value="JALAN" ${status === 'JALAN' ? 'selected' : ''}>JALAN</option>
                    <option value="BATAL" ${status === 'BATAL' ? 'selected' : ''}>BATAL</option>
                    <option value="BELUM" ${status === 'BELUM' ? 'selected' : ''}>BELUM</option>
                </select>
            `;
        }

        /* RENDER TABEL */
        function renderTable() {
            const tbody = document.getElementById('kaTableBody');

            if (!tbody) {
                return;
            }

            const visibleKA = getVisibleKA();
            tbody.innerHTML = '';

            if (visibleKA.length === 0) {
                const message = dataKA.length === 0
                    ? 'Belum ada jadwal KA untuk dinas aktif ini.'
                    : 'Tidak ada KA dalam rentang waktu ±1 jam dari waktu sekarang.';

                tbody.innerHTML = `
                    <tr>
                        <td colspan="11"
                            style="height:85px;text-align:center;color:#8a93a1">
                            ${escapeHtml(message)}
                        </td>
                    </tr>
                `;

                updateSummary();
                updateWindowRange();
                return;
            }

            visibleKA.forEach(({ ka, index }, visibleIndex) => {
                const row = document.createElement('tr');
                row.dataset.index = index;

                row.innerHTML = `
                    <td>${visibleIndex + 1}</td>

                    <td>
                        <strong>${escapeHtml(ka.nomor)}</strong>
                        ${ka.is_manual
                            ? '<div style="margin-top:3px;color:#d9923e;font-size:8px">MANUAL</div>'
                            : ''}
                    </td>

                    <td>${escapeHtml(ka.nama)}</td>
                    <td>${escapeHtml(ka.jadwal)}</td>

                    <td>
                        <input type="time"
                            class="actual-time"
                            value="${escapeHtml(ka.aktual)}"
                            onchange="updateActualTime(${index}, this.value)">
                    </td>

                    <td>${createStatus(ka.status, index)}</td>

                    <td>${createCheckGroup(ka.semboyan21, index, 'semboyan21')}</td>
                    <td>${createCheckGroup(ka.genta, index, 'genta')}</td>
                    <td>${createCheckGroup(ka.pintu, index, 'pintu')}</td>
                    <td>${createCheckGroup(ka.semboyan35, index, 'semboyan35')}</td>

                    <td>
                        <input type="text"
                            class="note-input"
                            value="${escapeHtml(ka.keterangan)}"
                            placeholder="Keterangan"
                            onchange="updateKeterangan(${index}, this.value)">
                    </td>
                `;

                tbody.appendChild(row);
            });

            updateSummary();
            updateWindowRange();
        }

        /* UPDATE JAM AKTUAL */
        function updateActualTime(index, value) {
            if (!dataKA[index]) {
                return;
            }

            dataKA[index].aktual = value;
        }

        /* UPDATE STATUS */
        function updateStatus(select, index) {
            if (!dataKA[index]) {
                return;
            }

            const status = select.value;
            dataKA[index].status = status;

            select.className = `status-select ${getStatusClass(status)}`;

            /*
             * Jika status dibatalkan, semua indikator semboyan
             * otomatis berubah menjadi tidak (×).
             */
            if (status === 'BATAL') {
                const fields = [
                    'semboyan21',
                    'genta',
                    'pintu',
                    'semboyan35'
                ];

                fields.forEach(field => {
                    dataKA[index][field] = 'no';
                });

                const row = select.closest('tr');

                if (row) {
                    row.querySelectorAll('.check-group').forEach(group => {
                        const buttons = group.querySelectorAll('.check');

                        buttons.forEach(button => {
                            button.classList.remove('active-yes', 'active-no');
                            button.classList.add('inactive');
                        });

                        if (buttons[1]) {
                            buttons[1].classList.remove('inactive');
                            buttons[1].classList.add('active-no');
                        }
                    });
                }
            }

            updateSummary();
        }

        /* UPDATE SEMBOYAN */
        function setCheck(button, index, field, value) {
            if (!dataKA[index]) {
                return;
            }

            const group = button.parentElement;

            group.querySelectorAll('.check').forEach(item => {
                item.classList.remove('active-yes', 'active-no');
                item.classList.add('inactive');
            });

            button.classList.remove('inactive');
            button.classList.add(
                value === 'yes' ? 'active-yes' : 'active-no'
            );

            dataKA[index][field] = value;
        }

        /* UPDATE KETERANGAN */
        function updateKeterangan(index, value) {
            if (!dataKA[index]) {
                return;
            }

            dataKA[index].keterangan = value;
        }

        /* RINGKASAN BERDASARKAN KA YANG TAMPIL */
        function updateSummary() {
            const visibleData = getVisibleKA().map(item => item.ka);

            const total = visibleData.length;
            const jalan = visibleData.filter(ka => ka.status === 'JALAN').length;
            const batal = visibleData.filter(ka => ka.status === 'BATAL').length;
            const belum = visibleData.filter(ka => ka.status === 'BELUM').length;

            document.getElementById('totalKA').textContent = total;
            document.getElementById('jalanKA').textContent = jalan;
            document.getElementById('batalKA').textContent = batal;
            document.getElementById('belumKA').textContent = belum;

            const selesai = jalan + batal;
            const progress = total
                ? Math.round((selesai / total) * 100)
                : 0;

            document.getElementById('progressText').textContent = `${progress}%`;

            document.getElementById('progressGreen').style.width =
                `${total ? (jalan / total) * 100 : 0}%`;

            document.getElementById('progressRed').style.width =
                `${total ? (batal / total) * 100 : 0}%`;

            document.getElementById('progressGray').style.width =
                `${total ? (belum / total) * 100 : 0}%`;
        }

        /* SIMPAN REALISASI KE DATABASE */
        async function simpanRealisasi() {
            const tombol = document.getElementById('simpanBtn');

            if (dataKA.length === 0) {
                alert('Belum ada data KA untuk disimpan.');
                return;
            }

            tombol.disabled = true;
            tombol.textContent = 'Menyimpan...';

            try {
                /*
                 * Seluruh dataKA dikirim, bukan hanya data yang terlihat
                 * pada filter ±1 jam.
                 */
                const response = await fetch(
                    window.realisasiKaConfig.storeUrl,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content
                        },
                        body: JSON.stringify({
                            data: dataKA
                        })
                    }
                );

                const contentType = response.headers.get('content-type') || '';

                if (!contentType.includes('application/json')) {
                    if (response.status === 419) {
                        throw new Error(
                            'Sesi berakhir atau token CSRF tidak valid. Muat ulang halaman, lalu coba lagi.'
                        );
                    }

                    if (response.status === 401 || response.status === 403) {
                        throw new Error(
                            'Akses ditolak. Silakan login kembali dan periksa hak akses akun.'
                        );
                    }

                    throw new Error(
                        'Server tidak mengembalikan respons JSON. Periksa route dan log Laravel.'
                    );
                }

                const result = await response.json();

                if (!response.ok) {
                    const pesanValidasi = result.errors
                        ? Object.values(result.errors).flat().join('\n')
                        : result.message;

                    throw new Error(
                        pesanValidasi || 'Gagal menyimpan realisasi KA.'
                    );
                }

                /*
                 * Backend diharapkan mengembalikan:
                 * {
                 *   message: "...",
                 *   data: [
                 *     {
                 *       index: 0,
                 *       realisasi_ka_id: 123
                 *     }
                 *   ]
                 * }
                 *
                 * ID yang dikembalikan disimpan pada dataKA di browser.
                 * Dengan begitu, penyimpanan berikutnya bisa mengenali
                 * record realisasi yang sama.
                 */
                if (Array.isArray(result.data)) {
                    result.data.forEach(item => {
                        const index = Number(item.index);

                        if (
                            Number.isInteger(index) &&
                            index >= 0 &&
                            dataKA[index] &&
                            item.realisasi_ka_id !== undefined &&
                            item.realisasi_ka_id !== null
                        ) {
                            dataKA[index].realisasi_ka_id =
                                item.realisasi_ka_id;
                        }
                    });
                }

                alert(
                    result.message ||
                    'Realisasi KA berhasil disimpan ke database.'
                );

            } catch (error) {
                console.error('Gagal menyimpan realisasi:', error);

                alert(
                    'Realisasi belum berhasil disimpan.\n\n' +
                    error.message
                );
            } finally {
                tombol.disabled = false;
                tombol.textContent = '▣  Simpan Realisasi';
            }
        }

        /* CETAK DOKUMEN */
        function cetakDokumen() {
            window.print();
        }

        /* CHECK MANUAL */
        function setManualCheck(button) {
            const group = button.dataset.group;
            const value = button.dataset.value;

            document.querySelectorAll(`[data-group="${group}"]`).forEach(item => {
                item.classList.remove('active-yes', 'active-no');
                item.classList.add('inactive');
            });

            button.classList.remove('inactive');
            button.classList.add(
                value === 'yes' ? 'active-yes' : 'active-no'
            );

            const fieldMap = {
                manual21: 'semboyan21',
                manualGenta: 'genta',
                manualPintu: 'pintu',
                manual35: 'semboyan35'
            };

            manualCheck[fieldMap[group]] = value;
        }

        function resetManualChecks() {
            manualCheck = {
                semboyan21: '',
                genta: '',
                pintu: '',
                semboyan35: ''
            };

            document.querySelectorAll('.mini-check').forEach(button => {
                button.classList.remove('active-yes', 'active-no');
                button.classList.add('inactive');
            });
        }

        /* TAMBAH KA MANUAL */
        function tambahKAManual() {
            const nomor = document.getElementById('manualNomor').value.trim();
            const nama = document.getElementById('manualNama').value.trim();
            const aktual = document.getElementById('manualAktual').value;
            const keterangan = document.getElementById('manualKeterangan').value.trim();

            if (!nomor) {
                alert('Nomor KA wajib diisi.');
                document.getElementById('manualNomor').focus();
                return;
            }

            if (!nama) {
                alert('Nama KA wajib diisi.');
                document.getElementById('manualNama').focus();
                return;
            }

            if (!aktual) {
                alert('Jam aktual wajib diisi.');
                document.getElementById('manualAktual').focus();
                return;
            }

            dataKA.push({
                no: dataKA.length + 1,
                nomor,
                nama,
                jadwal: '-',
                aktual,
                status: 'JALAN',
                semboyan21: manualCheck.semboyan21,
                genta: manualCheck.genta,
                pintu: manualCheck.pintu,
                semboyan35: manualCheck.semboyan35,
                keterangan: keterangan || 'KA tambahan',
                jadwal_ka_id: null,

                // Record baru belum mempunyai ID realisasi database.
                realisasi_ka_id: null,

                is_manual: true
            });

            renderTable();

            document.getElementById('manualNomor').value = '';
            document.getElementById('manualNama').value = '';
            document.getElementById('manualAktual').value = '';
            document.getElementById('manualKeterangan').value = '';

            resetManualChecks();

            alert(
                `KA ${nomor} berhasil ditambahkan ke tabel. ` +
                'Klik "Simpan Realisasi" agar data tersimpan ke database.'
            );
        }

        /* SIDEBAR */
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');

            if (sidebar.classList.contains('open')) {
                closeSidebar();
                return;
            }

            sidebar.classList.add('open');

            document.getElementById('sidebarOverlay').classList.add('active');
            document.body.classList.add('menu-open');

            document.getElementById('hamburgerBtn').setAttribute(
                'aria-expanded',
                'true'
            );

            document.getElementById('hamburgerBtn').setAttribute(
                'aria-label',
                'Tutup menu'
            );
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const button = document.getElementById('hamburgerBtn');

            if (!sidebar || !overlay || !button) {
                return;
            }

            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.classList.remove('menu-open');

            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Buka menu');
        }

        document.querySelectorAll('.sidebar .menu-item').forEach(item => {
            item.addEventListener('click', closeSidebar);
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        /* REFRESH FILTER WAKTU */
        let lastVisibleIds = '';

        function getVisibleIds() {
            return getVisibleKA()
                .map(item => item.index)
                .join(',');
        }

        function refreshTimeFilter() {
            updateWindowRange();

            const currentVisibleIds = getVisibleIds();

            if (currentVisibleIds !== lastVisibleIds) {
                lastVisibleIds = currentVisibleIds;
                renderTable();
            }
        }

        /* MULAI */
        renderTable();
        lastVisibleIds = getVisibleIds();
        updateWindowRange();

        // Periksa perubahan waktu setiap detik.
        setInterval(refreshTimeFilter, 1000);
    


