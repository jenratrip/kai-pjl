import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    requiresSerahTerimaReport,
    routeSerahTerimaCondition,
    validateSerahTerimaReport,
} from '../../resources/js/serah-terima-inventaris.js';

const blade = readFileSync(
    new URL('../../resources/views/user/serahterimacatatan/partials/inventaris.blade.php', import.meta.url),
    'utf8',
);
const script = readFileSync(
    new URL('../../resources/js/serah-terima-inventaris.js', import.meta.url),
    'utf8',
);

test('Rusak, Hilang and Perlu Perbaikan open the report popup while Baik saves directly', () => {
    const actions = [];
    for (const condition of ['rusak', 'hilang', 'perlu_perbaikan']) {
        assert.equal(requiresSerahTerimaReport(condition), true);
        assert.equal(routeSerahTerimaCondition(condition, {
            openReport: (value) => actions.push(`popup:${condition}:${value}`),
            saveBaik: () => actions.push('save'),
        }), 'report');
    }

    assert.equal(requiresSerahTerimaReport('baik'), false);
    assert.equal(routeSerahTerimaCondition('baik', {
        openReport: () => actions.push('unexpected-popup'),
        saveBaik: (condition) => actions.push(`save:${condition}`),
    }), 'save');

    assert.deepEqual(actions, [
        'popup:rusak:rusak',
        'popup:hilang:hilang',
        'popup:perlu_perbaikan:perlu_perbaikan',
        'save:baik',
    ]);
});

test('description and photo requirements match the existing Inventaris validation rules', () => {
    assert.deepEqual(validateSerahTerimaReport('rusak', '', null, false), {
        catatan: 'Keterangan kerusakan atau kehilangan wajib diisi.',
        bukti_foto: 'Foto bukti kerusakan wajib dilampirkan.',
    });
    assert.deepEqual(validateSerahTerimaReport('rusak', 'Bodi retak.', null, true), {});
    assert.deepEqual(validateSerahTerimaReport('hilang', 'Tidak ditemukan.', null, false), {});
    assert.deepEqual(validateSerahTerimaReport('hilang', '', null, false), {
        catatan: 'Keterangan kerusakan atau kehilangan wajib diisi.',
    });
    assert.deepEqual(validateSerahTerimaReport('perlu_perbaikan', '', null, false), {});
    assert.deepEqual(validateSerahTerimaReport('perlu_perbaikan', '', {
        type: 'image/jpeg',
        size: 10,
    }, false), {
        bukti_foto: 'Foto bukti hanya dapat dilampirkan untuk kondisi rusak atau hilang.',
    });
});

test('report popup keeps a preview and sends the report only on its confirmation control', () => {
    assert.match(blade, /data-report-preview/);
    assert.match(blade, /data-report-cancel/);
    assert.match(blade, /data-report-confirm/);
    const confirmHandler = script.slice(script.indexOf("confirmButton.addEventListener('click'"));
    const fileChangeHandler = script.slice(script.indexOf("photoInput.addEventListener('change'"), script.indexOf("confirmButton.addEventListener('click'"));
    assert.match(confirmHandler, /save\(activeCard, conditionInput\.value/);
    assert.doesNotMatch(fileChangeHandler, /fetch\s*\(|save\s*\(/);
});

test('report dialog is centered, viewport bounded, scrollable and keeps backdrop dismissal', () => {
    assert.match(blade, /data-report-dialog[\s\S]*?fixed left-1\/2 top-1\/2 m-0[\s\S]*?max-h-\[calc\(100dvh-2rem\)\][\s\S]*?overflow-y-auto/);
    assert.match(blade, /backdrop:bg-slate-900\/50/);
    assert.match(script, /dialog\.addEventListener\('cancel'/);
    assert.match(script, /dialog\.addEventListener\('click'[\s\S]*?clickedBackdrop[\s\S]*?cancelReport\(\)/);
});

test('report validation accepts JPEG, PNG and WebP up to five megabytes and rejects unsupported files', () => {
    for (const type of ['image/jpeg', 'image/png', 'image/webp']) {
        assert.deepEqual(validateSerahTerimaReport('hilang', 'Keterangan.', {
            type,
            size: 5 * 1024 * 1024,
        }, false), {});
    }
    assert.deepEqual(validateSerahTerimaReport('hilang', 'Keterangan.', {
        type: 'image/gif',
        size: 10,
    }, false), {
        bukti_foto: 'Foto harus berformat JPEG, PNG, atau WebP.',
    });
});
