import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const helper = {};
runInNewContext(read('../../public/js/user/realisasi-ka-time-window.js'), { window: helper });
const timeWindow = helper.RealisasiKaTimeWindow;
const interactionWindow = {};
runInNewContext(read('../../public/js/user/realisasi-ka-interactions.js'), { window: interactionWindow });
const interactions = interactionWindow.RealisasiKaInteractions;

const scriptNames = [
    '01-data-utilitas-filter', '02-status-awas', '03-tabel-dan-state',
    '04-interaksi-ringkasan', '05-penyimpanan', '06-cetak-dan-ka-manual',
    '07-catatan-dinas', '08-inisialisasi',
];
const scripts = Object.fromEntries(scriptNames.map((name) => [
    name,
    read(`../../resources/views/user/realisasi-ka/scripts/${name}.blade.php`),
]));
const allScripts = Object.values(scripts).join('\n');
const blade = read('../../resources/views/user/realisasi-ka/index.blade.php');
const interactionsScript = scripts['04-interaksi-ringkasan'];
const saveScript = scripts['05-penyimpanan'];
const tableScript = scripts['03-tabel-dan-state'];

 test('filter jadwal mempertahankan jendela berjalan 60 menit hingga melewati tengah malam', () => {
    assert.deepEqual(JSON.parse(JSON.stringify(timeWindow.getWindow(23 * 60 + 44))), {
        startMinutes: 1424, endMinutes: 1484,
    });
    assert.equal(timeWindow.isWithinNextHour('00:44', 1424), true);
    assert.equal(timeWindow.isWithinNextHour('00:45', 1424), false);
    assert.deepEqual(JSON.parse(JSON.stringify(timeWindow.getWindow(617))), {
        startMinutes: 617, endMinutes: 677,
    });
});

test('klasifikasi keterlambatan tetap mengikuti pengaturan Admin', () => {
    const settings = {
        toleransiKeterlambatanMenit: 5,
        peringatanKeterlambatanTinggiMenit: 10,
        warningKeterlambatanAktif: true,
        warningKeterlambatanTinggiAktif: true,
    };
    assert.equal(timeWindow.getDelayStatus(4, settings), 'tepat');
    assert.equal(timeWindow.getDelayStatus(5, settings), 'tepat');
    assert.equal(timeWindow.getDelayStatus(6, settings), 'telat');
    assert.equal(timeWindow.getDelayStatus(10, settings), 'telat-tinggi');
    assert.equal(timeWindow.getDelayStatus(11, settings), 'telat-tinggi');
});

test('catatan keterlambatan cetak tetap menyebut status toleransi secara benar', () => {
    const disabled = {
        toleransiKeterlambatanMenit: 5,
        peringatanKeterlambatanTinggiMenit: 10,
        warningKeterlambatanAktif: false,
        warningKeterlambatanTinggiAktif: false,
    };
    const enabled = { ...disabled, warningKeterlambatanAktif: true, warningKeterlambatanTinggiAktif: true };
    assert.equal(timeWindow.getPrintDelayNote(4, disabled), 'Terlambat 4 menit (dalam toleransi)');
    assert.equal(timeWindow.getPrintDelayNote(230, disabled), 'Terlambat 230 menit (peringatan nonaktif)');
    assert.equal(timeWindow.getPrintDelayNote(230, enabled), 'Peringatan keterlambatan tinggi: 230 menit');
});

test('baris peringatan KA bersamaan hanya berasal dari jadwal KA terjadwal', () => {
    const indexes = timeWindow.getSimultaneousRowIndexes([
        { nomor: 'KA-1', is_bersamaan: true, is_manual: false },
        { nomor: 'KA-2', is_bersamaan: false, is_manual: false },
        { nomor: 'KA-3', is_bersamaan: true, is_manual: true },
    ]);
    assert.deepEqual(Array.from(indexes), [0]);
});

test('label pemeriksaan cetak tetap YA, TIDAK, dan BELUM', () => {
    assert.equal(timeWindow.getCheckPrintLabel('yes'), 'YA');
    assert.equal(timeWindow.getCheckPrintLabel('no'), 'TIDAK');
    assert.equal(timeWindow.getCheckPrintLabel(''), 'BELUM');
});

test('ringkasan cetak tetap menghitung semua jadwal dalam shift', () => {
    const rows = [
        { jadwal_ka_id: 1, jadwal: '23:55', aktual: '23:57', status: 'JALAN', is_manual: false },
        { jadwal_ka_id: 2, jadwal: '00:10', aktual: '', status: 'BELUM', is_manual: false },
        { jadwal_ka_id: 3, jadwal: '03:10', aktual: '', status: 'BATAL', is_manual: false },
    ];
    const printRows = timeWindow.getShiftRows(rows);
    assert.deepEqual(printRows.map(({ ka }) => ka.jadwal_ka_id), [1, 2, 3]);
    assert.equal(printRows[0].ka.aktual, '23:57');
});

