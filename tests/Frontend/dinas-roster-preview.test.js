import test from 'node:test';
import assert from 'node:assert/strict';
import { createDinasRosterPreview } from '../../resources/js/admin/dinas-roster-preview.js';

function createPreviewState() {
    return {
        preview: {
            days_in_month: 3,
            staff: [{
                name: 'Petugas Uji',
                workbook_row: 8,
                account: { status: 'cocok' },
                assignment: {
                    status: 'cocok',
                    detail: 'JPL dipetakan dari kolom Jabatan workbook.',
                    jpl: { id: 11, code: '351', name: 'JPL 351' },
                },
                errors: [],
                days: [
                    { day: 1, date: '2026-10-01', code: 'P', valid: true, shift_status: 'cocok' },
                    { day: 2, date: '2026-10-02', code: 'L', valid: true, shift_status: 'libur' },
                    { day: 3, date: '2026-10-03', code: 'S', valid: true, shift_status: 'cocok' },
                ],
            }],
        },
        shiftConfiguration: {
            P: { status: 'cocok', name: 'Pagi' },
            S: { status: 'cocok', name: 'Siang' },
        },
        validation: {
            is_valid: true,
            errors: [],
            staff_count: 1,
            valid_staff_count: 1,
            invalid_staff_count: 0,
        },
    };
}

test('uses the automatically resolved JPL for the employee and all roster dates', () => {
    const preview = createDinasRosterPreview({ previewUrl: '/preview' });
    Object.assign(preview, createPreviewState());

    assert.equal(preview.previewReady, true);
    assert.equal(preview.preview.staff[0].assignment.jpl.code, '351');
    assert.equal(preview.preview.staff[0].days.length, 3);
    assert.equal(preview.preview.staff[0].days[1].holiday, undefined);
    assert.equal(preview.canConfirmImport(), false);
});

test('blocks import readiness when account, job mapping, workbook rows, or shifts are invalid', () => {
    const state = createPreviewState();
    state.preview.staff[0].account.status = 'tidak_cocok';
    state.preview.staff[0].assignment = {
        status: 'tidak_cocok',
        detail: 'Jabatan kosong.',
        jpl: null,
    };
    state.preview.staff[0].errors = ['Kode X tidak valid.'];
    state.shiftConfiguration.P.status = 'jam_tidak_cocok';
    state.validation = {
        is_valid: false,
        errors: [
            'Baris 8: akun belum cocok atau ambigu.',
            'Baris 8: Jabatan kosong.',
            'Kode X tidak valid.',
            'Master shift P belum sesuai dengan jam roster.',
        ],
        staff_count: 1,
        valid_staff_count: 0,
        invalid_staff_count: 1,
    };
    const preview = createDinasRosterPreview({ previewUrl: '/preview' });
    Object.assign(preview, state);

    assert.equal(preview.previewReady, false);
    assert.ok(preview.blockers.some((issue) => issue.includes('akun belum cocok')));
    assert.ok(preview.blockers.some((issue) => issue.includes('Jabatan kosong')));
    assert.ok(preview.blockers.includes('Kode X tidak valid.'));
    assert.ok(preview.blockers.some((issue) => issue.includes('Master shift P belum sesuai')));
});

test('uses server all-or-nothing result and refuses readiness when any staff row is invalid', () => {
    const state = createPreviewState();
    state.preview.staff.push({ ...state.preview.staff[0], workbook_row: 9 });
    state.validation = {
        is_valid: false,
        errors: ['Baris 9: JPL tidak aktif.'],
        staff_count: 2,
        valid_staff_count: 1,
        invalid_staff_count: 1,
    };
    const preview = createDinasRosterPreview({ previewUrl: '/preview' });
    Object.assign(preview, state);

    assert.equal(preview.previewReady, false);
    assert.deepEqual(preview.blockers, ['Baris 9: JPL tidak aktif.']);
    assert.equal(preview.canConfirmImport(), false);
});

test('keeps confirmation unavailable when server storage validation blocks persistence', () => {
    const preview = createDinasRosterPreview({ previewUrl: '/preview' });
    Object.assign(preview, createPreviewState());

    assert.equal(preview.previewReady, true);
    assert.equal(preview.canConfirmImport(), false);
});

test('clears an older roster preview when another import format is selected', () => {
    const preview = createDinasRosterPreview({ previewUrl: '/preview' });
    Object.assign(preview, createPreviewState());
    preview.file = { name: 'old.xlsx' };
    preview.importError = 'old error';

    preview.clearPreview();

    assert.equal(preview.file, null);
    assert.equal(preview.preview, null);
    assert.equal(preview.validation, null);
    assert.deepEqual(preview.errors, []);
    assert.equal(preview.importError, '');
    assert.equal(preview.canConfirmImport(), false);
});
