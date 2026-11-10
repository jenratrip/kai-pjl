export function createDinasRosterPreview({ previewUrl, confirmUrl }) {
    return {
        previewUrl,
        confirmUrl,
        file: null,
        month: '10',
        year: '2026',
        loading: false,
        preview: null,
        shiftConfiguration: {},
        validation: null,
        errors: [],
        importError: '',
        importing: false,

        clearPreview() {
            this.file = null;
            this.loading = false;
            this.preview = null;
            this.shiftConfiguration = {};
            this.validation = null;
            this.errors = [];
            this.importError = '';
            this.importing = false;
        },

        async previewExternalFile(file) {
            if (!file || this.loading) {
                return;
            }

            if (!/\.xlsx$/i.test(file.name) || file.size > 10 * 1024 * 1024) {
                this.errors = ['Workbook harus berformat .xlsx dan berukuran maksimal 10 MB.'];
                this.preview = null;
                return;
            }

            this.file = file;
            this.loading = true;
            this.errors = [];
            this.preview = null;
            this.validation = null;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('month', this.month);
            formData.append('year', this.year);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    body: formData,
                });
                const data = await response.json();

                if (!response.ok) {
                    this.errors = Object.values(data.errors ?? {}).flat();
                    if (this.errors.length === 0 && data.message) {
                        this.errors = [data.message];
                    }
                    return;
                }

                this.preview = data.preview;
                this.shiftConfiguration = data.shiftConfiguration;
                this.validation = data.validation;
            } catch {
                this.errors = ['Pratinjau gagal dimuat. Periksa koneksi lalu coba lagi.'];
            } finally {
                this.loading = false;
            }
        },

        async confirmImport() {
            if (!this.file || !this.canConfirmImport() || this.importing) {
                return;
            }

            this.importing = true;
            this.importError = '';
            const formData = new FormData();
            formData.append('file', this.file);
            formData.append('month', this.month);
            formData.append('year', this.year);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch(this.confirmUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    body: formData,
                });
                const data = await response.json();

                if (!response.ok) {
                    this.importError = [data.message, ...Object.values(data.errors ?? {}).flat()]
                        .filter(Boolean)
                        .join(' ');
                    return;
                }

                window.location.reload();
            } catch {
                this.importError = 'Impor gagal diproses. Periksa koneksi, lalu buat ulang pratinjau sebelum mencoba lagi.';
            } finally {
                this.importing = false;
            }
        },

        get blockers() {
            return this.validation?.errors ?? [];
        },

        get previewReady() {
            return this.preview !== null && this.validation?.is_valid === true;
        },

        canConfirmImport() {
            return this.validation?.can_store === true;
        },

        accountBadgeClass(status) {
            if (status === 'cocok') {
                return 'bg-green-100 text-green-800';
            }

            return status === 'ambigu' ? 'bg-amber-100 text-amber-900' : 'bg-red-100 text-red-800';
        },

        assignmentBadgeClass(status) {
            return status === 'cocok' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        },
    };
}
