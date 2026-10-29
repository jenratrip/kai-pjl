@extends('layouts.user')

@section('title', 'Catatan Dinas - Sistem PJL')

@section('content')
    @include('user.catatan-dinas._section', [
        'catatan' => $catatan,
        'daftarDinas' => $daftarDinas,
        'redirectTo' => '',
    ])
@endsection

@push('scripts')
    <script>
        "use strict";

        const catatanDinasConfig = {
            oldTanggal: @json((string) old('tanggal', '')),
            oldShiftId: @json((string) old('shift_id', '')),
            hasErrors: @json($errors->any())
        };

        let activeModal = null;
        let previousFocusedElement = null;

        // Modal umum
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            if (!activeModal) {
                previousFocusedElement = document.activeElement;
            }

            if (activeModal && activeModal !== modal) {
                activeModal.classList.add('hidden');
                activeModal.classList.remove('flex');
                activeModal.setAttribute('aria-hidden', 'true');
            }

            activeModal = modal;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');

            const firstInput = modal.querySelector(
                'input:not([readonly]):not([type=hidden]), select, textarea, button'
            );

            if (firstInput) firstInput.focus();
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');

            if (activeModal === modal) {
                activeModal = null;
                document.body.classList.remove('overflow-hidden');

                if (
                    previousFocusedElement &&
                    previousFocusedElement.isConnected
                ) {
                    previousFocusedElement.focus();
                }

                previousFocusedElement = null;
            }
        }

        // Tambah catatan
        function openAddModal() {
            updateShiftOptions(false);
            showModal('addModal');
        }

        function closeAddModal() {
            hideModal('addModal');
        }

        function closeOutside(event) {
            if (event.target.id === 'addModal') {
                closeAddModal();
            }
        }

        // Filter shift berdasarkan tanggal
        function updateShiftOptions(resetShift = false) {
            const tanggal = document.getElementById('catatanTanggal');
            const shift = document.getElementById('catatanShift');
            const help = document.getElementById('shiftHelp');

            if (!tanggal || !shift || !help || !shift.options.length) {
                return;
            }

            const selectedDate = tanggal.value;
            const previousShift = shift.value;

            let jumlahShiftCocok = 0;
            let shiftMasihSesuai = false;

            Array.from(shift.options).forEach((option, index) => {
                if (index === 0 || option.value === '') {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const cocok =
                    selectedDate !== '' &&
                    option.dataset.tanggal === selectedDate;

                option.hidden = !cocok;
                option.disabled = !cocok;

                if (cocok) {
                    jumlahShiftCocok++;

                    if (option.value === previousShift) {
                        shiftMasihSesuai = true;
                    }
                }
            });

            if (resetShift || !shiftMasihSesuai) {
                shift.value = '';
            }

            const placeholder = shift.options[0];

            if (!selectedDate) {
                placeholder.textContent = 'Pilih tanggal terlebih dahulu';
                help.textContent =
                    'Pilih tanggal dinas untuk menampilkan shift yang tersedia.';
            } else if (jumlahShiftCocok === 0) {
                placeholder.textContent = 'Shift tidak tersedia';
                help.textContent =
                    'Tidak ada jadwal dinas pada tanggal ini. ' +
                    'Periksa jadwal dinas akun PJL kamu.';
            } else {
                placeholder.textContent = 'Pilih Shift';
                help.textContent =
                    jumlahShiftCocok +
                    ' pilihan jadwal tersedia untuk tanggal ini.';
            }
        }

        // Detail catatan
        function viewDetail(tanggal, pjl, shift, catatan) {
            document.getElementById('detailTanggal').textContent = tanggal;
            document.getElementById('detailPjl').textContent = pjl;
            document.getElementById('detailShift').textContent = shift;
            document.getElementById('detailCatatan').textContent = catatan;

            showModal('detailModal');
        }

        function closeDetailModal() {
            hideModal('detailModal');
        }

        function closeDetailOutside(event) {
            if (event.target.id === 'detailModal') {
                closeDetailModal();
            }
        }

        // Inisialisasi halaman
        document.addEventListener('DOMContentLoaded', function () {
            const tanggal = document.getElementById('catatanTanggal');
            const shift = document.getElementById('catatanShift');
            const form = document.getElementById('catatanForm');
            const submitButton = document.getElementById('catatanSubmit');

            if (tanggal) {
                tanggal.addEventListener('change', function () {
                    updateShiftOptions(true);
                });
            }

            updateShiftOptions(false);

            if (tanggal && shift) {
                const oldTanggal = catatanDinasConfig.oldTanggal || '';
                const oldShiftId = catatanDinasConfig.oldShiftId || '';

                if (
                    oldTanggal &&
                    oldShiftId &&
                    tanggal.value === oldTanggal
                ) {
                    const oldOption = Array.from(shift.options).find(
                        option =>
                            option.value === oldShiftId &&
                            option.dataset.tanggal === oldTanggal
                    );

                    if (oldOption) {
                        shift.value = oldShiftId;
                        updateShiftOptions(false);
                    }
                }
            }

            if (form && submitButton) {
                form.addEventListener('submit', function () {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Menyimpan...';
                });
            }

            if (catatanDinasConfig.hasErrors) {
                openAddModal();
            }
        });

        // Pulihkan tombol jika halaman diambil dari browser back-forward cache
        window.addEventListener('pageshow', function (event) {
            const submitButton = document.getElementById('catatanSubmit');

            if (event.persisted && submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Simpan Catatan';
            }
        });

        // Tutup modal dengan tombol Escape
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape' || !activeModal) return;

            if (activeModal.id === 'addModal') {
                closeAddModal();
            } else if (activeModal.id === 'detailModal') {
                closeDetailModal();
            }
        });
    </script>
@endpush