test('status JALAN mengisi jam aktual dan seluruh pemeriksaan YES; klik ulang mengosongkan', () => {
    const ka = { status: 'BELUM', aktual: '', semboyan21: '', genta: '', pintu: '', semboyan35: '', keterangan: 'tetap' };
    interactions.toggleStatus(ka, 'JALAN', '00:05');
    assert.equal(ka.status, 'JALAN');
    assert.equal(ka.aktual, '00:05');
    assert.deepEqual(['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]), ['yes', 'yes', 'yes', 'yes']);
    interactions.toggleStatus(ka, 'JALAN', '00:06');
    assert.equal(ka.status, 'BELUM');
    assert.equal(ka.aktual, '');
    assert.deepEqual(['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]), ['', '', '', '']);
    assert.equal(ka.keterangan, 'tetap');
});

test('status BATAL mengosongkan jam aktual dan seluruh pemeriksaan NO', () => {
    const ka = { status: 'BELUM', aktual: '', semboyan21: '', genta: '', pintu: '', semboyan35: '' };
    interactions.toggleStatus(ka, 'BATAL', '00:05');
    assert.equal(ka.status, 'BATAL');
    assert.equal(ka.aktual, '');
    assert.deepEqual(['semboyan21', 'genta', 'pintu', 'semboyan35'].map((field) => ka[field]), ['no', 'no', 'no', 'no']);
});

test('tabel aktif memiliki tepat 13 kolom dan menghapus HULU/HILIR', () => {
    const headers = blade.match(/<th>(?:NO|NOMOR KA|NAMA KA|RELASI|JAM JADWAL|JAM AKTUAL|STATUS|S21|GENTA|PINTU|S35|KETERANGAN|AKSI)<\/th>/g) ?? [];
    assert.equal(headers.length, 13);
    assert.match(blade, /colspan="13"/);
    assert.doesNotMatch(blade, /<th>HULU<\/th>|<th>HILIR<\/th>/);
    assert.match(blade, /<th>S21<\/th>/);
    assert.match(blade, /<th>S35<\/th>/);
    assert.match(allScripts, /const JUMLAH_KOLOM_TABEL = 13/);
    assert.doesNotMatch(scripts['01-data-utilitas-filter'], /hulu:|hilir:/);
    assert.doesNotMatch(tableScript, /ka\.hulu|ka\.hilir/);
});

test('tabel memakai lebar penuh tanpa overflow horizontal dan teks dapat membungkus', () => {
    assert.match(blade, /realisasi-table \{ width: 100%; table-layout: fixed;/);
    assert.match(blade, /overflow-hidden/);
    assert.doesNotMatch(blade, /overflow-x-auto/);
    assert.match(tableScript, /break-words/);
    assert.match(tableScript, /w-full min-w-0|w-full min-w-24/);
});

test('tombol status memakai ikon centang dan silang dengan label aksesibel', () => {
    assert.match(tableScript, /aria-label="Tandai KA jalan"[\s\S]*?>✓<\/button>/);
    assert.match(tableScript, /aria-label="Tandai KA batal"[\s\S]*?>×<\/button>/);
    assert.match(tableScript, /setStatus\(\$\{index\}, 'JALAN'\)/);
    assert.match(tableScript, /setStatus\(\$\{index\}, 'BATAL'\)/);
});

test('tidak ada autosave: perubahan hanya menandai dirty dan fetch hanya di fungsi Simpan', () => {
    const marker = saveScript.slice(saveScript.indexOf('function tandaiBerubah('), saveScript.indexOf('function ambilCsrfToken('));
    const handlers = interactionsScript.slice(interactionsScript.indexOf('function setStatus('), interactionsScript.indexOf('/* =====================================================\n   RINGKASAN'));
    assert.doesNotMatch(marker, /setTimeout|fetch\s*\(|simpanRealisasi\s*\(/);
    assert.doesNotMatch(handlers, /fetch\s*\(|simpanRealisasi\s*\(/);
    assert.doesNotMatch(saveScript, /autoSaveTimer|retryTimer|otomatis|setTimeout\s*\(/);
    assert.match(saveScript, /async function simpanRealisasi\(\)/);
    assert.match(saveScript, /fetch\(CONFIG\.storeUrl/);
    assert.match(blade, /onclick="simpanRealisasi\(\)"/);
    assert.match(scripts['08-inisialisasi'], /hanya tersimpan setelah tombol Simpan Semua ditekan/);
});

test('perubahan baris hanya tersimpan setelah tombol Simpan baris ditekan', () => {
    assert.match(tableScript, /onclick="simpanBaris\(\$\{index\}\)"/);
    assert.match(tableScript, /function simpanBaris\(index\)[\s\S]*?simpanRealisasi\(\)/);
    assert.match(saveScript, /beforeunload/);
});

test('form KA manual tidak lagi meminta Hulu/Hilir dan data manual tidak mengirimkannya', () => {
    assert.doesNotMatch(blade, /manualHulu|manualHilir|for="manualHulu"|for="manualHilir"/);
    assert.doesNotMatch(scripts['06-cetak-dan-ka-manual'], /manualHulu|manualHilir|\bhulu,|\bhilir,/);
    assert.match(blade, /id="manualNomor"[\s\S]*id="manualNama"[\s\S]*id="manualRelasi"/);
});

test('header cetak A4 landscape memakai colspan 13 dan identitas petugas', () => {
    assert.match(blade, /@page \{ size: A4 landscape; margin: 10mm; \}/);
    assert.match(blade, /print-document-header-row/);
    assert.match(blade, /colspan="13"/);
    assert.match(blade, /KAI OPERASIONAL[\s\S]*SISTEM DIGITAL PJL[\s\S]*\$petugasCetak\?->name[\s\S]*Status:/);
});

test('helper dimuat sinkron sebelum konfigurasi script dan delapan partial', () => {
    const timeHelper = blade.indexOf('realisasi-ka-time-window.js');
    const interactionHelper = blade.indexOf('realisasi-ka-interactions.js');
    const firstPartial = blade.indexOf("scripts.01-data-utilitas-filter");
    const lastPartial = blade.indexOf("scripts.08-inisialisasi");
    assert.ok(timeHelper >= 0 && interactionHelper > timeHelper);
    assert.ok(firstPartial > interactionHelper);
    assert.ok(lastPartial > firstPartial);
    assert.doesNotMatch(blade, /realisasi-ka\.js|realisasi-ka\.css/);
});
